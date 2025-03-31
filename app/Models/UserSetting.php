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

    public static function getValueByName(string $name): ?string
    {
        return self::query()->where('name', $name)->first()?->value;
    }

    public static function getHeaderUrl(): string
    {
        $mediaRecord = Media::query()
            ->where('collection_name', 'headers')
            ->where('file_name', self::query()->firstWhere('name', 'header-background')->value)
            ->first();
        // Check if media record exists, otherwise use a fallback image
        return $mediaRecord ? asset('storage/' . $mediaRecord->model_id . '/' . $mediaRecord->file_name) : asset('images/default-header.jpg');
    }
}
