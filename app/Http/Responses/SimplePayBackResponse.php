<?php

namespace App\Http\Responses;

use App\Models\Purchase;
use App\Services\PurchasePaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Netipar\SimplePay\Contracts\BackUrlResponse;
use Netipar\SimplePay\Dto\BackResponse;
use Netipar\SimplePay\Enums\BackEvent;
use Symfony\Component\HttpFoundation\Response;

class SimplePayBackResponse implements BackUrlResponse
{
    public function __construct(
        private PurchasePaymentStatusService $paymentStatusService,
    ) {}

    public function toResponse(Request $request, BackResponse $back): Response
    {
        Log::info('SimplePay browser back received', [
            'event' => $back->event->value,
            'order_ref' => $back->orderRef,
            'transaction_id' => $back->transactionId,
        ]);

        $purchase = Purchase::query()
            ->where('order_number', $back->orderRef)
            ->first();

        if (! $purchase) {
            Log::warning('SimplePay browser back order not found', [
                'event' => $back->event->value,
                'order_ref' => $back->orderRef,
                'transaction_id' => $back->transactionId,
            ]);

            return to_route('checkout')->with('error', 'A rendelés nem található.');
        }

        if ($back->event === BackEvent::Fail) {
            $this->paymentStatusService->markFailed(
                $purchase,
                ['back' => $back->event->value, 'transaction_id' => $back->transactionId],
                (string) $back->transactionId,
                'A fizetés sikertelen volt.'
            );

            return to_route('payment-failed', [
                'purchaseId' => $purchase->id,
                'paymentResult' => 'failed',
            ]);
        }

        if ($back->event === BackEvent::Cancel) {
            $this->paymentStatusService->markCancelled(
                $purchase,
                ['back' => $back->event->value, 'transaction_id' => $back->transactionId],
                (string) $back->transactionId,
                'A fizetést a felhasználó megszakította.'
            );

            return to_route('payment-failed', [
                'purchaseId' => $purchase->id,
                'paymentResult' => 'cancelled',
            ]);
        }

        if ($back->event === BackEvent::Timeout) {
            $this->paymentStatusService->markFailed(
                $purchase,
                ['back' => $back->event->value, 'transaction_id' => $back->transactionId],
                (string) $back->transactionId,
                'A fizetés időtúllépés miatt megszakadt.'
            );

            return to_route('payment-failed', [
                'purchaseId' => $purchase->id,
                'paymentResult' => 'timeout',
            ]);
        }

        return to_route('order-successful', ['purchaseId' => $purchase->id]);
    }
}
