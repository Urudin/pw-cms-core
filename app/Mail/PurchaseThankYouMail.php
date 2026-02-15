<?php

namespace App\Mail;

use App\Models\Purchase;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Purchase $purchase,
        public string $bankName = 'K&H Bank',
        public string $bankAccount = '10200823-22223649-00000000',
        public float $vatRate = 0.27,
        public string $accentBlue = '#2f46d6',
        public string $accentPink = '#d63b73',
        public string $cardBg = '#efeff2',
    ) {}

    public function build(): self
    {
        // items: nálad tipikusan tömb -> [ ['id'=>..,'name'=>..], ... ]
        $itemIds = collect($this->purchase->items ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $videos = $itemIds
            ? Video::query()->whereIn('id', $itemIds)->get()
            : collect();

        $totalNet = (float) ($this->purchase->getTotal() ?? 0);
        $totalGross = $totalNet * (1 + $this->vatRate);

        // Ha van saját “label” meződ, használd azt; ha nincs, fordítsunk map-pel
        $methodRaw = (string) ($this->purchase->payment_method ?? 'forward_payment');

        $paymentMethodLabel = match ($methodRaw) {
            'forward_payment' => 'Banki átutalás',
            'card' => 'Online bankkártyás fizetés',
            default => 'Banki átutalás',
        };

        // Subjectben az azonosító jól jön
        return $this->subject('Köszönjük megrendelését! #' . $this->purchase->id)
            ->view('mail.purchase-thank-you', [
                'purchase' => $this->purchase,
                'videos' => $videos,
                'orderId' => $this->purchase->id,
                'paymentMethodLabel' => $paymentMethodLabel,
                'totalGross' => $totalGross,
                'vatRate' => $this->vatRate,
                'bankName' => $this->bankName,
                'bankAccount' => $this->bankAccount,
                'accentBlue' => $this->accentBlue,
                'accentPink' => $this->accentPink,
                'cardBg' => $this->cardBg,
            ]);
    }
}
