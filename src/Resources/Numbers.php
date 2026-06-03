<?php

namespace Wasaas\Resources;

use Wasaas\Http\WasaasHttp;

class Numbers
{
    public function __construct(private readonly WasaasHttp $http) {}

    public function validate(string $sessionId, string $phone): array
    {
        return $this->http->post('/api/v1/check-number', [
            'session_id' => $sessionId,
            'phone'      => $phone,
        ]);
    }
}
