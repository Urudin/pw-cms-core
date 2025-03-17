<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlock extends Model
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

    public function blocks()
    {
        return $this->hasMany(PageBlockItem::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    // Egyéni accessor a CSS class lista formázásához
    public function getFormattedClassListAttribute(): string
    {
        return $this->group_classes ? implode(' ', explode(',', $this->group_classes)) : '';
    }
}
