<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\PurchasePaymentStatusService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimplePayIpnController extends Controller
{
    public function handle(Request $request, PurchasePaymentStatusService $paymentStatusService): Response
    {
        $data = json_decode($request->getContent(), true);

        if (! is_array($data)) {
            return response('Invalid body', 400);
        }

        $purchase = Purchase::query()
            ->where('order_number', $data['orderRef'] ?? null)
            ->first();

        if (! $purchase) {
            return response('OK', 200);
        }

        $status = $data['status'] ?? null;
        $transactionId = (string) ($data['transactionId'] ?? '');

        if ($status === 'FINISHED') {
            $paymentStatusService->markPaid($purchase, $data, $transactionId);
        } elseif (in_array($status, ['NOTAUTHORIZED', 'TIMEOUT'], true)) {
            $paymentStatusService->markFailed(
                $purchase,
                $data,
                $transactionId,
                'A SimplePay fizetés sikertelen vagy időtúllépéses volt.'
            );
        } elseif ($status === 'CANCELLED') {
            $paymentStatusService->markCancelled(
                $purchase,
                $data,
                $transactionId,
                'A fizetést a felhasználó megszakította.'
            );
        }

        $payload = $data;
        $payload['receiveDate'] = now()->toIso8601String();

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $secret = trim((string) config('simplepay.merchants.HUF.secret_key'));
        $signature = base64_encode(hash_hmac('sha384', $json, $secret, true));

        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('Signature', $signature);
    }
}
