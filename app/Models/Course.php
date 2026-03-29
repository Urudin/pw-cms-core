<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithActualCourseInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    use InteractsWithActualCourseInfo;

    protected $guarded = ['id'];

    public function actualCourses(): HasMany
    {
        return $this->hasMany(ActualCourse::class);
    }

    public function courseCategory(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function onlineVersion(): HasOne
    {
        return $this->hasOne(self::class, 'course_id');
    }

    public function getRenderedDescriptionAttribute(): string
    {
        $description = $this->description ?? '';

        if ($description === '' || ! str_contains($description, '[actual_course_info]')) {
            return $description;
        }

        $this->loadMissing($this->actualCourseInfoRelations());

        return str_replace(
            '[actual_course_info]',
            $this->renderActualCourseInfoHtml($this),
            $description
        );
    }
}
