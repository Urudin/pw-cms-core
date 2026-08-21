<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActualCourse extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'moodle_course_id' => 'integer',
        'moodle_last_synced_at' => 'datetime',
    ];

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
