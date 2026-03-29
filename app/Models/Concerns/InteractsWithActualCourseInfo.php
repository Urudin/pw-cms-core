<?php

namespace App\Models\Concerns;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait InteractsWithActualCourseInfo
{
    protected function actualCourseInfoRelations(): array
    {
        return [
            'actualCourses' => fn ($query) => $query->orderBy('start_date'),
            'actualCourses.days' => fn ($query) => $query->orderBy('day'),

            'onlineVersion.actualCourses' => fn ($query) => $query->orderBy('start_date'),
            'onlineVersion.actualCourses.days' => fn ($query) => $query->orderBy('day'),
        ];
    }

    protected function renderActualCourseInfoHtml(Course $course): string
    {
        $onlineCourse = $course->onlineVersion;

        $items = [
            [
                'Aktuális tanfolyam időpontok:',
                $this->formatActualCourseDates($course->actualCourses),
            ],
            [
                'Oktatási napok:',
                $this->formatTeachingDays($course->actualCourses),
            ],
            [
                'Aktuális online tanfolyam időpontok:',
                $this->formatActualCourseDates($onlineCourse?->actualCourses ?? collect()),
            ],
            [
                'Online Oktatási napok:',
                $this->formatTeachingDays($onlineCourse?->actualCourses ?? collect()),
            ],
        ];

        return collect($items)
            ->map(function (array $item) {
                [$label, $value] = $item;

                $label = e($label);
                $value = e($value ?: '-');

                return <<<HTML
                <li>
                    <p class="text-sm sm:text-base leading-relaxed text-slate-700">
                        <span class="font-bold text-slate-900">{$label}</span> {$value}
                    </p>
                </li>
                HTML;
            })
            ->implode(PHP_EOL);
    }

    protected function formatActualCourseDates(Collection $actualCourses): string
    {
        return $actualCourses
            ->map(function ($actualCourse) {
                if (! empty($actualCourse->start_date)) {
                    return Carbon::parse($actualCourse->start_date)->format('Y.m.d.');
                }

                $firstDay = $actualCourse->days
                    ->pluck('day')
                    ->filter()
                    ->sort()
                    ->first();

                return $firstDay
                    ? Carbon::parse($firstDay)->format('Y.m.d.')
                    : null;
            })
            ->filter()
            ->unique()
            ->implode(', ');
    }

    protected function formatTeachingDays(Collection $actualCourses): string
    {
        return $actualCourses
            ->flatMap(fn ($actualCourse) => $actualCourse->days->pluck('day'))
            ->filter()
            ->map(fn ($day) => Carbon::parse($day)->format('Y.m.d.'))
            ->unique()
            ->implode(', ');
    }
}
