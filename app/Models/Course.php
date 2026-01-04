<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
