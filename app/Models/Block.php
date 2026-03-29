<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithActualCourseInfo;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use InteractsWithActualCourseInfo;

    protected $guarded = ['id'];

    public function getRenderedContentAttribute(): string
    {
        return $this->replaceActualCourseInfoShortcodes($this->content ?? '');
    }

    protected function replaceActualCourseInfoShortcodes(string $html): string
    {
        preg_match_all('/\[actual_course_info\s+\{?(\d+)\}?\]/', $html, $matches);

        $courseIds = collect($matches[1] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return $html;
        }

        $courses = Course::query()
            ->with($this->actualCourseInfoRelations())
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

                return $this->renderActualCourseInfoHtml($course);
            },
            $html
        );
    }
}
