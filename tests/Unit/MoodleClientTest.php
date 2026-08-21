<?php

namespace Tests\Unit;

use App\Services\Moodle\MoodleClient;
use App\Services\Moodle\MoodleException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MoodleClientTest extends TestCase
{
    private const BASE_URL = 'https://elearning.example.test';
    private const ENDPOINT = self::BASE_URL . '/webservice/rest/server.php';
    private const TOKEN = 'super-secret-moodle-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'moodle.enabled' => true,
            'moodle.base_url' => self::BASE_URL,
            'moodle.web_service_token' => self::TOKEN,
            'moodle.rest_format' => 'json',
            'moodle.timeout' => 15,
        ]);
    }

    public function test_successful_json_response_is_returned(): void
    {
        Http::fake([
            self::ENDPOINT => Http::response([
                'status' => 'ok',
                'items' => [
                    ['id' => 123, 'name' => 'Example'],
                ],
            ]),
        ]);

        $result = (new MoodleClient())->call('local_test_echo', [
            'field' => 'email',
            'values' => ['applicant@example.test'],
        ]);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(123, $result['items'][0]['id']);

        Http::assertSentCount(1);
    }

    public function test_request_uses_post_endpoint_and_common_moodle_parameters(): void
    {
        Http::fake([
            self::ENDPOINT => Http::response(['status' => 'ok']),
        ]);

        (new MoodleClient())->call('local_test_echo', [
            'field' => 'email',
            'values' => ['applicant@example.test'],
        ]);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === self::ENDPOINT
            && $request->isForm()
            && $request->hasHeader('Accept', 'application/json')
            && $request['wstoken'] === self::TOKEN
            && $request['wsfunction'] === 'local_test_echo'
            && $request['moodlewsrestformat'] === 'json'
            && $request['field'] === 'email'
            && $request['values'][0] === 'applicant@example.test');
    }

    public function test_moodle_api_error_response_is_normalized(): void
    {
        Http::fake([
            self::ENDPOINT => Http::response([
                'exception' => 'invalid_parameter_exception',
                'errorcode' => 'invalidparameter',
                'message' => 'Invalid parameter value detected',
            ]),
        ]);

        try {
            (new MoodleClient())->call('local_test_error');
            $this->fail('Expected MoodleException was not thrown.');
        } catch (MoodleException $exception) {
            $this->assertSame('invalidparameter', $exception->moodleErrorCode());
            $this->assertSame('invalid_parameter_exception', $exception->moodleException());
            $this->assertSame('local_test_error', $exception->moodleFunction());
            $this->assertNull($exception->status());
            $this->assertStringContainsString('Invalid parameter value detected', $exception->getMessage());
        }
    }

    public function test_http_server_failure_is_normalized(): void
    {
        Http::fake([
            self::ENDPOINT => Http::response([
                'message' => 'Service unavailable',
            ], 503),
        ]);

        try {
            (new MoodleClient())->call('local_test_server_failure');
            $this->fail('Expected MoodleException was not thrown.');
        } catch (MoodleException $exception) {
            $this->assertSame(503, $exception->status());
            $this->assertSame('local_test_server_failure', $exception->moodleFunction());
            $this->assertStringContainsString('503 Service unavailable', $exception->getMessage());
        }
    }

    public function test_missing_configuration_throws_before_sending_request(): void
    {
        Log::spy();
        Http::fake();

        config([
            'moodle.web_service_token' => null,
        ]);

        try {
            (new MoodleClient())->call('local_test_missing_config');
            $this->fail('Expected MoodleException was not thrown.');
        } catch (MoodleException $exception) {
            $this->assertSame('local_test_missing_config', $exception->moodleFunction());
            $this->assertStringContainsString('Missing Moodle configuration: moodle.web_service_token', $exception->getMessage());
        }

        Http::assertSentCount(0);
        Log::shouldHaveReceived('error')->withArgs(fn ($message, array $context = []) => $message === 'Missing Moodle configuration'
            && ($context['config_key'] ?? null) === 'moodle.web_service_token');
    }

    public function test_token_is_not_exposed_in_logs_or_exception_messages(): void
    {
        Log::spy();

        Http::fake([
            self::ENDPOINT => Http::response([
                'exception' => 'moodle_exception',
                'errorcode' => 'invalidtoken',
                'message' => 'Invalid token ' . self::TOKEN,
            ]),
        ]);

        try {
            (new MoodleClient())->call('local_test_secret_error', [
                'client_token' => self::TOKEN,
            ]);
            $this->fail('Expected MoodleException was not thrown.');
        } catch (MoodleException $exception) {
            $this->assertStringNotContainsString(self::TOKEN, $exception->getMessage());
            $this->assertStringContainsString('[redacted]', $exception->getMessage());
        }

        Log::shouldHaveReceived('error')->withArgs(fn ($message, array $context = []) => $message === 'Moodle API error response'
            && ($context['moodle_error_code'] ?? null) === 'invalidtoken'
            && ! $this->containsToken($message, $context)
            && ($context['message'] ?? null) === 'Invalid token [redacted]');

        Log::shouldHaveReceived('debug')->withArgs(fn ($message, array $context = []) => $message === 'Moodle request preparing'
            && ! $this->containsToken($message, $context)
            && ($context['parameters']['client_token'] ?? null) === '[redacted]');

        Log::shouldHaveReceived('debug')->withArgs(fn ($message, array $context = []) => $message === 'Moodle response received'
            && ! $this->containsToken($message, $context));
    }

    private function containsToken(string $message, array $context): bool
    {
        $encodedContext = json_encode($context);

        return str_contains($message, self::TOKEN)
            || ($encodedContext !== false && str_contains($encodedContext, self::TOKEN));
    }
}
