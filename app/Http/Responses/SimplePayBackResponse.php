<?php

namespace App\Http\Responses;

use App\Models\Purchase;
use App\Services\PurchasePaymentStatusService;
use Illuminate\Http\Request;
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
        $purchase = Purchase::query()
            ->where('order_number', $back->orderRef)
            ->first();

        if (! $purchase) {
            return to_route('checkout')->with('error', 'A rendelés nem található.');
        }

        if ($back->event === BackEvent::Fail) {
            $this->paymentStatusService->markFailed(
                $purchase,
                ['back' => $back->event->value],
                $purchase->payment_transaction_id,
                'A fizetés sikertelen volt.'
            );

            return to_route('payment-failed', ['purchaseId' => $purchase->id]);
        }

        if ($back->event === BackEvent::Cancel) {
            $this->paymentStatusService->markCancelled(
                $purchase,
                ['back' => $back->event->value],
                $purchase->payment_transaction_id,
                'A fizetést a felhasználó megszakította.'
            );

            return to_route('payment-failed', ['purchaseId' => $purchase->id]);
        }

        if ($back->event === BackEvent::Timeout) {
            $this->paymentStatusService->markFailed(
                $purchase,
                ['back' => $back->event->value],
                $purchase->payment_transaction_id,
                'A fizetés időtúllépés miatt megszakadt.'
            );

            return to_route('payment-failed', ['purchaseId' => $purchase->id]);
        }

        return to_route('order-successful', ['purchaseId' => $purchase->id]);
    }
}
