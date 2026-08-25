<?php

namespace App\Services\Moodle;

use App\Jobs\SyncMoodleApplication;
use App\Models\CourseApplication;

class ParticipantSyncDispatcher
{
    public function dispatch(CourseApplication $application): bool
    {
        if (! config('moodle.enabled')) {
            return false;
        }

        $application->forceFill([
            'moodle_sync_status' => ParticipantSyncService::STATUS_PENDING,
        ])->saveQuietly();

        SyncMoodleApplication::dispatch($application->getKey())->afterCommit();

        return true;
    }
}
