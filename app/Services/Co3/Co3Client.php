<?php

namespace App\Services\Co3;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use RuntimeException;

class Co3Client
{
    private const SESSION_CACHE_KEY = 'co3.session_key';
    private const XML_CONTENT_TYPE = 'text/xml; charset=utf-8';

    public function authenticate(bool $force = false): ?string
    {
        if ($force) {
            Cache::forget(self::SESSION_CACHE_KEY);
        }

        return Cache::remember(self::SESSION_CACHE_KEY, 240, function () {
            $this->ensureConfigured(['api_key', 'username', 'password_hash']);

            Log::info('CO3 authentication request started', [
                'endpoint' => 'authenticate',
                'username_configured' => filled(config('co3.username')),
                'api_key_configured' => filled(config('co3.api_key')),
            ]);

            $response = $this->post('authenticate', 'authenticate', [
                'api_key' => config('co3.api_key'),
                'username' => config('co3.username'),
                'password' => config('co3.password_hash'),
            ]);

            $sessionKey = $this->extractFirst($response, [
                'session_key',
                'sessionKey',
                'session',
                'token',
                'key',
            ]);

            if ($sessionKey === null) {
                Log::warning('CO3 authentication response missing session key', [
                    'response_keys' => $this->topLevelKeys($response),
                ]);

                throw new RuntimeException('CO3 authentication did not return a session key.');
            }

            Log::info('CO3 authentication succeeded', [
                'session_key_present' => true,
            ]);

            return $sessionKey;
        });
    }

    public function crm(string $method, array $payload): array
    {
        return $this->authenticatedRequest('crm', $method, $payload);
    }

    public function finance(string $method, array $payload): array
    {
        return $this->authenticatedRequest('finance', $method, $payload);
    }

    public function generateInvoice(array $payload): array
    {
        return $this->finance('generateInvoice', $payload);
    }

    public function extractFirst(array $response, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($response, $key);

            if (filled($value) && ! is_array($value)) {
                return (string) $value;
            }
        }

        foreach ($response as $value) {
            if (is_array($value)) {
                $found = $this->extractFirst($value, $keys);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function authenticatedRequest(string $endpoint, string $method, array $payload, bool $retry = true): array
    {
        $this->ensureConfigured(['api_key']);

        Log::debug('CO3 authenticated request preparing', [
            'endpoint' => $endpoint,
            'method' => $method,
            'retry_enabled' => $retry,
            'payload' => $this->redactPayload($payload),
        ]);

        $sessionKey = $this->authenticate();
        unset($payload['api_key']);
        unset($payload['session_key']);

        if (filled($sessionKey)) {
            $payload = array_merge([
                'api_key' => config('co3.api_key'),
                'session_key' => $sessionKey,
            ], $payload);
        } else {
            $payload = array_merge(['api_key' => config('co3.api_key')], $payload);
        }

        try {
            return $this->post($endpoint, $method, $payload);
        } catch (Co3Exception $exception) {
            if ($retry && $exception->isSessionError()) {
                Log::warning('CO3 session error detected, retrying after re-authentication', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'co3_code' => $exception->co3Code(),
                    'message' => $exception->getMessage(),
                ]);

                Cache::forget(self::SESSION_CACHE_KEY);

                return $this->authenticatedRequest($endpoint, $method, $payload, false);
            }

            Log::error('CO3 authenticated request failed', [
                'endpoint' => $endpoint,
                'method' => $method,
                'co3_code' => $exception->co3Code(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function post(string $endpoint, string $method, array $payload, bool $checkApiErrors = true): array
    {
        Log::debug('CO3 request sending', [
            'endpoint' => $endpoint,
            'method' => $method,
            'url' => $this->url($endpoint),
            'payload' => $this->redactPayload($payload),
        ]);

        $response = Http::withHeaders([
            'Accept' => 'text/xml',
            'Content-Type' => self::XML_CONTENT_TYPE,
            'Connection' => 'close',
        ])
            ->withBody($this->methodPayload($method, $payload), self::XML_CONTENT_TYPE)
            ->post($this->url($endpoint));

        $data = $this->decodeResponse($response, $method);

        Log::debug('CO3 response received', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $response->status(),
            'response_keys' => $this->topLevelKeys($data),
            'body_length' => strlen((string) $response->body()),
        ]);

        if ($response->failed()) {
            Log::error('CO3 HTTP error response', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'error_message' => $this->extractErrorMessage($data),
            ]);

            throw new Co3Exception(
                'CO3 HTTP error during ' . $method . ': ' . $response->status() . ' ' . $this->extractErrorMessage($data),
                $response->status(),
                $method,
            );
        }

        if ($checkApiErrors) {
            $this->throwIfApiError($data, $method);
        }

        return $data;
    }

    private function methodPayload(string $method, array $payload): string
    {
        $document = new \DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $command = $document->createElement('command');
        $document->appendChild($command);

        $methodElement = $document->createElement($method);
        $command->appendChild($methodElement);

        $this->appendXmlPayload($document, $methodElement, $payload);

        return $document->saveXML();
    }

    private function appendXmlPayload(\DOMDocument $document, \DOMElement $parent, array $payload): void
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $element = $document->createElement((string) $key);
                $parent->appendChild($element);

                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        $itemElement = $document->createElement('item');
                        $element->appendChild($itemElement);

                        if (is_array($item)) {
                            $this->appendXmlPayload($document, $itemElement, $item);
                        } else {
                            $itemElement->appendChild($document->createCDATASection((string) $item));
                        }
                    }

                    continue;
                }

                $this->appendXmlPayload($document, $element, $value);

                continue;
            }

            $element = $document->createElement((string) $key);
            $element->appendChild($document->createCDATASection((string) $value));
            $parent->appendChild($element);
        }
    }

    private function decodeResponse(Response $response, string $method): array
    {
        $body = trim($response->body());

        if ($body === '') {
            Log::warning('CO3 returned empty response body', [
                'method' => $method,
                'status' => $response->status(),
            ]);

            throw new Co3Exception('CO3 returned an empty XML response during ' . $method . '.', null, $method);
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $xml instanceof SimpleXMLElement && $method === 'authenticate') {
            Log::debug('CO3 authentication returned plain text session key', [
                'body_length' => strlen($body),
            ]);

            return [
                'response' => [
                    'session_key' => $body,
                ],
            ];
        }

        if (! $xml instanceof SimpleXMLElement) {
            Log::warning('CO3 returned non-XML response body', [
                'method' => $method,
                'status' => $response->status(),
                'body_prefix' => substr($body, 0, 120),
            ]);

            throw new Co3Exception('CO3 returned a non-XML response during ' . $method . '.', null, $method);
        }

        return [
            $xml->getName() => $this->xmlToArray($xml),
        ];
    }

    private function xmlToArray(SimpleXMLElement $element): array|string
    {
        $children = $element->children();

        if ($children->count() === 0) {
            return trim((string) $element);
        }

        $data = [];

        foreach ($children as $name => $child) {
            $value = $this->xmlToArray($child);

            if (array_key_exists($name, $data)) {
                if (! is_array($data[$name]) || ! array_is_list($data[$name])) {
                    $data[$name] = [$data[$name]];
                }

                $data[$name][] = $value;

                continue;
            }

            $data[$name] = $value;
        }

        return $data;
    }

    private function throwIfApiError(array $data, string $method): void
    {
        $error = Arr::get($data, 'error') ?? Arr::get($data, 'response.error');

        if (! is_array($error) && ! filled($error)) {
            return;
        }

        $code = is_array($error) ? (int) ($error['code'] ?? 0) : null;
        $message = is_array($error)
            ? (string) ($error['description'] ?? $error['message'] ?? 'CO3 API error')
            : (string) $error;

        Log::error('CO3 API error response', [
            'method' => $method,
            'co3_code' => $code,
            'message' => $message,
        ]);

        throw new Co3Exception('CO3 API error during ' . $method . ': ' . $message, $code, $method);
    }

    private function extractErrorMessage(array $data): string
    {
        $message = $this->extractFirst($data, ['description', 'message', 'error']);

        return $message ? trim($message) : '';
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('co3.base_url', 'https://eventrix.hu/apitest'), '/') . '/' . ltrim($endpoint, '/');
    }

    private function ensureConfigured(array $keys): void
    {
        foreach ($keys as $key) {
            if (blank(config("co3.{$key}"))) {
                Log::error('Missing CO3 configuration', [
                    'config_key' => "co3.{$key}",
                ]);

                throw new RuntimeException("Missing CO3 configuration: co3.{$key}");
            }
        }
    }

    private function redactPayload(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['api_key', 'session_key', 'password'], true)) {
                $redacted[$key] = filled($value) ? '[redacted]' : '';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redactPayload($value) : $value;
        }

        return $redacted;
    }

    private function topLevelKeys(array $data): array
    {
        return array_slice(array_keys($data), 0, 20);
    }
}
