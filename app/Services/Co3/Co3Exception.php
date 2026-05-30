<?php

namespace App\Services\Co3;

use RuntimeException;

class Co3Exception extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $co3Code = null,
        private readonly ?string $method = null,
    ) {
        parent::__construct($message, $co3Code ?? 0);
    }

    public function co3Code(): ?int
    {
        return $this->co3Code;
    }

    public function method(): ?string
    {
        return $this->method;
    }

    public function isSessionError(): bool
    {
        if (in_array($this->co3Code, [6, 10], true)) {
            return true;
        }

        return str_contains(strtolower($this->getMessage()), 'session');
    }
}
