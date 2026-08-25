<?php

namespace Tests\Feature;

use App\Filament\Resources\ActualCourseResource\Pages\EditActualCourse;
use App\Filament\Resources\ActualCourseResource\Pages\ListActualCourses;
use App\Jobs\SyncMoodleCourse;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use App\Services\Moodle\CourseSyncService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MoodleCourseAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'moodle.enabled' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
        Queue::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]));
    }

    protected function tearDown(): void
    {
        config(['moodle.enabled' => false]);

        parent::tearDown();
    }

    public function test_listing_displays_compact_sync_statuses(): void
    {
        $pending = $this->createActualCourse(['moodle_sync_status' => CourseSyncService::STATUS_PENDING]);
        $failed = $this->createActualCourse(['moodle_sync_status' => CourseSyncService::STATUS_FAILED]);
        $pending->forceFill(['moodle_sync_status' => CourseSyncService::STATUS_PENDING])->saveQuietly();
        $failed->forceFill(['moodle_sync_status' => CourseSyncService::STATUS_FAILED])->saveQuietly();

        Livewire::test(ListActualCourses::class)
            ->assertSee('Függőben')
            ->assertSee('Sikertelen');
    }

    public function test_edit_page_displays_detailed_sync_state_and_safe_error(): void
    {
        $actualCourse = $this->createActualCourse();
        $actualCourse->forceFill([
            'moodle_course_id' => 987,
            'moodle_sync_status' => CourseSyncService::STATUS_FAILED,
            'moodle_last_synced_at' => '2026-08-22 10:15:00',
            'moodle_sync_error' => 'Biztonságos Moodle hibaüzenet',
        ])->saveQuietly();

        Livewire::test(EditActualCourse::class, ['record' => $actualCourse->getRouteKey()])
            ->assertSee('Moodle kurzusazonosító')
            ->assertSee('987')
            ->assertSee('Sikertelen')
            ->assertSee('2026-08-22 10:15')
            ->assertSee('Biztonságos Moodle hibaüzenet');
    }

    public function test_manual_action_only_queues_sync_and_marks_failed_course_pending(): void
    {
        $actualCourse = $this->createActualCourse();
        $actualCourse->forceFill([
            'moodle_sync_status' => CourseSyncService::STATUS_FAILED,
            'moodle_sync_error' => 'Korábbi hiba',
        ])->saveQuietly();
        Queue::fake();

        Livewire::test(EditActualCourse::class, ['record' => $actualCourse->getRouteKey()])
            ->callAction('syncMoodle')
            ->assertNotified('A Moodle szinkronizálás sorba állítva.');

        Queue::assertPushed(SyncMoodleCourse::class, fn (SyncMoodleCourse $job): bool => $job->actualCourseId === $actualCourse->id);
        $this->assertSame(CourseSyncService::STATUS_PENDING, $actualCourse->fresh()->moodle_sync_status);
    }

    public function test_manual_action_is_hidden_when_integration_is_disabled(): void
    {
        config(['moodle.enabled' => false]);
        $actualCourse = $this->createActualCourse();
        Queue::fake();

        Livewire::test(EditActualCourse::class, ['record' => $actualCourse->getRouteKey()])
            ->assertActionHidden('syncMoodle');

        Queue::assertNothingPushed();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
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

    private function createActualCourse(array $attributes = []): ActualCourse
    {
        $category = CourseCategory::query()->first() ?? CourseCategory::query()->create(['name' => 'Kategória']);
        $course = Course::query()->create([
            'course_category_id' => $category->id,
            'name' => 'Moodle admin teszt',
            'price' => 1000,
        ]);

        return ActualCourse::query()->create(array_merge([
            'course_id' => $course->id,
            'user_given_id' => random_int(1000, 9999),
            'place_of_event' => 'Budapest',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'application_deadline' => '2026-08-20',
            'way_of_participation' => 'group',
            'price' => 1000,
            'min_participants' => 1,
            'max_participants' => 15,
        ], $attributes));
    }
}
