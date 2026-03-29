<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Tile extends Model
{
    protected $guarded = ['id'];

    public function getRenderedContentAttribute(): string
    {
        $content = $this->content ?? '';

        if ($content === '' || ! str_contains($content, '[video')) {
            return $content;
        }

        preg_match_all('/\[video\s+(\d+)]/', $content, $matches);

        $videoIds = collect($matches[1] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($videoIds->isEmpty()) {
            return $content;
        }

        $videos = Video::query()
            ->whereIn('id', $videoIds)
            ->where('is_active', true) // ha kell szűrés
            ->get()
            ->keyBy('id');

        return preg_replace_callback(
            '/\[video\s+(\d+)]/',
            function (array $matches) use ($videos): string {
                $videoId = (int) $matches[1];
                $video = $videos->get($videoId);

                if (! $video) {
                    return '';
                }

                return view('components.tiles.video', [
                    'video' => $video,
                ])->render();
            },
            $content
        );
    }
}
