<?php

namespace Tests\Feature;

use App\Jobs\SyncMoodleCourse;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\Moodle\CourseSyncDispatcher;
use App\Services\Moodle\CourseSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class MoodleCourseAutomaticSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'moodle.enabled' => true,
            'moodle.course_category_id' => 42,
            'moodle.course_visible' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        config(['moodle.enabled' => false]);

        parent::tearDown();
    }

    public function test_creating_actual_course_dispatches_when_moodle_is_enabled(): void
    {
        $actualCourse = $this->createActualCourse();

        Queue::assertPushed(
            SyncMoodleCourse::class,
            fn (SyncMoodleCourse $job) => $job->actualCourseId === $actualCourse->id,
        );
        $this->assertSame(CourseSyncService::STATUS_PENDING, $actualCourse->fresh()->moodle_sync_status);
    }

    public function test_creating_actual_course_does_not_dispatch_or_change_state_when_disabled(): void
    {
        config(['moodle.enabled' => false]);

        $actualCourse = $this->createActualCourse()->fresh();

        Queue::assertNothingPushed();
        $this->assertNull($actualCourse->moodle_course_id);
        $this->assertNull($actualCourse->moodle_sync_status);
        $this->assertNull($actualCourse->moodle_last_synced_at);
        $this->assertNull($actualCourse->moodle_sync_error);
    }

    public function test_changing_start_date_dispatches(): void
    {
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        $actualCourse->update(['start_date' => '2026-10-01']);

        $this->assertJobWasDispatchedFor($actualCourse);
    }

    public function test_changing_end_date_dispatches(): void
    {
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        $actualCourse->update(['end_date' => '2026-10-05']);

        $this->assertJobWasDispatchedFor($actualCourse);
    }

    public function test_changing_course_id_dispatches(): void
    {
        $actualCourse = $this->createActualCourse();
        $otherCourse = $this->createCourse(['name' => 'Masik kepzes']);
        Queue::fake();

        $actualCourse->update(['course_id' => $otherCourse->id]);

        $this->assertJobWasDispatchedFor($actualCourse);
    }

    public function test_changing_only_moodle_sync_state_does_not_dispatch(): void
    {
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        $actualCourse->update([
            'moodle_course_id' => 123,
            'moodle_sync_status' => 'synced',
            'moodle_last_synced_at' => now(),
            'moodle_sync_error' => null,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_changing_unsupported_actual_course_fields_does_not_dispatch(): void
    {
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        $actualCourse->update([
            'classification' => 'Mas besorolas',
            'place_of_event' => 'Mas helyszin',
            'way_of_participation' => 'individual',
            'price' => 175000,
            'min_participants' => 2,
            'max_participants' => 20,
            'application_deadline' => '2026-08-25',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_course_name_change_dispatches_only_participating_actual_courses(): void
    {
        $course = $this->createCourse();
        $mapped = $this->createActualCourse($course, ['moodle_course_id' => 111]);
        $attempted = $this->createActualCourse($course, ['moodle_sync_status' => 'failed']);
        $historical = $this->createActualCourse($course);
        $historical->forceFill(['moodle_sync_status' => null])->saveQuietly();
        Queue::fake();

        $course->update(['name' => 'Frissitett kepzesnev']);

        Queue::assertPushed(SyncMoodleCourse::class, 2);
        $this->assertJobWasDispatchedFor($mapped);
        $this->assertJobWasDispatchedFor($attempted);
        Queue::assertNotPushed(
            SyncMoodleCourse::class,
            fn (SyncMoodleCourse $job) => $job->actualCourseId === $historical->id,
        );
    }

    public function test_unrelated_course_change_does_not_dispatch(): void
    {
        $course = $this->createCourse();
        $this->createActualCourse($course, ['moodle_course_id' => 111]);
        Queue::fake();

        $course->update(['price' => 135000, 'is_active' => false, 'listed' => false]);

        Queue::assertNothingPushed();
    }

    public function test_job_reloads_course_and_uses_configured_category_and_visibility(): void
    {
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        config([
            'moodle.course_category_id' => '73',
            'moodle.course_visible' => true,
        ]);

        $this->mock(CourseSyncService::class, function (MockInterface $mock) use ($actualCourse) {
            $mock->shouldReceive('sync')
                ->once()
                ->withArgs(function (ActualCourse $reloaded, bool $visible, ?int $categoryId) use ($actualCourse) {
                    return $reloaded->id === $actualCourse->id
                        && $reloaded !== $actualCourse
                        && $reloaded->relationLoaded('course')
                        && $reloaded->course->relationLoaded('courseCategory')
                        && $reloaded->relationLoaded('days')
                        && $visible === true
                        && $categoryId === 73;
                })
                ->andReturnUsing(fn (ActualCourse $course) => $course);
        });

        (new SyncMoodleCourse($actualCourse->id))->handle(app(CourseSyncService::class));
    }

    public function test_job_safely_ignores_missing_actual_course(): void
    {
        $this->mock(CourseSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sync');
        });

        (new SyncMoodleCourse(999999))->handle(app(CourseSyncService::class));

        $this->addToAssertionCount(1);
    }

    public function test_job_applies_per_actual_course_overlap_protection(): void
    {
        $firstMiddleware = (new SyncMoodleCourse(41))->middleware();
        $sameCourseMiddleware = (new SyncMoodleCourse(41))->middleware();
        $otherCourseMiddleware = (new SyncMoodleCourse(42))->middleware();

        $this->assertCount(1, $firstMiddleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $firstMiddleware[0]);
        $this->assertSame('moodle-course:41', $firstMiddleware[0]->key);
        $this->assertSame($firstMiddleware[0]->key, $sameCourseMiddleware[0]->key);
        $this->assertNotSame($firstMiddleware[0]->key, $otherCourseMiddleware[0]->key);
        $this->assertSame(30, $firstMiddleware[0]->releaseAfter);
        $this->assertSame(300, $firstMiddleware[0]->expiresAfter);
    }

    public function test_job_has_limited_increasing_retry_policy(): void
    {
        $job = new SyncMoodleCourse(41);

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff);
    }

    public function test_failed_course_can_be_manually_requeued_without_recursive_dispatch(): void
    {
        $actualCourse = $this->createActualCourse();
        $actualCourse->forceFill([
            'moodle_sync_status' => CourseSyncService::STATUS_FAILED,
            'moodle_sync_error' => 'Safe previous error',
        ])->saveQuietly();
        Queue::fake();

        app(CourseSyncDispatcher::class)->dispatch($actualCourse);

        $this->assertSame(CourseSyncService::STATUS_PENDING, $actualCourse->fresh()->moodle_sync_status);
        $this->assertSame('Safe previous error', $actualCourse->fresh()->moodle_sync_error);
        Queue::assertPushed(SyncMoodleCourse::class, 1);
    }

    private function assertJobWasDispatchedFor(ActualCourse $actualCourse): void
    {
        Queue::assertPushed(
            SyncMoodleCourse::class,
            fn (SyncMoodleCourse $job) => $job->actualCourseId === $actualCourse->id,
        );
    }

    private function createSchema(): void
    {
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_category_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->boolean('listed')->default(true);
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moodle_course_id')->nullable();
            $table->string('moodle_sync_status')->nullable();
            $table->timestamp('moodle_last_synced_at')->nullable();
            $table->text('moodle_sync_error')->nullable();
            $table->foreignId('course_id');
            $table->string('classification')->nullable();
            $table->unsignedBigInteger('user_given_id')->nullable();
            $table->string('place_of_event')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('way_of_participation');
            $table->integer('price')->nullable();
            $table->smallInteger('min_participants')->default(0);
            $table->smallInteger('max_participants')->default(15);
            $table->timestamps();
        });

        Schema::create('actual_course_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->date('day');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    private function createCourse(array $attributes = []): Course
    {
        $category = CourseCategory::query()->first()
            ?? CourseCategory::query()->create(['name' => 'Innovaciomenedzsment']);

        return Course::query()->create(array_merge([
            'course_category_id' => $category->id,
            'course_id' => null,
            'name' => 'Innovaciomenedzsment kepzes',
            'price' => 120000,
            'is_active' => true,
            'listed' => true,
            'description' => null,
        ], $attributes));
    }

    private function createActualCourse(?Course $course = null, array $attributes = []): ActualCourse
    {
        $course ??= $this->createCourse();

        return ActualCourse::query()->create(array_merge([
            'course_id' => $course->id,
            'classification' => 'Minositett oktatas',
            'user_given_id' => 12345,
            'place_of_event' => '1051 Budapest, Arany Janos u. 15.',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'application_deadline' => '2026-08-20',
            'way_of_participation' => 'group',
            'price' => 150000,
            'min_participants' => 1,
            'max_participants' => 15,
        ], $attributes));
    }
}
