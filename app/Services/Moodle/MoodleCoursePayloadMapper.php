<?php

namespace App\Services\Moodle;

use App\Models\ActualCourse;
use Carbon\Carbon;

class MoodleCoursePayloadMapper
{
    public function map(ActualCourse $actualCourse): array
    {
        $actualCourse->loadMissing('course.courseCategory');

        $course = $actualCourse->course;
        $category = $course?->courseCategory;
        $days = $actualCourse->relationLoaded('days')
            ? $actualCourse->days
            : $actualCourse->days()->orderBy('day')->get();

        return [
            'local_actual_course_id' => $actualCourse->id,
            'user_given_id' => $actualCourse->user_given_id,
            'course_name' => $course?->name,
            'classification' => $actualCourse->classification,
            'category' => [
                'id' => $category?->id,
                'name' => $category?->name,
            ],
            'place_of_event' => $actualCourse->place_of_event,
            'start_date' => $this->formatDate($actualCourse->start_date),
            'end_date' => $this->formatDate($actualCourse->end_date),
            'way_of_participation' => $actualCourse->way_of_participation,
            'teaching_days' => $days
                ->sortBy('day')
                ->map(fn ($day) => [
                    'local_actual_course_day_id' => $day->id,
                    'day' => $this->formatDate($day->day),
                    'start_time' => $this->formatTime($day->start_time),
                    'end_time' => $this->formatTime($day->end_time),
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function formatTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('H:i');
    }
}
