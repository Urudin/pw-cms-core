<?php

namespace App\Services\Moodle;

use App\Jobs\SyncMoodleCourse;
use App\Models\ActualCourse;

class CourseSyncDispatcher
{
    public function dispatch(ActualCourse $actualCourse): void
    {
        $actualCourse->forceFill([
            'moodle_sync_status' => CourseSyncService::STATUS_PENDING,
        ])->saveQuietly();

        SyncMoodleCourse::dispatch($actualCourse->getKey())->afterCommit();
    }
}
