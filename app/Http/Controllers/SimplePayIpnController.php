<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\PurchasePaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SimplePayIpnController extends Controller
{
    public function handle(Request $request, PurchasePaymentStatusService $paymentStatusService): Response
    {
        $data = json_decode($request->getContent(), true);

        if (! is_array($data)) {
            Log::warning('SimplePay IPN rejected: invalid JSON body');

            return response('Invalid body', 400);
        }

        Log::info('SimplePay IPN received', [
            'order_ref' => $data['orderRef'] ?? null,
            'transaction_id' => $data['transactionId'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        $purchase = Purchase::query()
            ->where('order_number', $data['orderRef'] ?? null)
            ->first();

        if (! $purchase) {
            Log::warning('SimplePay IPN ignored: purchase not found', [
                'order_ref' => $data['orderRef'] ?? null,
                'transaction_id' => $data['transactionId'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

            return response('OK', 200);
        }

        $status = $data['status'] ?? null;
        $transactionId = (string) ($data['transactionId'] ?? '');

        if (! $this->ipnBelongsToPurchase($purchase, $data)) {
            Log::warning('SimplePay IPN rejected: payload does not match purchase', [
                'purchase_id' => $purchase->id,
                'order_ref' => $purchase->order_number,
                'ipn_order_ref' => $data['orderRef'] ?? null,
                'purchase_transaction_id' => $purchase->payment_transaction_id,
                'ipn_transaction_id' => $data['transactionId'] ?? null,
                'status' => $status,
            ]);

            return $this->signedIpnResponse($data);
        }

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
        } else {
            Log::info('SimplePay IPN ignored: unsupported manual/admin status', [
                'purchase_id' => $purchase->id,
                'order_ref' => $purchase->order_number,
                'transaction_id' => $transactionId,
                'status' => $status,
            ]);
        }

        return $this->signedIpnResponse($data);
    }

    private function ipnBelongsToPurchase(Purchase $purchase, array $data): bool
    {
        if ((string) ($data['orderRef'] ?? '') !== (string) $purchase->order_number) {
            return false;
        }

        $ipnTransactionId = (string) ($data['transactionId'] ?? '');
        if ($purchase->payment_transaction_id && $ipnTransactionId !== '' && $ipnTransactionId !== (string) $purchase->payment_transaction_id) {
            return false;
        }

        if (isset($data['currency']) && (string) $data['currency'] !== (string) $purchase->currency) {
            return false;
        }

        if (isset($data['total']) && (float) $data['total'] !== (float) $purchase->total) {
            return false;
        }

        $expectedMerchant = config('simplepay.merchants.HUF.merchant');
        if (isset($data['merchant']) && $expectedMerchant && (string) $data['merchant'] !== (string) $expectedMerchant) {
            return false;
        }

        return true;
    }

    private function signedIpnResponse(array $data): Response
    {
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
