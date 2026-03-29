<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
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
}
