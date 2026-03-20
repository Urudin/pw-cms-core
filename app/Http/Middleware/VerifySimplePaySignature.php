<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Netipar\SimplePay\Enums\Currency;
use Netipar\SimplePay\Http\Middleware\VerifySimplePaySignature as BaseVerifySimplePaySignature;
use Netipar\SimplePay\Support\MerchantResolver;
use Netipar\SimplePay\Support\Signature;
use Symfony\Component\HttpFoundation\Response;

class VerifySimplePaySignature extends BaseVerifySimplePaySignature
{
    public function __construct(
        private readonly MerchantResolver $merchantResolver,
    ) {
        parent::__construct($this->merchantResolver);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('Signature');

        if (! $signature) {
            abort(401, 'Missing SimplePay signature');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        if (! is_array($data)) {
            abort(401, 'Invalid SimplePay request body');
        }

        $currency = isset($data['currency'])
            ? Currency::from($data['currency'])
            : Currency::HUF;

        $secretKey = $this->merchantResolver->getSecretKey($currency);

        if (! Signature::verify($secretKey, $body, $signature)) {
            abort(401, 'Invalid SimplePay signature');
        }

        $request->merge([
            'currency' => 'HUF',
        ]);

        return $next($request);
    }
}
