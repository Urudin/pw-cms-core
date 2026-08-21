<?php

namespace App\Services\Moodle;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MoodleClient
{
    private const REST_ENDPOINT = '/webservice/rest/server.php';

    public function call(string $function, array $parameters = []): mixed
    {
        if (blank($function)) {
            throw new MoodleException('Missing Moodle Web Service function name.');
        }

        $this->ensureConfigured($function);

        Log::debug('Moodle request preparing', [
            'function' => $function,
            'url' => $this->endpointUrl(),
            'format' => $this->restFormat(),
            'parameters' => $this->redactPayload($parameters),
        ]);

        $payload = array_merge($parameters, $this->commonParameters($function));

        try {
            $response = Http::timeout($this->timeout())
                ->asForm()
                ->acceptJson()
                ->post($this->endpointUrl(), $payload);
        } catch (ConnectionException $exception) {
            Log::error('Moodle request failed', [
                'function' => $function,
                'exception' => get_class($exception),
                'message' => $this->redactString($exception->getMessage()),
            ]);

            throw new MoodleException(
                'Moodle request failed during ' . $function . ': ' . $this->redactString($exception->getMessage()),
                moodleFunction: $function,
            );
        } catch (Throwable $exception) {
            Log::error('Moodle request failed unexpectedly', [
                'function' => $function,
                'exception' => get_class($exception),
                'message' => $this->redactString($exception->getMessage()),
            ]);

            throw new MoodleException(
                'Moodle request failed during ' . $function . ': ' . $this->redactString($exception->getMessage()),
                moodleFunction: $function,
            );
        }

        $data = $this->decodeResponse($response, $function);

        Log::debug('Moodle response received', [
            'function' => $function,
            'status' => $response->status(),
            'response_type' => get_debug_type($data),
            'response_keys' => is_array($data) ? $this->topLevelKeys($data) : [],
            'body_length' => strlen((string) $response->body()),
        ]);

        if ($response->failed()) {
            $details = $this->errorDetails($data) ?? $this->httpErrorDetails($data, $response);
            $message = $details['message'];

            Log::error('Moodle HTTP error response', [
                'function' => $function,
                'status' => $response->status(),
                'moodle_error_code' => $details['error_code'] ?? null,
                'moodle_exception' => $details['exception'] ?? null,
                'message' => $message,
            ]);

            throw new MoodleException(
                'Moodle HTTP error during ' . $function . ': ' . $response->status() . ' ' . $message,
                $details['error_code'] ?? null,
                $details['exception'] ?? null,
                $function,
                $response->status(),
            );
        }

        $this->throwIfApiError($data, $function);

        return $data;
    }

    private function commonParameters(string $function): array
    {
        return [
            'wstoken' => config('moodle.web_service_token'),
            'wsfunction' => $function,
            'moodlewsrestformat' => $this->restFormat(),
        ];
    }

    private function decodeResponse(Response $response, string $function): mixed
    {
        $body = trim((string) $response->body());

        if ($body === '') {
            if ($response->failed()) {
                return null;
            }

            Log::warning('Moodle returned empty response body', [
                'function' => $function,
                'status' => $response->status(),
            ]);

            throw new MoodleException(
                'Moodle returned an empty JSON response during ' . $function . '.',
                moodleFunction: $function,
                status: $response->status(),
            );
        }

        $data = $response->json();

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($response->failed()) {
                return null;
            }

            Log::warning('Moodle returned non-JSON response body', [
                'function' => $function,
                'status' => $response->status(),
                'body_prefix' => $this->redactString(substr($body, 0, 120)),
            ]);

            throw new MoodleException(
                'Moodle returned a non-JSON response during ' . $function . '.',
                moodleFunction: $function,
                status: $response->status(),
            );
        }

        return $data;
    }

    private function throwIfApiError(mixed $data, string $function): void
    {
        $details = $this->errorDetails($data);

        if ($details === null) {
            return;
        }

        Log::error('Moodle API error response', [
            'function' => $function,
            'moodle_error_code' => $details['error_code'],
            'moodle_exception' => $details['exception'],
            'message' => $details['message'],
        ]);

        throw new MoodleException(
            'Moodle API error during ' . $function . ': ' . $details['message'],
            $details['error_code'],
            $details['exception'],
            $function,
        );
    }

    private function errorDetails(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        if (! Arr::has($data, 'exception') && ! Arr::has($data, 'errorcode') && ! Arr::has($data, 'error')) {
            return null;
        }

        $error = Arr::get($data, 'error');
        $message = $this->stringValue(Arr::get($data, 'message'));
        $errorCode = $this->stringValue(Arr::get($data, 'errorcode'));
        $exception = $this->stringValue(Arr::get($data, 'exception'));

        if (is_array($error)) {
            $message ??= $this->stringValue($error['message'] ?? $error['description'] ?? $error['error'] ?? null);
            $errorCode ??= $this->stringValue($error['errorcode'] ?? $error['code'] ?? null);
            $exception ??= $this->stringValue($error['exception'] ?? null);
        } elseif ($message === null) {
            $message = $this->stringValue($error);
        }

        return [
            'message' => $this->redactString($message ?? 'Moodle API error'),
            'error_code' => $errorCode,
            'exception' => $exception,
        ];
    }

    private function httpErrorDetails(mixed $data, Response $response): array
    {
        $message = is_array($data)
            ? $this->stringValue(Arr::get($data, 'message'))
            : null;

        return [
            'message' => $this->redactString($message ?? ('HTTP ' . $response->status())),
            'error_code' => null,
            'exception' => null,
        ];
    }

    private function ensureConfigured(string $function): void
    {
        if (! (bool) config('moodle.enabled', false)) {
            throw new MoodleException('Moodle integration is disabled.', moodleFunction: $function);
        }

        foreach (['base_url', 'web_service_token', 'rest_format'] as $key) {
            if (blank(config("moodle.{$key}"))) {
                Log::error('Missing Moodle configuration', [
                    'config_key' => "moodle.{$key}",
                ]);

                throw new MoodleException(
                    "Missing Moodle configuration: moodle.{$key}",
                    moodleFunction: $function,
                );
            }
        }

        if ($this->timeout() < 1) {
            Log::error('Invalid Moodle configuration', [
                'config_key' => 'moodle.timeout',
            ]);

            throw new MoodleException(
                'Invalid Moodle configuration: moodle.timeout',
                moodleFunction: $function,
            );
        }
    }

    private function endpointUrl(): string
    {
        return rtrim((string) config('moodle.base_url'), '/') . self::REST_ENDPOINT;
    }

    private function restFormat(): string
    {
        return (string) config('moodle.rest_format', 'json');
    }

    private function timeout(): int
    {
        return (int) config('moodle.timeout', 30);
    }

    private function redactPayload(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if (str_contains(strtolower((string) $key), 'token') || strtolower((string) $key) === 'wstoken') {
                $redacted[$key] = filled($value) ? '[redacted]' : '';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redactPayload($value) : $this->redactString($value);
        }

        return $redacted;
    }

    private function redactString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $token = (string) config('moodle.web_service_token', '');

        if ($token === '') {
            return $value;
        }

        return str_replace($token, '[redacted]', $value);
    }

    private function stringValue(mixed $value): ?string
    {
        if (! filled($value) || is_array($value)) {
            return null;
        }

        return (string) $value;
    }

    private function topLevelKeys(array $data): array
    {
        return array_slice(array_keys($data), 0, 20);
    }
}
