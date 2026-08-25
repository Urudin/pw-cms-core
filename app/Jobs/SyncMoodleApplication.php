<?php

namespace App\Jobs;

use App\Models\CourseApplication;
use App\Services\Moodle\ParticipantSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncMoodleApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $courseApplicationId) {}

    public function handle(ParticipantSyncService $participantSyncService): void
    {
        $application = CourseApplication::query()
            ->with('actualCourse.course')
            ->find($this->courseApplicationId);

        if ($application === null) {
            return;
        }

        $participantSyncService->sync($application);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('moodle-application:'.$this->courseApplicationId))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }
}
