<?php

namespace Tests\Feature;

use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCategory;
use App\Services\Moodle\MoodleParticipantPayloadMapper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodleParticipantPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    public function test_migration_adds_nullable_moodle_sync_fields_to_course_applications(): void
    {
        $this->createApplicationSchema(withMoodleFields: false);

        $migration = require base_path('database/migrations/2026_08_14_000001_add_moodle_sync_fields_to_course_applications_table.php');
        $migration->up();

        foreach (['moodle_user_id', 'moodle_sync_status', 'moodle_last_synced_at', 'moodle_sync_error'] as $column) {
            $this->assertTrue(Schema::hasColumn('course_applications', $column), "Missing column: {$column}");
        }

        $application = $this->createCourseApplication();
        $row = DB::table('course_applications')->find($application->id);

        $this->assertNull($row->moodle_user_id);
        $this->assertNull($row->moodle_sync_status);
        $this->assertNull($row->moodle_last_synced_at);
        $this->assertNull($row->moodle_sync_error);
    }

    public function test_course_application_casts_moodle_sync_fields(): void
    {
        $this->createApplicationSchema();

        $application = $this->createCourseApplication([
            'moodle_user_id' => '456',
            'moodle_sync_status' => 'synced',
            'moodle_last_synced_at' => '2026-08-14 11:20:00',
            'moodle_sync_error' => null,
        ])->fresh();

        $this->assertSame(456, $application->moodle_user_id);
        $this->assertInstanceOf(Carbon::class, $application->moodle_last_synced_at);
        $this->assertSame('2026-08-14 11:20:00', $application->moodle_last_synced_at->format('Y-m-d H:i:s'));
    }

    public function test_mapper_creates_internal_participant_sync_payload(): void
    {
        $this->createApplicationSchema();

        $application = $this->createCourseApplication([
            'moodle_user_id' => 456,
            'participant_first_name' => 'Elek',
            'participant_last_name' => 'Teszt',
            'participant_email' => 'applicant@example.test',
        ]);

        $payload = (new MoodleParticipantPayloadMapper())->map($application->fresh());

        $this->assertSame($application->id, $payload['local_course_application_id']);
        $this->assertSame($application->actual_course_id, $payload['actual_course_id']);
        $this->assertSame(456, $payload['moodle_user_id']);
        $this->assertSame('Elek', $payload['participant_first_name']);
        $this->assertSame('Teszt', $payload['participant_last_name']);
        $this->assertSame('applicant@example.test', $payload['participant_email']);
    }

    public function test_mapper_preserves_nullable_moodle_user_id(): void
    {
        $this->createApplicationSchema();

        $payload = (new MoodleParticipantPayloadMapper())->map($this->createCourseApplication([
            'moodle_user_id' => null,
        ])->fresh());

        $this->assertNull($payload['moodle_user_id']);
    }

    public function test_mapper_excludes_unrelated_personal_and_billing_data(): void
    {
        $this->createApplicationSchema();

        $payload = (new MoodleParticipantPayloadMapper())->map($this->createCourseApplication()->fresh());

        foreach ([
            'payer_name',
            'payer_email',
            'payer_address',
            'payer_mailing_address',
            'payer_signatory',
            'payer_tax_number',
            'participant_phone',
            'participant_address',
            'participant_notification_address',
            'participant_birth_name',
            'participant_birth_place',
            'participant_birth_country',
            'participant_birth_date',
            'participant_mother_name',
            'participant_education',
            'participant_education_id',
            'participant_supported',
            'participant_grant_id',
            'newsletter_opt_in',
            'privacy_accepted',
            'status',
        ] as $excludedKey) {
            $this->assertArrayNotHasKey($excludedKey, $payload);
        }

        $this->assertArrayNotHasKey('username', $payload);
    }

    private function createApplicationSchema(bool $withMoodleFields = true): void
    {
        Schema::dropIfExists('course_applications');
        Schema::dropIfExists('actual_courses');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');

        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_category_id');
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('way_of_participation');
            $table->smallInteger('max_participants')->default(15);
            $table->timestamps();
        });

        Schema::create('course_applications', function (Blueprint $table) use ($withMoodleFields) {
            $table->id();

            if ($withMoodleFields) {
                $table->unsignedBigInteger('moodle_user_id')->nullable();
                $table->string('moodle_sync_status')->nullable();
                $table->timestamp('moodle_last_synced_at')->nullable();
                $table->text('moodle_sync_error')->nullable();
            }

            $table->foreignId('actual_course_id');
            $table->string('certificate_language')->nullable();
            $table->string('payer_name');
            $table->string('payer_address');
            $table->string('payer_mailing_address');
            $table->string('payer_email')->default('');
            $table->string('payer_signatory');
            $table->string('payer_tax_number');
            $table->string('participant_last_name');
            $table->string('participant_first_name');
            $table->string('participant_birth_name');
            $table->string('participant_birth_place');
            $table->string('participant_birth_country');
            $table->string('participant_birth_date');
            $table->string('participant_address');
            $table->string('participant_notification_address');
            $table->string('participant_phone');
            $table->string('participant_email');
            $table->string('participant_mother_name');
            $table->string('participant_education')->nullable();
            $table->string('participant_education_id')->nullable();
            $table->enum('participant_supported', ['igen', 'nem'])->nullable();
            $table->string('participant_grant_id')->nullable();
            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->enum('status', ['NEW', 'PROCESSED', 'SENT', 'CANCELLED'])->default('NEW');
            $table->string('robot_field')->nullable();
            $table->timestamps();
        });
    }

    private function createCourseApplication(array $attributes = []): CourseApplication
    {
        $actualCourse = $this->createActualCourse();

        return CourseApplication::query()->create(array_merge([
            'actual_course_id' => $actualCourse->id,
            'certificate_language' => 'Angol',
            'payer_name' => 'Pelda Kft.',
            'payer_address' => '1051 Budapest, Pelda utca 1.',
            'payer_mailing_address' => '1051 Budapest, Postafiok 1.',
            'payer_email' => 'payer@example.test',
            'payer_signatory' => 'Kepviselo Karoly',
            'payer_tax_number' => '12345678-1-42',
            'participant_last_name' => 'Teszt',
            'participant_first_name' => 'Elek',
            'participant_birth_name' => 'Teszt Elek',
            'participant_birth_place' => 'Budapest',
            'participant_birth_country' => 'Magyarorszag',
            'participant_birth_date' => '1990-01-01',
            'participant_address' => '1051 Budapest, Resztvevo utca 2.',
            'participant_notification_address' => '1051 Budapest, Ertesites utca 3.',
            'participant_phone' => '+36 30 123 4567',
            'participant_email' => 'applicant@example.test',
            'participant_mother_name' => 'Minta Maria',
            'participant_education' => 'Felsofoku vegzettseg',
            'participant_education_id' => 'AB-123456',
            'participant_supported' => 'igen',
            'participant_grant_id' => 'P-2026-001',
            'newsletter_opt_in' => true,
            'privacy_accepted' => true,
            'status' => 'NEW',
            'robot_field' => null,
        ], $attributes));
    }

    private function createActualCourse(): ActualCourse
    {
        $category = CourseCategory::query()->create(['name' => 'Innovaciomenedzsment']);

        $course = Course::query()->create([
            'course_category_id' => $category->id,
            'name' => 'Innovaciomenedzsment kepzes',
            'price' => 120000,
            'is_active' => true,
        ]);

        return ActualCourse::query()->create([
            'course_id' => $course->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'way_of_participation' => 'group',
            'max_participants' => 15,
        ]);
    }
}
