<?php

namespace App\Services;

use App\Models\Purchase;
use App\Services\Co3\Co3Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BillingService
{
    public function __construct(
        private readonly Co3Client $co3 = new Co3Client(),
    ) {
    }

    public function issueInvoice(Purchase $purchase): array
    {
        if ((int) $purchase->billed === 1) {
            abort(403, 'Ez a számla már ki lett állítva');
        }

        $lock = $purchase->id ? Cache::lock('co3.issue_invoice.purchase.' . $purchase->id, 300) : null;

        if ($lock && ! $lock->get()) {
            abort(403, 'Ez a számla már ki lett állítva');
        }

        try {
            $items = $this->validInvoiceItems($purchase);
            $total = $items->sum(fn ($item) => (int) ($item['price'] ?? 0));

            if ($total <= 0) {
                Log::info('Negative or zero invoice skipped, purchase id: ' . $purchase->id);

                return ['error' => 'Negative or Zero invoice'];
            }

            $contactId = $this->findContactForPurchase($purchase);
            $invoice = $this->createInvoiceFromPurchase($purchase, $contactId, $items->all());
            $generatedInvoice = $this->generateInvoice((string) $invoice['invoice_id']);

            $invoiceNumber = $generatedInvoice['invoice_number'] ?? $invoice['invoice_number'] ?? null;
            $invoiceReference = $invoiceNumber ?: $invoice['invoice_id'];
            $invoiceFilePath = $this->storeInvoiceReference($invoiceReference, $generatedInvoice);

            $this->markPurchaseBilled($purchase, $invoiceFilePath);

            Log::info('CO3 invoice issued for purchase id: ' . $purchase->id . ', invoice id: ' . $invoice['invoice_id']);

            return [
                'invoice_id' => $invoice['invoice_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_file_path' => $invoiceFilePath,
            ];
        } catch (Throwable $e) {
            Log::error('CO3 invoice creation issue: ' . $e->getMessage(), [
                'purchase_id' => $purchase->id,
                'exception' => get_class($e),
            ]);

            return ['error' => $e->getMessage()];
        } finally {
            $lock?->release();
        }
    }

    public function findContactForPurchase(Purchase $purchase): string
    {
        $response = $this->co3->crm('getContactList', $this->contactSearchPayload($purchase));
        $contacts = $this->extractContacts($response);
        $match = $this->selectBestContactMatch($contacts, $purchase);

        if ($match !== null) {
            $contactId = $this->extractId($match, ['contact_id', 'id']);

            if ($contactId !== null) {
                return $contactId;
            }
        }

        return $this->createContactFromPurchase($purchase);
    }

    public function createContactFromPurchase(Purchase $purchase): string
    {
        $isCompany = filled($purchase->billing_company_name);
        $response = $this->co3->crm('setContact', [
            'contact_id' => '',
            'contact_type' => $isCompany ? 1 : 0,
            'contact_firstname' => (string) $purchase->billing_first_name,
            'contact_lastname' => (string) $purchase->billing_last_name,
            'contact_firm' => $isCompany ? (string) $purchase->billing_company_name : '',
            'contact_postal_code' => (string) $purchase->billing_postal_code,
            'contact_city' => (string) $purchase->billing_city,
            'contact_address' => (string) $purchase->billing_street_address,
            'contact_postal_address' => $this->billingAddress($purchase),
            'contact_email' => (string) $purchase->personal_email,
            'contact_tax' => (string) $purchase->billing_vat_number,
            'contact_note' => 'Purchase #' . $this->purchaseReference($purchase),
        ]);

        $contactId = $this->extractId($response, ['contact_id', 'id']);

        if ($contactId === null) {
            $this->logUnparseableResponse('CO3 setContact response did not contain contact_id.', $response);

            throw new RuntimeException('CO3 setContact response did not contain contact_id.');
        }

        return $contactId;
    }

    public function createInvoiceFromPurchase(Purchase $purchase, string|int $contactId, ?array $items = null): array
    {
        $items ??= $this->validInvoiceItems($purchase)->all();
        $date = now()->format('Y-m-d');
        $reference = $this->purchaseReference($purchase);

        $payload = [
            'crdr' => 'outgoing',
            'method' => $this->co3PaymentMethod($purchase),
            'issued_date' => $date,
            'payment_date' => $date,
            'fulfillment_date' => $date,
            'selected_account' => (string) config('co3.selected_account', ''),
            'contact_id' => (string) $contactId,
            'customer_vat_type' => filled($purchase->billing_vat_number) ? 'DOMESTIC' : 'PRIVATE_PERSON',
            'invoice_description' => 'Purchase #' . $reference,
            'central_description' => 'Purchase #' . $reference,
            'currency' => (string) config('co3.currency', 'HUF'),
            'esignature' => (int) config('co3.esignature', 0),
            'language' => (string) config('co3.language', 'hu_HU'),
            'email' => (string) $purchase->personal_email,
            'proforma' => (int) config('co3.proforma', 0),
            'items' => collect($items)->map(fn ($item) => [
                'name' => ($item['title'] ?? 'Videó') . ' - Digitális tartalom megtekintés jogosultság',
                'unit_price' => number_format((int) ($item['price'] ?? 0), 2, '.', ''),
                'unit' => 'pcs',
                'quantity' => '1.00',
                'vat' => '27.00',
                'discount' => 0,
                'description' => ($item['title'] ?? 'Videó') . ' - Digitális tartalom megtekintés jogosultság',
                'product_id' => '',
                'deposit' => 0,
            ])->values()->all(),
        ];

        if (blank($payload['selected_account'])) {
            unset($payload['selected_account']);
        }

        $response = $this->co3->finance('setInvoice', $payload);
        $invoiceId = $this->extractId($response, ['invoice_id', 'id']);

        if ($invoiceId === null) {
            $this->logUnparseableResponse('CO3 setInvoice response did not contain invoice_id.', $response);

            throw new RuntimeException('CO3 setInvoice response did not contain invoice_id.');
        }

        return [
            'invoice_id' => $invoiceId,
            'invoice_number' => $this->extractId($response, ['invoice_number', 'number']),
        ];
    }

    public function generateInvoice(string $invoiceId): array
    {
        $response = $this->co3->generateInvoice([
            'invoice_id' => $invoiceId,
            'language' => (string) config('co3.language', 'hu_HU'),
            'proforma' => (int) config('co3.proforma', 0),
        ]);

        $this->assertSuccessfulResponse($response, 'CO3 generateInvoice did not confirm success.');

        return [
            'invoice_id' => $invoiceId,
            'invoice_number' => $this->extractId($response, ['invoice_number', 'number']),
            'pdf_content' => $this->extractPdfContent($response),
            'pdf_url' => $this->extractId($response, ['pdf_url', 'url', 'file_url']),
        ];
    }

    private function contactSearchPayload(Purchase $purchase): array
    {
        $payload = [
            'contact_tax' => (string) $purchase->billing_vat_number,
            'contact_email' => (string) $purchase->personal_email,
            'contact_name' => $this->customerName($purchase),
            'contact_postal_code' => (string) $purchase->billing_postal_code,
            'contact_city' => (string) $purchase->billing_city,
            'contact_address' => (string) $purchase->billing_street_address,
        ];

        return array_filter($payload, fn ($value) => filled($value));
    }

    private function selectBestContactMatch(array $contacts, Purchase $purchase): ?array
    {
        $vatNumber = $this->normalize((string) $purchase->billing_vat_number);
        $email = $this->normalize((string) $purchase->personal_email);
        $name = $this->normalize($this->customerName($purchase));
        $address = $this->normalize($this->billingAddress($purchase));

        foreach ($contacts as $contact) {
            if ($vatNumber !== '' && $vatNumber === $this->normalize((string) ($contact['contact_tax'] ?? $contact['tax'] ?? ''))) {
                return $contact;
            }
        }

        foreach ($contacts as $contact) {
            if ($email !== '' && $email === $this->normalize((string) ($contact['contact_email'] ?? $contact['email'] ?? ''))) {
                return $contact;
            }
        }

        foreach ($contacts as $contact) {
            $contactName = $this->normalize(collect([
                $contact['contact_firm'] ?? null,
                $contact['contact_lastname'] ?? null,
                $contact['contact_firstname'] ?? null,
                $contact['name'] ?? null,
            ])->filter()->implode(' '));
            $contactAddress = $this->normalize(collect([
                $contact['contact_postal_code'] ?? null,
                $contact['contact_city'] ?? null,
                $contact['contact_address'] ?? $contact['address'] ?? null,
            ])->filter()->implode(' '));

            if ($name !== '' && $name === $contactName && ($address === '' || $address === $contactAddress)) {
                return $contact;
            }
        }

        return null;
    }

    private function extractContacts(array $response): array
    {
        $contacts = [];

        $walk = function (array $value) use (&$walk, &$contacts): void {
            if ($this->extractId($value, ['contact_id', 'id']) !== null) {
                $contacts[] = $value;
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($response);

        return $contacts;
    }

    private function assertSuccessfulResponse(array $response, string $message): void
    {
        $success = $this->co3->extractFirst($response, ['success', 'status', 'result']);

        if ($success === null || in_array(strtolower($success), ['1', 'true', 'ok', 'success', 'generated'], true)) {
            return;
        }

        throw new RuntimeException($message);
    }

    private function storeInvoiceReference(string $invoiceReference, array $generatedInvoice): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $invoiceReference);

        if (filled($generatedInvoice['pdf_content'] ?? null)) {
            $invoiceFilePath = "co3/{$fileName}.pdf";
            Storage::disk('public')->put($invoiceFilePath, $generatedInvoice['pdf_content']);

            return $invoiceFilePath;
        }

        if (filled($generatedInvoice['pdf_url'] ?? null)) {
            return (string) $generatedInvoice['pdf_url'];
        }

        // TODO: Add CO3 PDF download once the production API exposes the final document endpoint/field.
        return "co3/invoice-{$fileName}";
    }

    private function markPurchaseBilled(Purchase $purchase, string $invoiceFilePath): void
    {
        if (! $purchase->exists) {
            $purchase->forceFill([
                'invoice_file_path' => $invoiceFilePath,
                'billed' => 1,
            ]);

            return;
        }

        DB::transaction(function () use ($purchase, $invoiceFilePath) {
            $freshPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ((int) $freshPurchase->billed === 1) {
                abort(403, 'Ez a számla már ki lett állítva');
            }

            $freshPurchase->invoice_file_path = $invoiceFilePath;
            $freshPurchase->billed = 1;
            $freshPurchase->save();

            $purchase->forceFill([
                'invoice_file_path' => $invoiceFilePath,
                'billed' => 1,
            ]);
        });
    }

    private function validInvoiceItems(Purchase $purchase): Collection
    {
        return collect($purchase->items ?? [])
            ->filter(fn ($item) => ! empty($item['id']) && (int) ($item['price'] ?? 0) > 0)
            ->values();
    }

    private function co3PaymentMethod(Purchase $purchase): string
    {
        return $purchase->payment_method === 'card' ? 'bankcard' : 'transfer';
    }

    private function customerName(Purchase $purchase): string
    {
        if (filled($purchase->billing_company_name)) {
            return (string) $purchase->billing_company_name;
        }

        return trim((string) $purchase->billing_last_name . ' ' . (string) $purchase->billing_first_name);
    }

    private function billingAddress(Purchase $purchase): string
    {
        return trim(collect([
            $purchase->billing_postal_code,
            $purchase->billing_city,
            $purchase->billing_street_address,
        ])->filter(fn ($part) => filled($part))->implode(' '));
    }

    private function purchaseReference(Purchase $purchase): string
    {
        return (string) ($purchase->order_number ?: $purchase->id);
    }

    private function extractId(array $response, array $keys): ?string
    {
        return $this->co3->extractFirst($response, $keys);
    }

    private function extractPdfContent(array $response): ?string
    {
        $encoded = $this->extractId($response, ['pdf', 'file', 'content', 'attachment']);

        if ($encoded === null) {
            return null;
        }

        $decoded = base64_decode($encoded, true);

        return $decoded !== false && str_starts_with($decoded, '%PDF') ? $decoded : null;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($value))) ?? '';
    }

    private function logUnparseableResponse(string $message, array $response): void
    {
        Log::debug($message, [
            'response_keys' => array_slice(array_keys($response), 0, 10),
        ]);
    }
}
