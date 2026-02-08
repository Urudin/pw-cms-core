<?php

namespace App\Mail;

use App\Models\Purchase;
use App\Models\Video;
use App\Models\VideoAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PurchaseAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Purchase $purchase)
    {
    }

    public function build(): self
    {
        $items = $this->purchase->items ?? [];

        $videoIds = collect($items)->map(function ($item) {
            if (is_array($item)) return $item['id'] ?? null;
            if (is_object($item)) return $item->id ?? null;
            if (is_numeric($item)) return (int) $item;
            return null;
        })->filter()->unique()->values()->all();

        $videos = Video::query()->whereIn('id', $videoIds)->get()->keyBy('id');
        $accesses = [];
        foreach($videos as $video) {
            $password = Str::random(10);
            $access = VideoAccess::query()->create([
                'video_id' => $video->id,
                'password' => $password,
                'email' => $this->purchase->personal_email
            ]);
            $accesses[$access->video_id] = $access->toArray();
        }
        return $this->subject('Digitális tartalmak elérése')
            ->view('mail.purchase-access')
            ->with([
                'purchase' => $this->purchase,
                'videos'   => $videos,
                'items'    => $items,
                'accesses' => $accesses
            ]);
    }
}
