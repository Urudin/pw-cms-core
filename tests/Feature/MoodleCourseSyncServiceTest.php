<?php

namespace Tests\Feature;

use App\Jobs\SyncMoodleCourse;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\Moodle\CourseSyncService;
use App\Services\Moodle\MoodleException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodleCourseSyncServiceTest extends TestCase
{
    private const BASE_URL = 'https://elearning.example.test';

    private const ENDPOINT = self::BASE_URL.'/webservice/rest/server.php';

    private const TOKEN = 'course-sync-secret-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'moodle.enabled' => true,
            'moodle.base_url' => self::BASE_URL,
            'moodle.web_service_token' => self::TOKEN,
            'moodle.rest_format' => 'json',
            'moodle.timeout' => 15,
            'moodle.course_namespace' => 'test',
            'moodle.course_category_id' => 42,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Queue::fake([SyncMoodleCourse::class]);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_course_is_looked_up_created_persisted_and_marked_synced(): void
    {
        Carbon::setTestNow('2026-08-21 12:00:00');
        $actualCourse = $this->createActualCourse();
        $functions = [];

        Http::fake(function (Request $request) use (&$functions, $actualCourse) {
            $functions[] = $request['wsfunction'];
            $this->assertSame(CourseSyncService::STATUS_PENDING, $actualCourse->fresh()->moodle_sync_status);

            return match ($request['wsfunction']) {
                'core_course_get_courses_by_field' => Http::response(['courses' => [], 'warnings' => []]),
                'core_course_create_courses' => Http::response([['id' => 901, 'shortname' => 'IMA-TEST-'.$actualCourse->id]]),
                default => Http::response(['message' => 'Unexpected function'], 500),
            };
        });

        $synced = $this->service()->sync($actualCourse, visible: false);

        $this->assertSame([
            'core_course_get_courses_by_field',
            'core_course_create_courses',
        ], $functions);
        $this->assertSame(901, $synced->moodle_course_id);
        $this->assertSame(CourseSyncService::STATUS_SYNCED, $synced->moodle_sync_status);
        $this->assertSame('2026-08-21 12:00:00', $synced->moodle_last_synced_at?->format('Y-m-d H:i:s'));
        $this->assertNull($synced->moodle_sync_error);

        Http::assertSent(fn (Request $request) => $request['wsfunction'] === 'core_course_get_courses_by_field'
            && $request['field'] === 'idnumber'
            && $request['value'] === 'imakademia-test-actual-course-'.$actualCourse->id);

        Http::assertSent(function (Request $request) use ($actualCourse) {
            if ($request['wsfunction'] !== 'core_course_create_courses') {
                return false;
            }

            $course = $request['courses'][0];

            return $course['fullname'] === 'Innovaciomenedzsment kepzes – 2026-09-01'
                && $course['shortname'] === 'IMA-TEST-'.$actualCourse->id
                && $course['idnumber'] === 'imakademia-test-actual-course-'.$actualCourse->id
                && $course['categoryid'] === 42
                && $course['startdate'] === Carbon::parse('2026-09-01')->startOfDay()->timestamp
                && $course['enddate'] === Carbon::parse('2026-09-03')->startOfDay()->timestamp
                && $course['visible'] === 0
                && ! array_key_exists('classification', $course)
                && ! array_key_exists('place_of_event', $course)
                && ! array_key_exists('way_of_participation', $course)
                && ! array_key_exists('teaching_days', $course)
                && ! array_key_exists('customfields', $course);
        });
    }

    public function test_retry_without_local_mapping_reuses_matching_course_and_updates_it(): void
    {
        $actualCourse = $this->createActualCourse();

        Http::fake(function (Request $request) {
            return match ($request['wsfunction']) {
                'core_course_get_courses_by_field' => Http::response([
                    'courses' => [['id' => 321, 'idnumber' => $request['value']]],
                    'warnings' => [],
                ]),
                'core_course_update_courses' => Http::response([]),
                default => Http::response(['message' => 'Unexpected function'], 500),
            };
        });

        $synced = $this->service()->sync($actualCourse, visible: true, categoryId: 5);

        $this->assertSame(321, $synced->moodle_course_id);
        $this->assertSame(CourseSyncService::STATUS_SYNCED, $synced->moodle_sync_status);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request) => $request['wsfunction'] === 'core_course_create_courses');
        Http::assertSent(fn (Request $request) => $request['wsfunction'] === 'core_course_update_courses'
            && $request['courses'][0]['id'] === 321
            && $request['courses'][0]['categoryid'] === 5
            && $request['courses'][0]['visible'] === 1);
    }

    public function test_existing_mapped_course_is_updated_without_lookup_or_create(): void
    {
        $actualCourse = $this->createActualCourse(['moodle_course_id' => 654]);

        Http::fake([self::ENDPOINT => Http::response([])]);

        $synced = $this->service()->sync($actualCourse, visible: true);

        $this->assertSame(654, $synced->moodle_course_id);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['wsfunction'] === 'core_course_update_courses'
            && $request['courses'][0]['id'] === 654);
        Http::assertNotSent(fn (Request $request) => in_array($request['wsfunction'], [
            'core_course_get_courses_by_field',
            'core_course_create_courses',
        ], true));
    }

    public function test_moodle_api_failure_marks_failed_and_persists_redacted_error(): void
    {
        $actualCourse = $this->createActualCourse();

        Http::fake([self::ENDPOINT => Http::response([
            'exception' => 'moodle_exception',
            'errorcode' => 'invalidparameter',
            'message' => 'Rejected token '.self::TOKEN,
        ])]);

        try {
            $this->service()->sync($actualCourse, visible: false);
            $this->fail('Expected MoodleException was not thrown.');
        } catch (MoodleException $exception) {
            $this->assertStringNotContainsString(self::TOKEN, $exception->getMessage());
        }

        $failed = $actualCourse->fresh();
        $this->assertSame(CourseSyncService::STATUS_FAILED, $failed->moodle_sync_status);
        $this->assertStringContainsString('Rejected token [redacted]', $failed->moodle_sync_error);
        $this->assertStringNotContainsString(self::TOKEN, $failed->moodle_sync_error);
        $this->assertNull($failed->moodle_last_synced_at);
    }

    public function test_successful_retry_clears_previous_error_and_marks_synced(): void
    {
        $actualCourse = $this->createActualCourse([
            'moodle_course_id' => 777,
            'moodle_sync_status' => CourseSyncService::STATUS_FAILED,
            'moodle_sync_error' => 'Previous failure',
        ]);

        Http::fake([self::ENDPOINT => Http::response([])]);

        $synced = $this->service()->sync($actualCourse, visible: false);

        $this->assertSame(CourseSyncService::STATUS_SYNCED, $synced->moodle_sync_status);
        $this->assertNull($synced->moodle_sync_error);
        $this->assertNotNull($synced->moodle_last_synced_at);
    }

    public function test_optional_end_date_is_omitted(): void
    {
        $actualCourse = $this->createActualCourse([
            'moodle_course_id' => 888,
            'end_date' => null,
        ]);

        Http::fake([self::ENDPOINT => Http::response([])]);

        $this->service()->sync($actualCourse, visible: true);

        Http::assertSent(fn (Request $request) => $request['wsfunction'] === 'core_course_update_courses'
            && ! array_key_exists('enddate', $request['courses'][0]));
    }

    public function test_ambiguous_lookup_fails_without_creating_course(): void
    {
        $actualCourse = $this->createActualCourse();

        Http::fake([self::ENDPOINT => Http::response([
            'courses' => [['id' => 1], ['id' => 2]],
            'warnings' => [],
        ])]);

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($actualCourse, visible: false);
        } finally {
            $this->assertSame(CourseSyncService::STATUS_FAILED, $actualCourse->fresh()->moodle_sync_status);
            Http::assertSentCount(1);
            Http::assertNotSent(fn (Request $request) => $request['wsfunction'] === 'core_course_create_courses');
        }
    }

    public function test_malformed_lookup_response_fails_without_creating_course(): void
    {
        $actualCourse = $this->createActualCourse();

        Http::fake([self::ENDPOINT => Http::response(['warnings' => []])]);

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($actualCourse, visible: false);
        } finally {
            $this->assertSame(CourseSyncService::STATUS_FAILED, $actualCourse->fresh()->moodle_sync_status);
            Http::assertSentCount(1);
            Http::assertNotSent(fn (Request $request) => $request['wsfunction'] === 'core_course_create_courses');
        }
    }

    public function test_missing_category_configuration_fails_before_http_request(): void
    {
        config(['moodle.course_category_id' => null]);
        $actualCourse = $this->createActualCourse();
        Http::fake();

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($actualCourse, visible: false);
        } finally {
            $this->assertSame(CourseSyncService::STATUS_FAILED, $actualCourse->fresh()->moodle_sync_status);
            Http::assertSentCount(0);
        }
    }

    public function test_missing_namespace_configuration_fails_before_http_request(): void
    {
        config(['moodle.course_namespace' => null]);
        $actualCourse = $this->createActualCourse();
        Http::fake();

        $this->expectException(MoodleException::class);

        try {
            $this->service()->sync($actualCourse, visible: false);
        } finally {
            $this->assertSame(CourseSyncService::STATUS_FAILED, $actualCourse->fresh()->moodle_sync_status);
            Http::assertSentCount(0);
        }
    }

    public function test_different_normalized_namespaces_generate_different_identifiers_for_same_course(): void
    {
        $actualCourse = $this->createActualCourse(['moodle_course_id' => 654]);
        $payloads = [];

        Http::fake(function (Request $request) use (&$payloads) {
            $payloads[] = $request['courses'][0];

            return Http::response([]);
        });

        config(['moodle.course_namespace' => 'Development Environment']);
        $this->service()->sync($actualCourse, visible: false);

        config(['moodle.course_namespace' => 'test']);
        $this->service()->sync($actualCourse->fresh(), visible: false);

        $this->assertSame('IMA-DEVELOPMENT-ENVIRONMENT-'.$actualCourse->id, $payloads[0]['shortname']);
        $this->assertSame('imakademia-development-environment-actual-course-'.$actualCourse->id, $payloads[0]['idnumber']);
        $this->assertSame('IMA-TEST-'.$actualCourse->id, $payloads[1]['shortname']);
        $this->assertSame('imakademia-test-actual-course-'.$actualCourse->id, $payloads[1]['idnumber']);
        $this->assertNotSame($payloads[0]['shortname'], $payloads[1]['shortname']);
        $this->assertNotSame($payloads[0]['idnumber'], $payloads[1]['idnumber']);
    }

    private function service(): CourseSyncService
    {
        return app(CourseSyncService::class);
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

    private function createActualCourse(array $attributes = []): ActualCourse
    {
        $category = CourseCategory::query()->create(['name' => 'Innovaciomenedzsment']);
        $course = Course::query()->create([
            'course_category_id' => $category->id,
            'course_id' => null,
            'name' => 'Innovaciomenedzsment kepzes',
            'price' => 120000,
            'is_active' => true,
            'listed' => true,
            'description' => null,
        ]);

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
