<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Block extends Model
{
    protected $guarded = ['id'];
    public function getRenderedContentAttribute(): string
    {
        return $this->replaceActualCourseInfoShortcodes($this->content ?? '');
    }

    protected function replaceActualCourseInfoShortcodes(string $html): string
    {
        // Támogatott formák:
        // [actual_course_info 12]
        // [actual_course_info {12}]
        preg_match_all('/\[actual_course_info\s+\{?(\d+)\}?\]/', $html, $matches);

        $courseIds = collect($matches[1] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return $html;
        }

        $courses = Course::query()
            ->with([
                'actualCourses' => fn ($query) => $query->orderBy('start_date'),
                'actualCourses.days' => fn ($query) => $query->orderBy('day'),

                'onlineVersion.actualCourses' => fn ($query) => $query->orderBy('start_date'),
                'onlineVersion.actualCourses.days' => fn ($query) => $query->orderBy('day'),
            ])
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        return preg_replace_callback(
            '/\[actual_course_info\s+\{?(\d+)\}?\]/',
            function (array $match) use ($courses) {
                $courseId = (int) $match[1];
                $course = $courses->get($courseId);

                if (! $course) {
                    return '';
                }

                return $this->buildActualCourseInfoHtml($course);
            },
            $html
        );
    }

    protected function buildActualCourseInfoHtml(Course $course): string
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
                // Elsődleges: start_date mező az ActualCourse-ban
                if (! empty($actualCourse->start_date)) {
                    return Carbon::parse($actualCourse->start_date)->format('Y.m.d.');
                }

                // Fallback: ha nincs start_date, akkor az első oktatási nap
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
            ->flatMap(function ($actualCourse) {
                return $actualCourse->days->pluck('day');
            })
            ->filter()
            ->map(fn ($day) => Carbon::parse($day)->format('Y.m.d.'))
            ->unique()
            ->implode(', ');
    }
}
