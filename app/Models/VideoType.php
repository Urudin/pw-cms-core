<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoType extends Model
{
    protected $guarded = ['id'];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
