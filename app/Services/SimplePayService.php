<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Netipar\SimplePay\Dto\Address;
use Netipar\SimplePay\Dto\Item;
use Netipar\SimplePay\Dto\PaymentRequest;
use Netipar\SimplePay\Enums\Currency;
use Netipar\SimplePay\Enums\PaymentMethod;
use Netipar\SimplePay\Exceptions\SimplePayApiException;
use Netipar\SimplePay\Facades\SimplePay;

class SimplePayService
{
    public function startPayment(Purchase $purchase): RedirectResponse
    {
        try {
            $response = SimplePay::payment()->start(
                new PaymentRequest(
                    currency: Currency::HUF,
                    total: (int) $purchase->total, // legyen konzisztens, ne itt ÁFA-zd
                    orderRef: (string) $purchase->order_number,
                    customerEmail: $purchase->personal_email,
                    language: 'HU',
                    url: route('simplepay.back'),
                    methods: [PaymentMethod::CARD],
                    invoice: new Address(
                        name: $purchase->billing_company_name
                            ?: trim($purchase->billing_last_name . ' ' . $purchase->billing_first_name),
                        country: 'hu',
                        city: $purchase->billing_city,
                        zip: $purchase->billing_postal_code,
                        address: $purchase->billing_street_address,
                    ),
                    items: collect($purchase->items ?? [])
                        ->map(fn ($item) => new Item(
                            title: $item['title'] ?? ('Videó #' . ($item['id'] ?? '')),
                            price: (int) ($item['price'] ?? 0),   // nettó egységár
                            quantity: (int) ($item['quantity'] ?? 1),
                            tax: 27
                        ))
                        ->values()
                        ->all(),
                )
            );

            $purchase->update([
                'payment_provider' => 'simplepay',
                'payment_transaction_id' => $response->transactionId,
                'payment_started_at' => now(),
                'payment_payload' => [
                    'transaction_id' => $response->transactionId,
                    'payment_url' => $response->paymentUrl,
                    'timeout' => $response->timeout,
                ],
            ]);

            return redirect()->away($response->paymentUrl);
        } catch (SimplePayApiException $e) {
            Log::error('SimplePay start error', [
                'purchase_id' => $purchase->id,
                'message' => $e->getMessage(),
                'codes' => $e->getErrorCodes(),
            ]);

            $purchase->update([
                'payment_provider' => 'simplepay',
                'payment_error_message' => $e->getMessage(),
                'payment_failed_at' => now(),
            ]);

            return redirect()
                ->route('checkout')
                ->withErrors(['payment_method' => 'A SimplePay fizetés indítása sikertelen volt.']);
        }
    }
}
