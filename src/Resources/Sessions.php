<?php

namespace Wasaas\Resources;

use Wasaas\Http\WasaasHttp;

class Sessions
{
    public function __construct(private readonly WasaasHttp $http) {}

    public function list(): array
    {
        return $this->http->get('/api/v1/sessions');
    }
}
