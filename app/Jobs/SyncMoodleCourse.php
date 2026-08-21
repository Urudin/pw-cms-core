<?php

namespace App\Jobs;

use App\Models\ActualCourse;
use App\Services\Moodle\CourseSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncMoodleCourse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $actualCourseId) {}

    public function handle(CourseSyncService $courseSyncService): void
    {
        $actualCourse = ActualCourse::query()
            ->with(['course.courseCategory', 'days'])
            ->find($this->actualCourseId);

        if ($actualCourse === null) {
            return;
        }

        $configuredCategoryId = config('moodle.course_category_id');

        $courseSyncService->sync(
            $actualCourse,
            visible: (bool) config('moodle.course_visible', false),
            categoryId: is_numeric($configuredCategoryId) ? (int) $configuredCategoryId : null,
        );
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('moodle-course:'.$this->actualCourseId))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }
}
