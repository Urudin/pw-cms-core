<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActualCourse extends Model
{
    protected $guarded = ['id'];

    public function course() : BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(ActualCourseDay::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CourseApplication::class);
    }
}
