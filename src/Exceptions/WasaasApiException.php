<?php

namespace Wasaas\Exceptions;

class WasaasApiException extends WasaasException
{
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly ?string $detail = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }
}
