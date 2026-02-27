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
        'username',
        'password',
        'original_price_huf',
        'duration_seconds',
    ];

    protected $casts = [
        'price_huf' => 'integer',
        'is_active' => 'boolean',
    ];

    public function type()   { return $this->belongsTo(VideoType::class, 'video_type_id'); }
    public function topic()  { return $this->belongsTo(VideoTopic::class, 'video_topic_id'); }
    public function domain() { return $this->belongsTo(VideoDomain::class, 'video_domain_id'); }
    public function accesses()  { return $this->hasMany(VideoAccess::class, 'video_id'); }

    public function getTotalUsageCountAttribute(): int
    {
        return (int) $this->accesses()->sum('usage_count');
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return self::makeEmbedUrl($this->video_url);
    }

    public static function makeEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        // YouTube
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            $id = $m[1];
            return "https://www.youtube-nocookie.com/embed/{$id}";
        }

        // Vimeo (vimeo.com/123, player.vimeo.com/video/123, channels/.../123, etc.)
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            $id = $m[1];
            return "https://player.vimeo.com/video/{$id}";
        }

        // Vimeo extra: pl. vimeo.com/channels/staffpicks/123456789
        if (preg_match('~vimeo\.com/(?:channels/[^/]+/|groups/[^/]+/videos/|album/\d+/video/|showcase/\d+/video/|ondemand/[^/]+/)(\d+)~i', $url, $m)) {
            $id = $m[1];
            return "https://player.vimeo.com/video/{$id}";
        }

        return null;
    }
}
