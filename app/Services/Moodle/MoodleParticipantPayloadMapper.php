<?php

namespace App\Services\Moodle;

use App\Models\CourseApplication;

class MoodleParticipantPayloadMapper
{
    public function map(CourseApplication $application): array
    {
        return [
            'local_course_application_id' => $application->id,
            'actual_course_id' => $application->actual_course_id,
            'moodle_user_id' => $application->moodle_user_id,
            'participant_first_name' => $application->participant_first_name,
            'participant_last_name' => $application->participant_last_name,
            'participant_email' => $application->participant_email,
        ];
    }
}
