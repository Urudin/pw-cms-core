<?php

namespace Tests\Unit;

use App\Models\Purchase;
use App\Services\BillingService;
use App\Services\Co3\Co3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Co3BillingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'co3.base_url' => 'https://eventrix.hu/apitest',
            'co3.username' => 'integracio',
            'co3.password_hash' => 'hash-from-env',
            'co3.language' => 'hu_HU',
            'co3.currency' => 'HUF',
        ]);
    }

    public function test_already_billed_aborts(): void
    {
        $this->expectException(HttpException::class);

        $purchase = new Purchase();
        $purchase->forceFill(['billed' => 1]);

        (new BillingService())->issueInvoice($purchase);
    }

    public function test_zero_total_returns_error(): void
    {
        $result = (new BillingService())->issueInvoice(new Purchase([
            'billed' => 0,
            'items' => [
                ['id' => 10, 'title' => 'Teszt video', 'price' => 0],
            ],
        ]));

        $this->assertSame(['error' => 'Negative or Zero invoice'], $result);
    }

    public function test_customer_search_existing_contact_path_marks_billed_after_generate_invoice(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response(['response' => ['session_key' => 'session-1']]),
            'https://eventrix.hu/apitest/crm' => Http::response(['response' => ['contacts' => [[
                'contact_id' => '77',
                'contact_tax' => '12345678-1-12',
                'contact_email' => 'customer@example.test',
            ]]]]),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push(['response' => ['invoice_id' => '123', 'invoice_number' => 'INV-123']])
                ->push(['response' => ['success' => true]]),
        ]);

        $purchase = $this->purchase([
            'payment_method' => 'card',
            'billing_vat_number' => '12345678-1-12',
        ]);

        $result = (new BillingService())->issueInvoice($purchase);

        $this->assertSame('123', $result['invoice_id']);
        $this->assertSame(1, (int) $purchase->billed);
        $this->assertSame('co3/invoice-INV-123', $purchase->invoice_file_path);

        Http::assertSent(fn ($request) => $request->url() === 'https://eventrix.hu/apitest/crm'
            && isset($request['command']['getContactList'])
            && $request['command']['getContactList']['contact_tax'] === '12345678-1-12');
    }

    public function test_customer_creation_path_uses_set_contact(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response(['response' => ['session_key' => 'session-1']]),
            'https://eventrix.hu/apitest/crm' => Http::sequence()
                ->push(['response' => ['contacts' => []]])
                ->push(['response' => ['contact_id' => '88']]),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push(['response' => ['invoice_id' => '123']])
                ->push(['response' => ['success' => true]]),
        ]);

        $result = (new BillingService())->issueInvoice($this->purchase([
            'billing_company_name' => 'Acme Kft.',
            'billing_vat_number' => '12345678-1-12',
        ]));

        $this->assertSame('123', $result['invoice_id']);

        Http::assertSent(fn ($request) => $request->url() === 'https://eventrix.hu/apitest/crm'
            && isset($request['command']['setContact'])
            && $request['command']['setContact']['contact_type'] === 1
            && $request['command']['setContact']['contact_firm'] === 'Acme Kft.');
    }

    public function test_card_payment_maps_to_bankcard_and_vat_maps_to_domestic_company(): void
    {
        $client = new class extends Co3Client {
            public array $financePayloads = [];

            public function finance(string $method, array $payload): array
            {
                $this->financePayloads[$method] = $payload;

                return ['response' => ['invoice_id' => '123']];
            }
        };

        $purchase = $this->purchase([
            'payment_method' => 'card',
            'billing_vat_number' => '12345678-1-12',
        ]);

        (new BillingService($client))->createInvoiceFromPurchase($purchase, 77);

        $payload = $client->financePayloads['setInvoice'];

        $this->assertSame('bankcard', $payload['method']);
        $this->assertSame('DOMESTIC', $payload['customer_vat_type']);
        $this->assertSame('HUF', $payload['currency']);
        $this->assertSame('hu_HU', $payload['language']);
        $this->assertSame('27.00', $payload['items'][0]['vat']);
        $this->assertStringContainsString('Digitális tartalom', $payload['items'][0]['name']);
    }

    public function test_transfer_payment_maps_to_transfer_and_no_vat_maps_to_private_person(): void
    {
        $client = new class extends Co3Client {
            public array $financePayloads = [];

            public function finance(string $method, array $payload): array
            {
                $this->financePayloads[$method] = $payload;

                return ['response' => ['invoice_id' => '123']];
            }
        };

        (new BillingService($client))->createInvoiceFromPurchase($this->purchase([
            'payment_method' => 'forward_payment',
            'billing_vat_number' => null,
        ]), 77);

        $payload = $client->financePayloads['setInvoice'];

        $this->assertSame('transfer', $payload['method']);
        $this->assertSame('PRIVATE_PERSON', $payload['customer_vat_type']);
    }

    public function test_billed_is_only_set_after_generate_invoice_success(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response(['response' => ['session_key' => 'session-1']]),
            'https://eventrix.hu/apitest/crm' => Http::response(['response' => ['contacts' => [[
                'contact_id' => '77',
                'contact_email' => 'customer@example.test',
            ]]]]),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push(['response' => ['invoice_id' => '123']])
                ->push(['response' => ['success' => 'failed']]),
        ]);

        $purchase = $this->purchase();
        $result = (new BillingService())->issueInvoice($purchase);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(0, (int) $purchase->billed);
        $this->assertNull($purchase->invoice_file_path);
    }

    public function test_api_error_returns_error_array(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response(['response' => ['session_key' => 'session-1']]),
            'https://eventrix.hu/apitest/crm' => Http::response([
                'error' => ['code' => 5001, 'message' => 'CRM failure'],
            ]),
        ]);

        $result = (new BillingService())->issueInvoice($this->purchase());

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('CRM failure', $result['error']);
    }

    public function test_client_sends_json_and_does_not_hash_password_hash(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response(['response' => ['session_key' => 'session-1']]),
        ]);

        (new Co3Client())->authenticate();

        Http::assertSent(fn ($request) => $request->hasHeader('Content-Type', 'application/json')
            && $request['command']['authenticate']['username'] === 'integracio'
            && $request['command']['authenticate']['password'] === 'hash-from-env');
    }

    private function purchase(array $overrides = []): Purchase
    {
        return new Purchase(array_merge([
            'billed' => 0,
            'payment_method' => 'card',
            'personal_email' => 'customer@example.test',
            'billing_first_name' => 'Elek',
            'billing_last_name' => 'Teszt',
            'billing_company_name' => null,
            'billing_vat_number' => null,
            'billing_postal_code' => '1111',
            'billing_city' => 'Budapest',
            'billing_street_address' => 'Fo utca 1.',
            'items' => [
                ['id' => 10, 'title' => 'Teszt video', 'price' => 5000],
            ],
        ], $overrides));
    }
}
