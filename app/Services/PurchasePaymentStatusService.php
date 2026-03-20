<?php

namespace App\Services;

use App\Mail\PurchaseAccessMail;
use App\Models\Purchase;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Mail;

class PurchasePaymentStatusService
{
    public function markPaid(Purchase $purchase, array $payload = [], ?string $transactionId = null): void
    {
        if ($purchase->status === 'paid') {
            return;
        }

        $purchase->update([
            'status' => 'paid',
            'payment_provider' => 'simplepay',
            'payment_transaction_id' => $transactionId ?? $purchase->payment_transaction_id,
            'payment_paid_at' => now(),
            'payment_error_message' => null,
            'payment_payload' => array_merge($purchase->payment_payload ?? [], [
                'ipn' => $payload,
            ]),
        ]);

        app(BillingService::class)->issueInvoice($purchase);

        Mail::to([
            $purchase->personal_email,
            UserSetting::query()->firstWhere('name', 'admin-email-address')->value,
        ])->send(new PurchaseAccessMail($purchase));

        $purchase->update([
            'access_sent' => now(),
        ]);
    }

    public function markFailed(Purchase $purchase, array $payload = [], ?string $transactionId = null, ?string $message = null): void
    {
        if ($purchase->status === 'paid') {
            return;
        }

        $purchase->update([
            'status' => 'failed',
            'payment_provider' => 'simplepay',
            'payment_transaction_id' => $transactionId ?? $purchase->payment_transaction_id,
            'payment_failed_at' => now(),
            'payment_error_message' => $message,
            'payment_payload' => array_merge($purchase->payment_payload ?? [], [
                'ipn' => $payload,
            ]),
        ]);
    }

    public function markCancelled(Purchase $purchase, array $payload = [], ?string $transactionId = null, ?string $message = null): void
    {
        if ($purchase->status === 'paid') {
            return;
        }

        $purchase->update([
            'status' => 'cancelled',
            'payment_provider' => 'simplepay',
            'payment_transaction_id' => $transactionId ?? $purchase->payment_transaction_id,
            'payment_failed_at' => now(),
            'payment_error_message' => $message,
            'payment_payload' => array_merge($purchase->payment_payload ?? [], [
                'ipn' => $payload,
            ]),
        ]);
    }
}
