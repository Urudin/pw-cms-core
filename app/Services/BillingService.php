<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\Log;
use zoparga\SzamlazzHu\Client\ApiErrors\CommonResponseException;
use zoparga\SzamlazzHu\Client\Client;
use zoparga\SzamlazzHu\Client\Errors\InvoiceValidationException;
use zoparga\SzamlazzHu\Internal\Support\PaymentMethods;
use zoparga\SzamlazzHu\Invoice;

class BillingService
{
    use PaymentMethods;

    public function issueInvoice(Purchase $purchase)
    {
        if ((int) $purchase->billed === 1) {
            abort(403, 'Ez a számla már ki lett állítva');
        }

        try {
            $items = collect($purchase->items ?? [])
                ->filter(fn ($item) => ! empty($item['id']) && (int) ($item['price'] ?? 0) > 0)
                ->values();

            $total = $items->sum(fn ($item) => (int) ($item['price'] ?? 0));

            if ($total <= 0) {
                Log::info('Negative or zero invoice skipped, purchase id: ' . $purchase->id);

                return ['error' => 'Negative or Zero invoice'];
            }

            $orderNumber = uniqid();

            $invoice = new Invoice();
            $invoice->invoiceNumber = $orderNumber;
            $invoice->orderNumber = $orderNumber;
            $invoice->isElectronic = true;
            $invoice->invoiceLanguage = 'hu';
            $invoice->currency = 'HUF';
            $invoice->fulfillmentAt = now();
            $invoice->paymentDeadline = now();
            $invoice->paymentMethod = $purchase->payment_method === 'card'
                ? self::$paymentMethods['credit_card']
                : self::$paymentMethods['transfer'];
            $invoice->isImprestInvoice = false;
            $invoice->isFinalInvoice = false;
            $invoice->exchangeRateBank = 'MNB';
            $invoice->isPaid = true;
            $invoice->invoicePrefix = config('payment_settings.invoice_prefix');

            $vatNumber = $purchase->billing_vat_number;
            $taxSubject = ! empty($vatNumber)
                ? Client::HUNGARIAN_TAX_ID
                : Client::NO_TAX_ID;

            $customerData = [
                'customerName' => ! empty($purchase->billing_company_name)
                    ? $purchase->billing_company_name
                    : trim($purchase->billing_last_name . ' ' . $purchase->billing_first_name),
                'customerZipCode' => $purchase->billing_postal_code,
                'customerCity' => $purchase->billing_city,
                'customerAddress' => $purchase->billing_street_address,
                'customerReceivesEmail' => true,
                'customerEmail' => $purchase->personal_email,
                'customerTaxSubject' => $taxSubject,
            ];

            if (! empty($vatNumber)) {
                $customerData['taxNumber'] = $vatNumber;
            }

            $invoice->setCustomer($customerData);

            foreach ($items as $item) {
                $invoice->addItem([
                    'name' => ($item['title'] ?? 'Videó') . ' - Digitális tartalom megtekintés jogosultság',
                    'quantity' => 1.0,
                    'quantityUnit' => 'piece',
                    'netUnitPrice' => (int) ($item['price'] ?? 0),
                    'taxRate' => 27,
                ]);
            }

            $invoice->save();

            $invoiceFilePath = $invoice->toArray();

            $purchase->invoice_file_path = 'szamlazzhu/' . $invoiceFilePath['invoiceNumber'] . '.pdf';
            $purchase->billed = 1;
            $purchase->save();

            Log::info('Invoice issued with orderNumber: ' . $orderNumber);

            return $invoice;
        } catch (\Exception $e) {
            if ($e instanceof InvoiceValidationException) {
                Log::error($e->getValidator()->errors()->toJson());
            } elseif ($e instanceof CommonResponseException) {
                Log::error($e->getMessage());
            }

            Log::error('Invoice Creation Issue: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return ['error' => $e->getMessage()];
        }
    }
}
