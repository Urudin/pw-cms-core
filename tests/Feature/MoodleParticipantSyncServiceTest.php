<?php

namespace Tests\Feature;

use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Services\Moodle\MoodleException;
use App\Services\Moodle\ParticipantSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodleParticipantSyncServiceTest extends TestCase
{
    private const ENDPOINT = 'https://elearning.example.test/webservice/rest/server.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'moodle.enabled' => true,
            'moodle.base_url' => 'https://elearning.example.test',
            'moodle.web_service_token' => 'participant-secret-token',
            'moodle.rest_format' => 'json',
            'moodle.student_role_id' => 9,
            'moodle.user_auth' => 'manual',
            'moodle.user_create_password' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
    }

    public function test_persisted_user_is_updated_and_enrolled_without_lookup(): void
    {
        $application = $this->application([
            'moodle_user_id' => 77,
            'moodle_sync_status' => ParticipantSyncService::STATUS_FAILED,
            'moodle_sync_error' => 'Previous safe error',
        ]);
        $functions = [];
        Http::fake(function (Request $request) use (&$functions) {
            $functions[] = $request['wsfunction'];

            return Http::response('null', 200, ['Content-Type' => 'application/json']);
        });

        $synced = $this->service()->sync($application);

        $this->assertSame(['core_user_update_users', 'enrol_manual_enrol_users'], $functions);
        $this->assertSame(ParticipantSyncService::STATUS_SYNCED, $synced->moodle_sync_status);
        $this->assertNull($synced->moodle_sync_error);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_update_users'
            && $request['users'][0] === ['id' => 77, 'firstname' => 'Elek', 'lastname' => 'Teszt', 'email' => 'person@example.test']);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'enrol_manual_enrol_users'
            && $request['enrolments'][0] === ['roleid' => 9, 'userid' => 77, 'courseid' => 321, 'suspend' => 0]);
    }

    public function test_exact_email_lookup_adopts_and_updates_existing_user(): void
    {
        $application = $this->application();
        Http::fake(function (Request $request) {
            return match ($request['wsfunction']) {
                'core_user_get_users_by_field' => Http::response([['id' => 88, 'email' => 'person@example.test']]),
                default => Http::response('null', 200, ['Content-Type' => 'application/json']),
            };
        });

        $this->service()->sync($application);

        $this->assertSame(88, $application->fresh()->moodle_user_id);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_get_users_by_field'
            && $request['field'] === 'email' && $request['values'] === ['person@example.test']);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_update_users'
            && $request['users'][0]['id'] === 88);
    }

    public function test_missing_user_is_created_with_configured_mvp_fields_and_retry_does_not_recreate(): void
    {
        $application = $this->application(['participant_email' => 'Person@Example.Test']);
        $functions = [];
        Http::fake(function (Request $request) use (&$functions) {
            $functions[] = $request['wsfunction'];

            return match ($request['wsfunction']) {
                'core_user_get_users_by_field' => Http::response([]),
                'core_user_create_users' => Http::response([['id' => 99, 'username' => 'person@example.test']]),
                default => Http::response('null', 200, ['Content-Type' => 'application/json']),
            };
        });

        $this->service()->sync($application);
        $this->service()->sync($application->fresh());

        $this->assertSame(99, $application->fresh()->moodle_user_id);
        $this->assertSame(1, collect($functions)->filter(fn (string $function): bool => $function === 'core_user_create_users')->count());
        $this->assertSame(2, collect($functions)->filter(fn (string $function): bool => $function === 'enrol_manual_enrol_users')->count());
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_create_users'
            && $request['users'][0] === [
                'username' => 'person@example.test',
                'firstname' => 'Elek',
                'lastname' => 'Teszt',
                'email' => 'person@example.test',
                'auth' => 'manual',
                'createpassword' => true,
            ]);
    }

    public function test_ambiguous_lookup_fails_safely_without_create_or_enrol(): void
    {
        $application = $this->application();
        Http::fake([self::ENDPOINT => Http::response([
            ['id' => 1, 'email' => 'person@example.test'],
            ['id' => 2, 'email' => 'person@example.test'],
        ])]);

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($application);
        } finally {
            $failed = $application->fresh();
            $this->assertSame(ParticipantSyncService::STATUS_FAILED, $failed->moodle_sync_status);
            $this->assertStringContainsString('more than one', $failed->moodle_sync_error);
            Http::assertSentCount(1);
        }
    }

    public function test_malformed_lookup_fails_safely(): void
    {
        $application = $this->application();
        Http::fake([self::ENDPOINT => Http::response(['users' => []])]);

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($application);
        } finally {
            $this->assertSame(ParticipantSyncService::STATUS_FAILED, $application->fresh()->moodle_sync_status);
            Http::assertSentCount(1);
        }
    }

    public function test_missing_course_mapping_fails_before_user_operations(): void
    {
        $application = $this->application();
        $application->actualCourse->forceFill(['moodle_course_id' => null])->saveQuietly();
        Http::fake();

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($application->fresh('actualCourse'));
        } finally {
            $this->assertSame(ParticipantSyncService::STATUS_FAILED, $application->fresh()->moodle_sync_status);
            Http::assertSentCount(0);
        }
    }

    public function test_cancel_suspends_and_return_to_processed_reactivates_only_mapped_course(): void
    {
        $application = $this->application(['status' => 'CANCELLED', 'moodle_user_id' => 77]);
        Http::fake([self::ENDPOINT => Http::response('null', 200, ['Content-Type' => 'application/json'])]);

        $this->service()->sync($application);
        $application->forceFill(['status' => 'PROCESSED'])->saveQuietly();
        $this->service()->sync($application->fresh('actualCourse'));

        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'enrol_manual_enrol_users'
            && $request['enrolments'][0]['courseid'] === 321
            && $request['enrolments'][0]['userid'] === 77
            && $request['enrolments'][0]['suspend'] === 1);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'enrol_manual_enrol_users'
            && $request['enrolments'][0]['courseid'] === 321
            && $request['enrolments'][0]['userid'] === 77
            && $request['enrolments'][0]['suspend'] === 0);
        Http::assertNotSent(fn (Request $request): bool => $request['wsfunction'] === 'enrol_manual_unenrol_users');
    }

    public function test_unmapped_cancellation_is_a_successful_noop(): void
    {
        $application = $this->application(['status' => 'CANCELLED']);
        Http::fake();

        $synced = $this->service()->sync($application);

        $this->assertSame(ParticipantSyncService::STATUS_SYNCED, $synced->moodle_sync_status);
        Http::assertSentCount(0);
    }

    public function test_email_update_uses_persisted_user_and_uniqueness_failure_is_recorded(): void
    {
        $application = $this->application(['moodle_user_id' => 77, 'participant_email' => 'new@example.test']);
        Http::fake([self::ENDPOINT => Http::response([
            'exception' => 'invalid_parameter_exception',
            'errorcode' => 'invalidparameter',
            'message' => 'Email address already exists',
        ])]);

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($application);
        } finally {
            $this->assertSame(77, $application->fresh()->moodle_user_id);
            $this->assertSame(ParticipantSyncService::STATUS_FAILED, $application->fresh()->moodle_sync_status);
            Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_update_users'
                && $request['users'][0]['id'] === 77
                && $request['users'][0]['email'] === 'new@example.test');
            Http::assertNotSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_get_users_by_field');
        }
    }

    private function service(): ParticipantSyncService
    {
        return app(ParticipantSyncService::class);
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

    private function application(array $attributes = []): CourseApplication
    {
        config(['moodle.enabled' => false]);
        $course = Course::query()->create(['name' => 'Moodle participant test']);
        $actualCourse = ActualCourse::query()->create(['course_id' => $course->id, 'moodle_course_id' => 321]);
        $application = CourseApplication::query()->create([
            'actual_course_id' => $actualCourse->id,
            'participant_first_name' => 'Elek',
            'participant_last_name' => 'Teszt',
            'participant_email' => 'person@example.test',
            'payer_name' => 'Unrelated payer',
            'participant_phone' => '+36 30 123 4567',
            'status' => 'NEW',
        ]);
        $application->forceFill(array_merge(['status' => 'PROCESSED'], $attributes))->saveQuietly();
        config(['moodle.enabled' => true]);

        return $application->fresh('actualCourse');
    }
}
