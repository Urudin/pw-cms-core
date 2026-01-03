<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTile extends Model
{
    protected $guarded = ['id'];

    public function scopeGrouped($query)
    {
        return $query->orderBy('group')->orderBy('order');
    }
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function tiles()
    {
        return $this->hasMany(PageTileItem::class)->orderBy('order');
    }

    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class);
    }

    // Egyéni accessor a CSS class lista formázásához
    public function getFormattedClassListAttribute(): string
    {
        return $this->group_classes ? implode(' ', explode(',', $this->group_classes)) : '';
    }
}
