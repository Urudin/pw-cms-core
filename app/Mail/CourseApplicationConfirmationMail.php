<?php

namespace App\Mail;

use App\Models\CourseApplication;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseApplicationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CourseApplication $application)
    {
    }

    public function build(): self
    {
        $this->application->loadMissing('actualCourse.course.courseCategory');

        return $this->subject('Jelentkezés visszaigazolása - Innovációmenedzsment Akadémia')
            ->view('mail.course-application-confirmation')
            ->with([
                'application' => $this->application,
                'details' => $this->details(),
            ]);
    }

    private function details(): array
    {
        $actualCourse = $this->application->actualCourse;
        $course = $actualCourse?->course;

        return [
            'applicant_name' => trim($this->application->participant_last_name . ' ' . $this->application->participant_first_name),
            'course_name' => $course?->name,
            'course_start_date' => $actualCourse?->start_date
                ? Carbon::parse($actualCourse->start_date)->format('Y.m.d.')
                : null,
            'course_type' => $course?->courseCategory?->name,
            'course_location' => $actualCourse?->place_of_event,
            'certificate_language' => $this->application->certificate_language,
            'course_fee' => $this->formatHuf($actualCourse?->price ?? $course?->price),
        ];
    }

    private function formatHuf(null|int|float|string $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return number_format((int) $amount, 0, ',', ' ') . ' Ft';
    }
}
