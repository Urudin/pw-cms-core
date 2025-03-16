<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    //
    protected $fillable = ['name', 'value'];

    public $timestamps = true;

    // Prevent accidental deletion
    public static function boot(): void
    {
        parent::boot();
        static::deleting(function ($model) {
            return false;
        });
    }
}
