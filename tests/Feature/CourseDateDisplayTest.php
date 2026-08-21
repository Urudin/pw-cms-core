<?php

namespace Tests\Feature;

use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseDateDisplayTest extends TestCase
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

        Carbon::setTestNow('2026-06-01 10:00:00');

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_base_course_with_own_upcoming_date_shows_base_course_date(): void
    {
        $course = $this->createCourse();
        $this->createActualCourse($course, '2026-06-10', ['2026-06-10']);

        $onlineCourse = $this->createCourse(['course_id' => $course->id, 'listed' => false]);
        $this->createActualCourse($onlineCourse, '2026-06-05', ['2026-06-05']);

        $course->load($this->courseDateRelations());

        $this->assertSame('2026.06.10.', $course->display_actual_course_date_text);
    }

    public function test_base_course_without_own_upcoming_date_uses_related_distance_learning_date(): void
    {
        $course = $this->createCourse();

        $onlineCourse = $this->createCourse(['course_id' => $course->id, 'listed' => false]);
        $this->createActualCourse($onlineCourse, '2026-06-05', ['2026-06-05']);

        $course->load($this->courseDateRelations());

        $this->assertSame('2026.06.05.', $course->display_actual_course_date_text);
    }

    public function test_base_course_without_any_date_and_without_distance_learning_fallback_shows_hamarosan(): void
    {
        $course = $this->createCourse();

        $course->load($this->courseDateRelations());

        $this->assertSame('Hamarosan', $course->display_actual_course_date_text);
    }

    public function test_online_education_days_come_from_distance_learning_course_when_fallback_date_is_used(): void
    {
        $course = $this->createCourse([
            'description' => '[actual_course_info]',
        ]);
        $staleBaseActualCourse = $this->createActualCourse($course, '2026-05-01', ['2026-05-02']);

        $onlineCourse = $this->createCourse(['course_id' => $course->id, 'listed' => false]);
        $this->createActualCourse($onlineCourse, '2026-06-05', ['2026-06-05', '2026-06-06']);

        $course->load($this->courseDateRelations());

        $renderedDescription = $course->renderedDescription;

        $this->assertStringContainsString('Aktuális tanfolyam időpontok:', $renderedDescription);
        $this->assertStringContainsString('2026.06.05.', $renderedDescription);
        $this->assertStringContainsString('Online Oktatási napok:', $renderedDescription);
        $this->assertStringContainsString('2026.06.05., 2026.06.06.', $renderedDescription);
        $this->assertStringNotContainsString('2026.05.02.', $renderedDescription);
    }

    private function createCourse(array $attributes = []): Course
    {
        $category = CourseCategory::query()->first()
            ?? CourseCategory::query()->create(['name' => 'Innovációmenedzsment']);

        $courseId = DB::table('courses')->insertGetId(array_merge([
            'course_category_id' => $category->id,
            'course_id' => null,
            'name' => 'Teszt tanfolyam',
            'price' => 120000,
            'is_active' => true,
            'listed' => true,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Course::query()->findOrFail($courseId);
    }

    private function createActualCourse(Course $course, string $startDate, array $days = []): ActualCourse
    {
        $actualCourseId = DB::table('actual_courses')->insertGetId([
            'course_id' => $course->id,
            'start_date' => $startDate,
            'end_date' => $startDate,
            'type' => 'group',
            'max_participants' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $actualCourse = ActualCourse::query()->findOrFail($actualCourseId);

        foreach ($days as $day) {
            $actualCourse->days()->create(['day' => $day]);
        }

        return $actualCourse;
    }

    private function courseDateRelations(): array
    {
        return [
            'actualCourses' => fn ($query) => $query->orderBy('start_date'),
            'actualCourses.days' => fn ($query) => $query->orderBy('day'),
            'onlineVersion.actualCourses' => fn ($query) => $query->orderBy('start_date'),
            'onlineVersion.actualCourses.days' => fn ($query) => $query->orderBy('day'),
        ];
    }

    private function createTestSchema(): void
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

        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['group', 'individual']);
            $table->smallInteger('max_participants')->default(15);
            $table->timestamps();
        });

        Schema::create('actual_course_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->date('day');
            $table->timestamps();
        });
    }
}
