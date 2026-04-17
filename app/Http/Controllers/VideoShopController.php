<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseThankYouMail;
use App\Models\Purchase;
use App\Models\UserSetting;
use App\Models\Video;
use App\Models\VideoType;
use App\Models\VideoTopic;
use App\Models\VideoDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

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
        $videos = $query->latest()->paginate(4)->withQueryString();

        // Filter option listák
        $types = VideoType::query()->whereHas('videos', fn($q) => $q->where('is_active', true))->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $topics = VideoTopic::query()->whereHas('videos', fn($q) => $q->where('is_active', true))->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $domains = VideoDomain::query()->whereHas('videos', fn($q) => $q->where('is_active', true))->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $tiles = \App\Models\Tile::query()
                    ->whereIn('id', [6, /*4,*/ 2])
                    ->orderByDesc('id')
                    ->get();

        return view('videos.index', compact('videos', 'types', 'topics', 'domains', 'tiles'));
    }

    public function checkout()
    {
        return view('checkout');
    }

    public function placeOrder(Request $request)
    {
        $paymentMethods = array_keys(config('payment_methods', []));

        $validated = $request->validate([
            'personal_last_name' => ['required', 'string', 'max:200'],
            'personal_first_name' => ['required', 'string', 'max:200'],
            'personal_phone' => ['required', 'string', 'max:50'],
            'personal_email' => ['required', 'email', 'max:255'],
            'personal_note' => ['nullable', 'string'],

            'billing_last_name' => ['required', 'string', 'max:200'],
            'billing_first_name' => ['required', 'string', 'max:200'],
            'billing_company_name' => ['nullable', 'string', 'max:255'],
            'billing_vat_number' => ['nullable', 'regex:/^\d{8}-\d-\d{2}$/'],
            'billing_postal_code' => ['required', 'string', 'max:20'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_street_address' => ['required', 'string', 'max:255'],

            'payment_method' => ['required', 'string', Rule::in($paymentMethods)],

            'terms_accepted' => ['accepted'],
            'no_refund' => ['accepted'],
            'newsletter_opt_in' => ['nullable'],
//            'new_video_opt_in' => ['nullable'],

            'items_json' => ['required', 'string'],
        ]);

        $items = $this->normalizeItems($validated['items_json']);

        if (count($items) < 1) {
            return back()
                ->withErrors(['items_json' => 'A kosár üres.'])
                ->withInput();
        }

        $amounts = $this->calculateAmounts($items);

        $purchase = DB::transaction(function () use ($validated, $items, $amounts, $request) {
            return Purchase::create([
                'personal_last_name' => $validated['personal_last_name'],
                'personal_first_name' => $validated['personal_first_name'],
                'personal_phone' => $validated['personal_phone'],
                'personal_email' => $validated['personal_email'],
                'personal_note' => $validated['personal_note'] ?? null,

                'billing_last_name' => $validated['billing_last_name'],
                'billing_first_name' => $validated['billing_first_name'],
                'billing_company_name' => $validated['billing_company_name'] ?? null,
                'billing_vat_number' => $validated['billing_vat_number'] ?? null,
                'billing_postal_code' => $validated['billing_postal_code'],
                'billing_city' => $validated['billing_city'],
                'billing_street_address' => $validated['billing_street_address'],

                'payment_method' => $validated['payment_method'],

                'terms_accepted' => true,
                'privacy_accepted' => true,
                'newsletter_opt_in' => $request->boolean('newsletter_opt_in'),
//                'new_video_opt_in' => $request->boolean('new_video_opt_in'),

                'items' => $items,
                'currency' => 'HUF',
                'subtotal' => $amounts['subtotal'],
                'vat_rate' => $amounts['vat_rate'],
                'vat_amount' => $amounts['vat_amount'],
                'total' => $amounts['total'],

                'status' => 'pending',
                'client_ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ]);
        });

        if ($purchase->payment_method === 'forward_payment') {
            Mail::to([$purchase->personal_email, UserSetting::query()->firstWhere('name', 'admin-email-address')->value])
                ->send(new PurchaseThankYouMail(
                    purchase: $purchase,
                    bankName: 'K&H Bank',
                    bankAccount: '10200823-22223649-00000000',
                ));

            return redirect()
                ->route('order-successful', ['purchaseId' => $purchase])
                ->with('success', 'Sikeres rendelés! Köszönjük.');
        }

        if ($purchase->payment_method === 'card') {
            return app(\App\Services\SimplePayService::class)->startPayment($purchase);
        }

        abort(422, 'Ismeretlen fizetési mód.');
    }

    public function show(Request $request, Video $video)
    {
        abort_unless($video->is_active, 404);

        $embedUrl = $video->embed_url;

        return view('videos.show', compact('video', 'embedUrl'));
    }

    protected function normalizeItems(string $itemsJson): array
    {
        $items = json_decode($itemsJson, true);

        if (! is_array($items)) {
            return [];
        }

        $ids = collect($items)
            ->map(fn ($i) => (int) str_replace('video_', '', data_get($i, 'id')))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $videos = Video::query()
            ->whereIn('id', $ids)
            ->get(['id', 'title', 'price_huf'])
            ->keyBy('id');

        return $ids
            ->map(function ($id) use ($videos) {
                $video = $videos->get($id);

                if (! $video) {
                    return null;
                }

                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'price' => (int) $video->price_huf,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
    protected function calculateAmounts(array $items): array
    {
        $subtotal = collect($items)->sum(fn ($item) => (int) ($item['price'] ?? 0));

        return [
            'subtotal' => $subtotal,
            'vat_rate' => 27,
            'vat_amount' => 0,
            'total' => $subtotal,
        ];
    }

    public function orderSuccessfulByOrderRef(string $orderRef)
    {
        $purchase = Purchase::query()
            ->where('order_number', $orderRef)
            ->firstOrFail();

        $videos = \App\Models\Video::query()
            ->whereIn('id', array_column($purchase->items ?? [], 'id'))
            ->get();

        return view('videos.order-successful', [
            'purchase' => $purchase,
            'videos' => $videos,
            'bankName' => 'K&H Bank',
            'bankAccount' => '10200823-22223649-00000000',
        ]);
    }

    public function orderSuccessful(Request $request)
    {
        $purchase = Purchase::query()->findOrFail($request->integer('purchaseId'));

        $videos = \App\Models\Video::query()
            ->whereIn('id', array_column($purchase->items ?? [], 'id'))
            ->get();

        return view('videos.order-successful', [
            'purchase' => $purchase,
            'videos' => $videos,
            'bankName' => 'K&H Bank',
            'bankAccount' => '10200823-22223649-00000000',
        ]);
    }
    public function paymentFailed(Request $request)
    {
        $purchase = Purchase::query()->findOrFail($request->integer('purchaseId'));

        return view('videos.order-failed', compact('purchase'));
    }
}

