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
            'co3.api_key' => 'api-key-from-env',
            'co3.username' => 'integracio',
            'co3.password_hash' => 'hash-from-env',
            'co3.contact_owner' => 'contact-owner-from-env',
            'co3.contact_category' => 'Client',
            'co3.selected_account' => 'selected-account-from-env',
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
            'https://eventrix.hu/apitest/authenticate' => $this->xmlResponse('<response><session_key>session-1</session_key></response>'),
            'https://eventrix.hu/apitest/crm' => Http::sequence()
                ->push('<response><contacts><item><contact_id>77</contact_id><contact_tax>12345678-1-12</contact_tax><contact_email>customer@example.test</contact_email><contact_firstname>Old</contact_firstname><contact_lastname>Name</contact_lastname></item></contacts></response>', 200, $this->xmlHeaders())
                ->push('<response><contact_id>77</contact_id></response>', 200, $this->xmlHeaders()),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push('<response><invoice_id>123</invoice_id><invoice_number>INV-123</invoice_number></response>', 200, $this->xmlHeaders())
                ->push('<response><success>true</success></response>', 200, $this->xmlHeaders()),
        ]);

        $purchase = $this->purchase([
            'payment_method' => 'card',
            'billing_vat_number' => '12345678-1-12',
        ]);

        $result = (new BillingService())->issueInvoice($purchase);

        $this->assertSame('123', $result['invoice_id']);
        $this->assertSame(1, (int) $purchase->billed);
        $this->assertSame('co3/invoice-INV-123', $purchase->invoice_file_path);

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/crm', 'getContactList')
            && $this->xmlValue($request, 'getContactList/search_term') === 'customer@example.test'
            && $this->xmlValue($request, 'getContactList/contact_tax') === '12345678-1-12'
            && $this->xmlValue($request, 'getContactList/contact_address') === '1111, Budapest Fo utca 1.'
            && $this->xmlValue($request, 'getContactList/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'getContactList/session_key') === 'session-1');

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/crm', 'setContact')
            && $this->xmlValue($request, 'setContact/contact_id') === '77'
            && $this->xmlValue($request, 'setContact/contact_firstname') === 'Elek'
            && $this->xmlValue($request, 'setContact/contact_lastname') === 'Teszt'
            && $this->xmlValue($request, 'setContact/contact_tax') === '12345678-1-12'
            && $this->xmlValue($request, 'setContact/contact_email') === 'customer@example.test'
            && $this->xmlValue($request, 'setContact/contact_address') === '1111, Budapest Fo utca 1.'
            && $this->xmlValue($request, 'setContact/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'setContact/session_key') === 'session-1');

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/finance', 'setInvoice')
            && $this->xmlValue($request, 'setInvoice/contact_id') === '77'
            && $this->xmlValue($request, 'setInvoice/selected_account') === 'selected-account-from-env'
            && $this->xmlValue($request, 'setInvoice/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'setInvoice/session_key') === 'session-1');

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/finance', 'generateInvoice')
            && $this->xmlValue($request, 'generateInvoice/invoice_id') === '123'
            && $this->xmlValue($request, 'generateInvoice/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'generateInvoice/session_key') === 'session-1');
    }

    public function test_customer_creation_path_uses_set_contact(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => $this->xmlResponse('<response><session_key>session-1</session_key></response>'),
            'https://eventrix.hu/apitest/crm' => Http::sequence()
                ->push('<response><contacts></contacts></response>', 200, $this->xmlHeaders())
                ->push('<response><contact_id>88</contact_id></response>', 200, $this->xmlHeaders())
                ->push('<response><contact><contact_id>88</contact_id><contact_firm>Acme Kft.</contact_firm><contact_tax>12345678-1-12</contact_tax><contact_email>customer@example.test</contact_email></contact></response>', 200, $this->xmlHeaders()),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push('<response><invoice_id>123</invoice_id></response>', 200, $this->xmlHeaders())
                ->push('<response><success>true</success></response>', 200, $this->xmlHeaders()),
        ]);

        $result = (new BillingService())->issueInvoice($this->purchase([
            'billing_company_name' => 'Acme Kft.',
            'billing_vat_number' => '12345678-1-12',
        ]));

        $this->assertSame('123', $result['invoice_id']);

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/crm', 'setContact')
            && $this->xmlValue($request, 'setContact/contact_type') === '1'
            && $this->xmlValue($request, 'setContact/contact_owner') === 'contact-owner-from-env'
            && $this->xmlValue($request, 'setContact/contact_categories') === 'Client'
            && $this->xmlValue($request, 'setContact/contact_firm') === 'Acme Kft.'
            && $this->xmlValue($request, 'setContact/contact_address') === '1111, Budapest Fo utca 1.'
            && $this->xmlValue($request, 'setContact/contact_postal_address') === '1111, Budapest Fo utca 1.'
            && $this->xmlValue($request, 'setContact/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'setContact/session_key') === 'session-1');

        $crmRequests = collect(Http::recorded())
            ->map(fn ($record) => $record[0])
            ->filter(fn ($request) => $request->url() === 'https://eventrix.hu/apitest/crm');

        $this->assertSame(3, $crmRequests->count());
        $this->assertSame('getContactList', $this->requestMethodName($crmRequests->values()[0]));
        $this->assertSame('setContact', $this->requestMethodName($crmRequests->values()[1]));
        $this->assertSame('getContact', $this->requestMethodName($crmRequests->values()[2]));
        $this->assertSame('88', $this->xmlValue($crmRequests->values()[2], 'getContact/contact_id'));
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
            'https://eventrix.hu/apitest/authenticate' => $this->xmlResponse('<response><session_key>session-1</session_key></response>'),
            'https://eventrix.hu/apitest/crm' => $this->xmlResponse('<response><contacts><item><contact_id>77</contact_id><contact_email>customer@example.test</contact_email></item></contacts></response>'),
            'https://eventrix.hu/apitest/finance' => Http::sequence()
                ->push('<response><invoice_id>123</invoice_id></response>', 200, $this->xmlHeaders())
                ->push('<response><success>failed</success></response>', 200, $this->xmlHeaders()),
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
            'https://eventrix.hu/apitest/authenticate' => $this->xmlResponse('<response><session_key>session-1</session_key></response>'),
            'https://eventrix.hu/apitest/crm' => $this->xmlResponse('<response><error><code>5001</code><description><![CDATA[CRM failure]]></description></error></response>'),
        ]);

        $result = (new BillingService())->issueInvoice($this->purchase());

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('CRM failure', $result['error']);
    }

    public function test_client_sends_xml_and_does_not_hash_password_hash(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => Http::response('plain-text-session-key'),
        ]);

        $sessionKey = (new Co3Client())->authenticate();

        $this->assertSame('plain-text-session-key', $sessionKey);

        Http::assertSent(fn ($request) => $this->assertXmlRequest($request, 'https://eventrix.hu/apitest/authenticate', 'authenticate')
            && $this->xmlValue($request, 'authenticate/api_key') === 'api-key-from-env'
            && $this->xmlValue($request, 'authenticate/username') === 'integracio'
            && $this->xmlValue($request, 'authenticate/password') === 'hash-from-env');
    }

    public function test_xml_error_response_is_detected_with_http_200(): void
    {
        Http::fake([
            'https://eventrix.hu/apitest/authenticate' => $this->xmlResponse('<response><session_key>session-1</session_key></response>'),
            'https://eventrix.hu/apitest/crm' => $this->xmlResponse('<response><error><code>2</code><description><![CDATA[XML syntax error Start tag expected, \'<\' not found Line: 1 Column: 1]]></description></error></response>'),
        ]);

        $result = (new BillingService())->issueInvoice($this->purchase());

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('XML syntax error', $result['error']);
    }

    public function test_postman_collection_contains_required_co3_xml_fields(): void
    {
        $collection = json_decode(file_get_contents(base_path('docs/postman/co3-eventrix.postman_collection.json')), true);
        $environment = json_decode(file_get_contents(base_path('docs/postman/co3-eventrix-test.postman_environment.json')), true);

        $environmentKeys = collect($environment['values'])->pluck('key')->all();

        foreach (['baseUrl', 'username', 'passwordHash', 'apiKey', 'sessionKey', 'searchTerm', 'contactOwner', 'contactCategory', 'selectedAccount', 'contactId', 'invoiceId'] as $key) {
            $this->assertContains($key, $environmentKeys);
        }

        $requests = collect($collection['item'])->keyBy('name');

        $authenticateScript = implode("\n", $requests['01 Authenticate']['event'][0]['script']['exec']);
        $this->assertStringContainsString('pm.response.text().trim()', $authenticateScript);
        $this->assertStringNotContainsString("response is XML", $authenticateScript);
        $this->assertStringContainsString("pm.environment.set('sessionKey', raw)", $authenticateScript);

        $searchXml = $requests['02 CRM - Search contact - getContactList']['request']['body']['raw'];
        $this->assertXmlBodyContains($searchXml, 'getContactList', 'search_term', '{{searchTerm}}');

        $contactXml = $requests['03 CRM - Create contact - setContact']['request']['body']['raw'];
        $this->assertXmlBodyContains($contactXml, 'setContact', 'contact_owner', '{{contactOwner}}');
        $this->assertXmlBodyContains($contactXml, 'setContact', 'contact_categories', '{{contactCategory}}');

        $getContactXml = $requests['04 CRM - Get contact - getContact']['request']['body']['raw'];
        $this->assertXmlBodyContains($getContactXml, 'getContact', 'contact_id', '{{contactId}}');

        $invoiceXml = $requests['05 Finance - Create invoice - setInvoice']['request']['body']['raw'];
        $this->assertXmlBodyContains($invoiceXml, 'setInvoice', 'selected_account', '{{selectedAccount}}');

        $generateEvents = collect($requests['06 Finance - Generate invoice / send to NAV - generateInvoice']['event']);
        $preRequest = $generateEvents->firstWhere('listen', 'prerequest');
        $this->assertNotNull($preRequest);
        $this->assertStringContainsString("Missing required Postman environment variable: invoiceId", implode("\n", $preRequest['script']['exec']));
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

    private function xmlResponse(string $body)
    {
        return Http::response($body, 200, $this->xmlHeaders());
    }

    private function xmlHeaders(): array
    {
        return ['Content-Type' => 'text/xml; charset=utf-8'];
    }

    private function assertXmlRequest($request, string $url, string $method): bool
    {
        if ($request->url() !== $url) {
            return false;
        }

        if (! $request->hasHeader('Content-Type', 'text/xml; charset=utf-8')
            || ! $request->hasHeader('Accept', 'text/xml')
            || ! $request->hasHeader('Connection', 'close')) {
            return false;
        }

        $document = $this->requestDocument($request);

        return $document !== null
            && $document->documentElement?->nodeName === 'command'
            && $document->getElementsByTagName($method)->length === 1
            && ! str_contains($request->body(), '"command"');
    }

    private function xmlValue($request, string $path): ?string
    {
        $document = $this->requestDocument($request);

        if ($document === null) {
            return null;
        }

        $xpath = new \DOMXPath($document);
        $node = $xpath->query('/command/' . $path)->item(0);

        return $node?->textContent;
    }

    private function requestDocument($request): ?\DOMDocument
    {
        $document = new \DOMDocument();

        return @$document->loadXML($request->body()) ? $document : null;
    }

    private function requestMethodName($request): ?string
    {
        $document = $this->requestDocument($request);

        return $document?->documentElement?->firstElementChild?->nodeName;
    }

    private function assertXmlBodyContains(string $xml, string $method, string $field, string $value): void
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($xml));

        $xpath = new \DOMXPath($document);
        $this->assertSame('command', $document->documentElement?->nodeName);
        $this->assertSame($value, $xpath->query("/command/{$method}/{$field}")->item(0)?->textContent);
    }
}
