<?php

namespace App\Models\Concerns;

use App\Models\ActualCourse;
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
        $displayActualCourse = $this->resolveDisplayActualCourse($course);
        $displayActualCourses = $displayActualCourse ? collect([$displayActualCourse]) : collect();
        $onlineActualCourse = $this->resolveNearestUpcomingActualCourse($course->onlineVersion?->actualCourses ?? collect());
        $onlineActualCourses = $onlineActualCourse ? collect([$onlineActualCourse]) : collect();

        $items = [
            [
                'Aktuális tanfolyam időpontok:',
                $this->formatActualCourseDates($displayActualCourses) ?: 'Hamarosan',
            ],
            [
                'Oktatási napok:',
                $this->formatTeachingDays($displayActualCourses),
            ],
            [
                'Aktuális online tanfolyam időpontok:',
                $this->formatActualCourseDates($onlineActualCourses),
            ],
            [
                'Online Oktatási napok:',
                $this->formatTeachingDays($onlineActualCourses),
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

    public function displayActualCourseDateText(Course $course, string $fallback = 'Hamarosan'): string
    {
        $actualCourse = $this->resolveDisplayActualCourse($course);

        return $actualCourse
            ? $this->formatActualCourseDates(collect([$actualCourse]))
            : $fallback;
    }

    public function displayActualCourseTeachingDaysText(Course $course): string
    {
        $actualCourse = $this->resolveDisplayActualCourse($course);

        return $actualCourse
            ? $this->formatTeachingDays(collect([$actualCourse]))
            : '';
    }

    public function resolveDisplayActualCourse(Course $course): ?ActualCourse
    {
        return $this->resolveNearestUpcomingActualCourse($course->actualCourses ?? collect())
            ?? $this->resolveNearestUpcomingActualCourse($course->onlineVersion?->actualCourses ?? collect());
    }

    protected function resolveNearestUpcomingActualCourse(Collection $actualCourses): ?ActualCourse
    {
        $today = now()->startOfDay();

        return $actualCourses
            ->filter(function ($actualCourse) use ($today) {
                $candidateDate = $this->actualCourseDisplayDate($actualCourse);

                if (! $candidateDate) {
                    return false;
                }

                $endDate = filled($actualCourse->end_date)
                    ? Carbon::parse($actualCourse->end_date)->startOfDay()
                    : null;

                return $candidateDate->greaterThanOrEqualTo($today)
                    || $endDate?->greaterThanOrEqualTo($today);
            })
            ->sortBy(fn ($actualCourse) => $this->actualCourseDisplayDate($actualCourse)?->timestamp ?? PHP_INT_MAX)
            ->first();
    }

    protected function formatActualCourseDates(Collection $actualCourses): string
    {
        return $actualCourses
            ->map(fn ($actualCourse) => $this->actualCourseDisplayDate($actualCourse)?->format('Y.m.d.'))
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

    protected function actualCourseDisplayDate(ActualCourse $actualCourse): ?Carbon
    {
        if (! empty($actualCourse->start_date)) {
            return Carbon::parse($actualCourse->start_date)->startOfDay();
        }

        $firstDay = $actualCourse->days
            ->pluck('day')
            ->filter()
            ->sort()
            ->first();

        return $firstDay
            ? Carbon::parse($firstDay)->startOfDay()
            : null;
    }
}
