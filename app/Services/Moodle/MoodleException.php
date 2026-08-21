<?php

namespace App\Services\Moodle;

use RuntimeException;

class MoodleException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $moodleErrorCode = null,
        private readonly ?string $moodleException = null,
        private readonly ?string $moodleFunction = null,
        private readonly ?int $status = null,
    ) {
        parent::__construct($message, $status ?? 0);
    }

    public function moodleErrorCode(): ?string
    {
        return $this->moodleErrorCode;
    }

    public function moodleException(): ?string
    {
        return $this->moodleException;
    }

    public function moodleFunction(): ?string
    {
        return $this->moodleFunction;
    }

    public function status(): ?int
    {
        return $this->status;
    }
}
