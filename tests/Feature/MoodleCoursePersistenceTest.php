<?php

namespace Tests\Feature;

use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\Moodle\MoodleCoursePayloadMapper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodleCoursePersistenceTest extends TestCase
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

    public function test_migration_adds_nullable_moodle_sync_fields_to_actual_courses(): void
    {
        $this->createCourseSchema(withMoodleFields: false);

        $migration = require base_path('database/migrations/2026_08_14_000000_add_moodle_sync_fields_to_actual_courses_table.php');
        $migration->up();

        foreach (['moodle_course_id', 'moodle_sync_status', 'moodle_last_synced_at', 'moodle_sync_error'] as $column) {
            $this->assertTrue(Schema::hasColumn('actual_courses', $column), "Missing column: {$column}");
        }

        $course = $this->createCourse();

        $actualCourseId = DB::table('actual_courses')->insertGetId([
            'course_id' => $course->id,
            'classification' => null,
            'user_given_id' => null,
            'place_of_event' => null,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'application_deadline' => null,
            'way_of_participation' => 'group',
            'price' => null,
            'min_participants' => 0,
            'max_participants' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('actual_courses')->find($actualCourseId);

        $this->assertNull($row->moodle_course_id);
        $this->assertNull($row->moodle_sync_status);
        $this->assertNull($row->moodle_last_synced_at);
        $this->assertNull($row->moodle_sync_error);
    }

    public function test_actual_course_casts_moodle_sync_fields(): void
    {
        $this->createCourseSchema();

        $actualCourse = $this->createActualCourse([
            'moodle_course_id' => '987',
            'moodle_sync_status' => 'synced',
            'moodle_last_synced_at' => '2026-08-14 10:15:00',
            'moodle_sync_error' => null,
        ])->fresh();

        $this->assertSame(987, $actualCourse->moodle_course_id);
        $this->assertInstanceOf(Carbon::class, $actualCourse->moodle_last_synced_at);
        $this->assertSame('2026-08-14 10:15:00', $actualCourse->moodle_last_synced_at->format('Y-m-d H:i:s'));
    }

    public function test_mapper_creates_internal_course_sync_payload(): void
    {
        $this->createCourseSchema();

        $actualCourse = $this->createActualCourse();
        $actualCourse->days()->create([
            'day' => '2026-09-03',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
        ]);
        $actualCourse->days()->create([
            'day' => '2026-09-01',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
        ]);

        $payload = (new MoodleCoursePayloadMapper())->map($actualCourse->fresh());

        $this->assertSame($actualCourse->id, $payload['local_actual_course_id']);
        $this->assertSame(12345, $payload['user_given_id']);
        $this->assertSame('Innovaciomenedzsment kepzes', $payload['course_name']);
        $this->assertSame('Minositett oktatas', $payload['classification']);
        $this->assertSame('Innovaciomenedzsment', $payload['category']['name']);
        $this->assertSame('1051 Budapest, Arany Janos u. 15.', $payload['place_of_event']);
        $this->assertSame('2026-09-01', $payload['start_date']);
        $this->assertSame('2026-09-03', $payload['end_date']);
        $this->assertSame('group', $payload['way_of_participation']);
        $this->assertSame('2026-09-01', $payload['teaching_days'][0]['day']);
        $this->assertSame('09:00', $payload['teaching_days'][0]['start_time']);
        $this->assertSame('13:00', $payload['teaching_days'][0]['end_time']);
        $this->assertSame('2026-09-03', $payload['teaching_days'][1]['day']);
        $this->assertSame('10:00', $payload['teaching_days'][1]['start_time']);
        $this->assertSame('14:00', $payload['teaching_days'][1]['end_time']);
    }

    public function test_mapper_preserves_nullable_optional_values(): void
    {
        $this->createCourseSchema();

        $actualCourse = $this->createActualCourse([
            'classification' => null,
            'user_given_id' => null,
            'place_of_event' => null,
        ]);
        $actualCourse->days()->create([
            'day' => '2026-09-01',
            'start_time' => null,
            'end_time' => null,
        ]);

        $payload = (new MoodleCoursePayloadMapper())->map($actualCourse->fresh());

        $this->assertNull($payload['classification']);
        $this->assertNull($payload['user_given_id']);
        $this->assertNull($payload['place_of_event']);
        $this->assertNull($payload['teaching_days'][0]['start_time']);
        $this->assertNull($payload['teaching_days'][0]['end_time']);
    }

    public function test_mapper_does_not_assume_target_moodle_custom_fields(): void
    {
        $this->createCourseSchema();

        $payload = (new MoodleCoursePayloadMapper())->map($this->createActualCourse()->fresh());

        $this->assertArrayNotHasKey('customfields', $payload);
        $this->assertArrayNotHasKey('categoryid', $payload);
        $this->assertArrayNotHasKey('fullname', $payload);
        $this->assertArrayNotHasKey('shortname', $payload);
    }

    private function createCourseSchema(bool $withMoodleFields = true): void
    {
        Schema::dropIfExists('actual_course_days');
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
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->boolean('listed')->default(true);
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        Schema::create('actual_courses', function (Blueprint $table) use ($withMoodleFields) {
            $table->id();

            if ($withMoodleFields) {
                $table->unsignedBigInteger('moodle_course_id')->nullable();
                $table->string('moodle_sync_status')->nullable();
                $table->timestamp('moodle_last_synced_at')->nullable();
                $table->text('moodle_sync_error')->nullable();
            }

            $table->foreignId('course_id');
            $table->string('classification')->nullable();
            $table->unsignedBigInteger('user_given_id')->nullable();
            $table->string('place_of_event')->nullable();
            $table->date('start_date');
            $table->date('end_date');
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

    private function createActualCourse(array $attributes = []): ActualCourse
    {
        $course = $this->createCourse();

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
