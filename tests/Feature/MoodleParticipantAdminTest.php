<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseApplicationResource\Pages\EditCourseApplication;
use App\Filament\Resources\CourseApplicationResource\Pages\ListCourseApplications;
use App\Jobs\SyncMoodleApplication;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\User;
use App\Services\Moodle\ParticipantSyncService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MoodleParticipantAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'moodle.enabled' => true]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
        Queue::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password']));
    }

    public function test_listing_and_edit_page_display_participant_sync_state(): void
    {
        $application = $this->application();
        $application->forceFill([
            'moodle_user_id' => 654,
            'moodle_sync_status' => ParticipantSyncService::STATUS_FAILED,
            'moodle_last_synced_at' => '2026-08-22 11:20:00',
            'moodle_sync_error' => 'Biztonságos résztvevői hiba',
        ])->saveQuietly();

        Livewire::test(ListCourseApplications::class)->assertSee('Sikertelen');
        Livewire::test(EditCourseApplication::class, ['record' => $application->getRouteKey()])
            ->assertSee('Moodle felhasználóazonosító')
            ->assertSee('654')
            ->assertSee('Sikertelen')
            ->assertSee('2026-08-22 11:20')
            ->assertSee('Biztonságos résztvevői hiba');
    }

    public function test_manual_action_queues_job_without_executing_moodle(): void
    {
        $application = $this->application();
        $application->forceFill([
            'moodle_user_id' => 654,
            'moodle_sync_status' => ParticipantSyncService::STATUS_FAILED,
            'moodle_sync_error' => 'Previous failure',
        ])->saveQuietly();
        Queue::fake();

        Livewire::test(EditCourseApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('syncMoodle')
            ->assertNotified('A Moodle résztvevő-szinkronizálás sorba állítva.');

        Queue::assertPushed(SyncMoodleApplication::class, fn (SyncMoodleApplication $job): bool => $job->courseApplicationId === $application->id);
        $this->assertSame(ParticipantSyncService::STATUS_PENDING, $application->fresh()->moodle_sync_status);
    }

    public function test_manual_action_is_hidden_for_disabled_integration_and_new_application(): void
    {
        $processed = $this->application();
        config(['moodle.enabled' => false]);
        Livewire::test(EditCourseApplication::class, ['record' => $processed->getRouteKey()])->assertActionHidden('syncMoodle');

        config(['moodle.enabled' => true]);
        $new = $this->application(['status' => 'NEW']);
        Livewire::test(EditCourseApplication::class, ['record' => $new->getRouteKey()])->assertActionHidden('syncMoodle');
    }

    private function application(array $attributes = []): CourseApplication
    {
        config(['moodle.enabled' => false]);
        $course = Course::query()->create(['name' => 'Participant admin test']);
        $actualCourse = ActualCourse::query()->create([
            'course_id' => $course->id,
            'moodle_course_id' => 321,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ]);
        $application = CourseApplication::query()->create(array_merge([
            'actual_course_id' => $actualCourse->id,
            'status' => 'PROCESSED',
            'participant_first_name' => 'Elek',
            'participant_last_name' => 'Teszt',
            'participant_email' => 'person@example.test',
        ], $attributes));
        config(['moodle.enabled' => true]);

        return $application;
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->unsignedBigInteger('moodle_course_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->unsignedBigInteger('moodle_user_id')->nullable();
            $table->string('moodle_sync_status')->nullable();
            $table->timestamp('moodle_last_synced_at')->nullable();
            $table->text('moodle_sync_error')->nullable();
            $table->string('status')->default('NEW');
            $table->string('certificate_language')->nullable();
            foreach (['payer_name', 'payer_tax_number', 'payer_address', 'payer_mailing_address', 'payer_email', 'payer_signatory', 'participant_last_name', 'participant_first_name', 'participant_birth_name', 'participant_birth_place', 'participant_birth_country', 'participant_birth_date', 'participant_address', 'participant_notification_address', 'participant_phone', 'participant_email', 'participant_mother_name', 'participant_education', 'participant_education_id', 'participant_supported', 'participant_grant_id'] as $field) {
                $table->string($field)->nullable();
            }
            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->timestamps();
        });
    }
}
