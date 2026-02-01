<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_url',
        'price_huf',
        'is_active',
        'thumbnail_url',
    ];

    protected $casts = [
        'price_huf' => 'integer',
        'is_active' => 'boolean',
    ];

    public function type()   { return $this->belongsTo(VideoType::class, 'video_type_id'); }
    public function topic()  { return $this->belongsTo(VideoTopic::class, 'video_topic_id'); }
    public function domain() { return $this->belongsTo(VideoDomain::class, 'video_domain_id'); }

}
