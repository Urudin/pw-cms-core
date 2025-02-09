<?php

namespace App\Models;

use Biostate\FilamentMenuBuilder\Traits\Menuable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string name
 */
class Page extends Model
{
    use HasFactory;
    use Menuable;

    protected $fillable = ['name', 'title', 'slug', 'meta_title', 'meta_keywords', 'meta_description', 'content'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->name);
            }
        });
    }

    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'page_blocks')->withPivot('order')->orderBy('order');
    }

    public function pageBlocks()
    {
        return $this->hasMany(PageBlock::class);
    }

    public function getMenuLinkAttribute(): string
    {
        return route('pages.show', $this);
    }

    public function getMenuNameAttribute(): string
    {
        return $this->name;
    }
}
