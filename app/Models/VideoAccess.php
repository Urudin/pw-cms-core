<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAccess extends Model
{
    protected $table = 'video_accesses';

    protected $guarded = ['id'];

    protected $appends = ['username'];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function getUsernameAttribute(): string
    {
        return explode('@', $this->email)[0];

    }
}
