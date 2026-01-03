<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTileItem extends Model
{
    protected $fillable = ['page_tile_id', 'tile_id', 'order'];

    public function pageTile(): BelongsTo
    {
        return $this->belongsTo(PageTile::class);
    }

    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class);
    }
}
