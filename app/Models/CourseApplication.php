<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseApplication extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'newsletter_opt_in' => 'boolean',
        'privacy_accepted' => 'boolean',
    ];

    public function actualCourse(): BelongsTo
    {
        return $this->belongsTo(ActualCourse::class);
    }
}
