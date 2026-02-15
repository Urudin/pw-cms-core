<?php

namespace App\Models;

use App\Services\BillingService;
use Illuminate\Database\Eloquent\Model;
use zoparga\SzamlazzHu\Invoice;

class Purchase extends Model
{
    protected $fillable = [
        // personal
        'personal_last_name',
        'personal_first_name',
        'personal_phone',
        'personal_email',
        'personal_note',

        // billing
        'billing_last_name',
        'billing_first_name',
        'billing_company_name',
        'billing_vat_number',
        'billing_address',

        // payment
        'payment_method',

        // declarations
        'terms_accepted',
        'privacy_accepted',
        'newsletter_opt_in',
        'new_video_opt_in',

        // order payload
        'items',
        'currency',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',

        // meta
        'status',
        'client_ip',
        'user_agent',

        'billing_postal_code',
        'billing_city',
        'billing_street_address',
        'access_sent',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'privacy_accepted' => 'boolean',
        'newsletter_opt_in' => 'boolean',
        'new_video_opt_in' => 'boolean',

        'items' => 'array',

        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function getTotal()
    {
        $total = 0;
        $videos = Video::query()->whereIn('id', array_column($this->items, 'id'))->get();
        foreach($videos as $video) {
            $total += $video->price_huf;
        }
        return $total;
    }

    public function issueInvoice(Purchase $purchase): Invoice|array
    {
        return (new BillingService())->issueInvoice($purchase);
    }
}
