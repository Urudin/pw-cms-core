<?php

namespace App\Mail;

use App\Models\CourseApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseApplicationAdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CourseApplication $application)
    {
    }

    public function build(): self
    {
        $this->application->loadMissing('actualCourse.course.courseCategory');

        return $this->subject('Új tanfolyami jelentkezés - Innovációmenedzsment Akadémia')
            ->view('mail.course-application-admin-notification')
            ->with([
                'application' => $this->application,
                'courseName' => $this->application->actualCourse?->course?->name,
                'courseCategory' => $this->application->actualCourse?->course?->courseCategory?->name,
                'participantName' => trim($this->application->participant_last_name . ' ' . $this->application->participant_first_name),
            ]);
    }
}
