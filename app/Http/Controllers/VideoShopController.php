<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Video;
use App\Models\VideoType;
use App\Models\VideoTopic;
use App\Models\VideoDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $videos = $query->latest()->paginate(12)->withQueryString();

        // Filter option listák
        $types = VideoType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $topics = VideoTopic::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $domains = VideoDomain::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $tiles = \App\Models\Tile::query()
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
            // personal
            'personal_last_name' => ['required', 'string', 'max:200'],
            'personal_first_name' => ['required', 'string', 'max:200'],
            'personal_phone' => ['required', 'string', 'max:50'],
            'personal_email' => ['required', 'email', 'max:255'],
            'personal_note' => ['nullable', 'string'],

            // billing
            'billing_last_name' => ['required', 'string', 'max:200'],
            'billing_first_name' => ['required', 'string', 'max:200'],
            'billing_company_name' => ['nullable', 'string', 'max:255'],
            'billing_vat_number' => ['nullable', 'string', 'max:100'],
            'billing_address' => ['required', 'string', 'max:500'],

            // payment
            'payment_method' => ['required', 'string', Rule::in($paymentMethods)],

            // declarations
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
            'newsletter_opt_in' => ['nullable'],
            'new_video_opt_in' => ['nullable'],

            // items from hidden json field
            'items_json' => ['required', 'string'],
        ]);

        $items = json_decode($validated['items_json'], true);

        if (!is_array($items) || count($items) < 1) {
            return back()
                ->withErrors(['items_json' => 'A kosár üres vagy hibás.'])
                ->withInput();
        }
        // normalize items: only id + quantity
        $items = collect($items)
            ->map(fn ($i) => [
                'id' => (int) str_replace('video_', '', data_get($i, 'id')),
                'quantity' => (int) (data_get($i, 'quantity', 1) ?: 1),
            ])
            ->filter(fn ($i) => $i['id'] > 0 && $i['quantity'] > 0)
            ->values()
            ->all();

        if (count($items) < 1) {
            return back()
                ->withErrors(['items_json' => 'A kosár üres.'])
                ->withInput();
        }

        // TODO: itt szerver oldalon számold az árakat DB-ből (ne a kliensből)
        $subtotal = 0.00;
        $vatRate = 0.00;
        $vatAmount = round($subtotal * ($vatRate / 100), 2);
        $total = $subtotal + $vatAmount;

        $purchase = DB::transaction(function () use ($validated, $items, $subtotal, $vatRate, $vatAmount, $total, $request) {
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
                'billing_address' => $validated['billing_address'],

                'payment_method' => $validated['payment_method'],

                'terms_accepted' => true,
                'privacy_accepted' => true,
                'newsletter_opt_in' => $request->boolean('newsletter_opt_in'),
                'new_video_opt_in' => $request->boolean('new_video_opt_in'),

                'items' => $items,
                'currency' => 'HUF',
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,

                'status' => 'pending',
                'client_ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ]);
        });

        // opcionális: ürítsd a sessionbe a cartot, hogy a "köszönjük" oldalon tudd jelezni
        return redirect()
            ->route('order-successful', $purchase) // csinálsz egy route-ot hozzá
            ->with('success', 'Sikeres rendelés! Köszönjük.');
    }
}

