<?php

namespace App\Services\Co3;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Co3Client
{
    private const SESSION_CACHE_KEY = 'co3.session_key';

    public function authenticate(bool $force = false): ?string
    {
        if ($force) {
            Cache::forget(self::SESSION_CACHE_KEY);
        }

        return Cache::remember(self::SESSION_CACHE_KEY, 240, function () {
            $this->ensureConfigured(['username', 'password_hash']);

            $response = $this->post('authenticate', 'authenticate', [
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
                throw new RuntimeException('CO3 authentication did not return a session key.');
            }

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
        $sessionKey = $this->authenticate();
        unset($payload['session_key']);

        if (filled($sessionKey)) {
            $payload = array_merge(['session_key' => $sessionKey], $payload);
        }

        try {
            return $this->post($endpoint, $method, $payload);
        } catch (Co3Exception $exception) {
            if ($retry && $exception->isSessionError()) {
                Cache::forget(self::SESSION_CACHE_KEY);

                return $this->authenticatedRequest($endpoint, $method, $payload, false);
            }

            throw $exception;
        }
    }

    private function post(string $endpoint, string $method, array $payload, bool $checkApiErrors = true): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->post($this->url($endpoint), $this->commandPayload($method, $payload));

        $data = $this->decodeJsonResponse($response, $method);

        if ($response->failed()) {
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

    private function commandPayload(string $method, array $payload): array
    {
        return [
            'command' => [
                $method => $payload,
            ],
        ];
    }

    private function decodeJsonResponse(Response $response, string $method): array
    {
        $data = $response->json();

        if (is_array($data)) {
            return $data;
        }

        throw new Co3Exception('CO3 returned a non-JSON response during ' . $method . '.', null, $method);
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
                throw new RuntimeException("Missing CO3 configuration: co3.{$key}");
            }
        }
    }
}
