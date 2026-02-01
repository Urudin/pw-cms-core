<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoType;
use App\Models\VideoTopic;
use App\Models\VideoDomain;
use Illuminate\Http\Request;

class VideoShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::query()
            ->where('is_active', true)
            ->with(['type', 'topic', 'domain']);

        // Szabadszavas keresés (title + description)
        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Taxonomy szűrők
        if ($typeId = $request->integer('type_id')) {
            $query->where('video_type_id', $typeId);
        }

        if ($topicId = $request->integer('topic_id')) {
            $query->where('video_topic_id', $topicId);
        }

        if ($domainId = $request->integer('domain_id')) {
            $query->where('video_domain_id', $domainId);
        }

        // (Ha kell rendezés később, most kihagyjuk MVP-nél.)
        $videos = $query->latest()->paginate(12)->withQueryString();

        // Filter option listák
        $types = VideoType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $topics = VideoTopic::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $domains = VideoDomain::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $tiles = \App\Models\Tile::query()
                    ->get();

        return view('videos.index', compact('videos', 'types', 'topics', 'domains', 'tiles'));
    }
}

