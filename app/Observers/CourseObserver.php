<?php

namespace App\Observers;

use App\Models\ActualCourse;
use App\Models\Course;
use App\Services\Moodle\CourseSyncDispatcher;

class CourseObserver
{
    public function __construct(private readonly CourseSyncDispatcher $dispatcher) {}

    public function updated(Course $course): void
    {
        if (! config('moodle.enabled') || ! $course->wasChanged('name')) {
            return;
        }

        $course->actualCourses()
            ->where(function ($query) {
                $query->whereNotNull('moodle_course_id')
                    ->orWhereNotNull('moodle_sync_status');
            })
            ->select('actual_courses.id')
            ->eachById(function (ActualCourse $actualCourse) {
                $this->dispatcher->dispatch($actualCourse);
            });
    }
}
