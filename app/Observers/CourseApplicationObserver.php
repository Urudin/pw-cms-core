<?php

namespace App\Observers;

use App\Models\CourseApplication;
use App\Services\Moodle\ParticipantSyncDispatcher;

class CourseApplicationObserver
{
    private const PARTICIPANT_FIELDS = [
        'participant_first_name',
        'participant_last_name',
        'participant_email',
    ];

    public function __construct(private readonly ParticipantSyncDispatcher $dispatcher) {}

    public function created(CourseApplication $application): void
    {
        if ($application->status === 'PROCESSED') {
            $this->dispatcher->dispatch($application);
        }
    }

    public function updated(CourseApplication $application): void
    {
        if ($application->wasChanged('status')) {
            if ($application->status === 'PROCESSED') {
                $this->dispatcher->dispatch($application);

                return;
            }

            if ($application->status === 'CANCELLED' && $this->participatesInMoodle($application)) {
                $this->dispatcher->dispatch($application);

                return;
            }
        }

        if ($application->wasChanged(self::PARTICIPANT_FIELDS) && $this->participatesInMoodle($application)) {
            $this->dispatcher->dispatch($application);
        }
    }

    private function participatesInMoodle(CourseApplication $application): bool
    {
        return $application->moodle_user_id !== null || $application->moodle_sync_status !== null;
    }
}
