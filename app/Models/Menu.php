<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for query purposes only
 */
class Menu extends Model
{
    protected $fillable = [];

    public function menu_items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
