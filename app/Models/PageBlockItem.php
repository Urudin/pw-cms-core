<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockItem extends Model
{
    protected $fillable = ['page_block_id', 'block_id', 'order'];

    public function pageBlock(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}
