<?php

namespace Tests\Feature;

use App\Jobs\SyncMoodleApplication;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Services\Moodle\ParticipantSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class MoodleParticipantAutomaticSyncTest extends TestCase
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
    }

    public function test_new_application_creation_does_not_dispatch(): void
    {
        $application = $this->application();

        Queue::assertNothingPushed();
        $this->assertNull($application->moodle_sync_status);
    }

    public function test_new_to_processed_dispatches_and_marks_pending(): void
    {
        $application = $this->application();

        $application->update(['status' => 'PROCESSED']);

        $this->assertDispatchedFor($application);
        $this->assertSame(ParticipantSyncService::STATUS_PENDING, $application->fresh()->moodle_sync_status);
    }

    public function test_saving_processed_without_relevant_change_does_not_dispatch(): void
    {
        $application = $this->participatingApplication();
        Queue::fake();

        $application->save();

        Queue::assertNothingPushed();
    }

    public function test_cancelled_transition_dispatches_only_for_participating_application(): void
    {
        $participating = $this->participatingApplication();
        $new = $this->application();
        Queue::fake();

        $participating->update(['status' => 'CANCELLED']);
        $new->update(['status' => 'CANCELLED']);

        Queue::assertPushed(SyncMoodleApplication::class, 1);
        $this->assertDispatchedFor($participating);
    }

    public function test_relevant_participant_changes_dispatch_after_participation(): void
    {
        foreach (['participant_first_name', 'participant_last_name', 'participant_email'] as $field) {
            $application = $this->participatingApplication();
            Queue::fake();

            $application->update([$field => $field === 'participant_email' ? 'changed@example.test' : 'Changed']);

            $this->assertDispatchedFor($application);
        }
    }

    public function test_unrelated_and_moodle_state_changes_do_not_dispatch(): void
    {
        $application = $this->participatingApplication();
        Queue::fake();

        $application->update([
            'payer_name' => 'Changed payer',
            'participant_phone' => '+36 1 999 9999',
        ]);
        $application->update([
            'moodle_sync_status' => ParticipantSyncService::STATUS_SYNCED,
            'moodle_sync_error' => null,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_dispatcher_respects_disabled_integration(): void
    {
        $application = $this->application();
        config(['moodle.enabled' => false]);

        $application->update(['status' => 'PROCESSED']);

        Queue::assertNothingPushed();
        $this->assertNull($application->fresh()->moodle_sync_status);
    }

    public function test_job_policy_overlap_and_missing_record_handling(): void
    {
        $job = new SyncMoodleApplication(41);
        $middleware = $job->middleware();

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff);
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
        $this->assertSame('moodle-application:41', $middleware[0]->key);
        $this->mock(ParticipantSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sync');
        });

        $job->handle(app(ParticipantSyncService::class));
        $this->addToAssertionCount(1);
    }

    private function assertDispatchedFor(CourseApplication $application): void
    {
        Queue::assertPushed(
            SyncMoodleApplication::class,
            fn (SyncMoodleApplication $job): bool => $job->courseApplicationId === $application->id,
        );
    }

    private function participatingApplication(): CourseApplication
    {
        $application = $this->application();
        $application->forceFill([
            'status' => 'PROCESSED',
            'moodle_user_id' => 77,
            'moodle_sync_status' => ParticipantSyncService::STATUS_SYNCED,
        ])->saveQuietly();

        return $application->fresh();
    }

    private function application(): CourseApplication
    {
        config(['moodle.enabled' => false]);
        $course = Course::query()->create(['name' => 'Participant trigger test']);
        $actualCourse = ActualCourse::query()->create(['course_id' => $course->id, 'moodle_course_id' => 321]);
        $application = CourseApplication::query()->create([
            'actual_course_id' => $actualCourse->id,
            'participant_first_name' => 'Elek',
            'participant_last_name' => 'Teszt',
            'participant_email' => 'person@example.test',
            'payer_name' => 'Payer',
            'participant_phone' => '+36 30 123 4567',
            'status' => 'NEW',
        ]);
        config(['moodle.enabled' => true]);

        return $application;
    }

    private function createSchema(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->unsignedBigInteger('moodle_course_id')->nullable();
            $table->timestamps();
        });
        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->unsignedBigInteger('moodle_user_id')->nullable();
            $table->string('moodle_sync_status')->nullable();
            $table->timestamp('moodle_last_synced_at')->nullable();
            $table->text('moodle_sync_error')->nullable();
            $table->string('participant_first_name');
            $table->string('participant_last_name');
            $table->string('participant_email');
            $table->string('payer_name')->nullable();
            $table->string('participant_phone')->nullable();
            $table->string('status')->default('NEW');
            $table->timestamps();
        });
    }
}
