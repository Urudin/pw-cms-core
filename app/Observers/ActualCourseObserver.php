<?php

namespace App\Observers;

use App\Models\ActualCourse;
use App\Services\Moodle\CourseSyncDispatcher;

class ActualCourseObserver
{
    public function __construct(private readonly CourseSyncDispatcher $dispatcher) {}

    private const SYNCHRONIZED_FIELDS = [
        'course_id',
        'start_date',
        'end_date',
    ];

    public function created(ActualCourse $actualCourse): void
    {
        $this->dispatchWhenEnabled($actualCourse);
    }

    public function updated(ActualCourse $actualCourse): void
    {
        if ($actualCourse->wasChanged(self::SYNCHRONIZED_FIELDS)) {
            $this->dispatchWhenEnabled($actualCourse);
        }
    }

    private function dispatchWhenEnabled(ActualCourse $actualCourse): void
    {
        if (! config('moodle.enabled')) {
            return;
        }

        $this->dispatcher->dispatch($actualCourse);
    }
}
