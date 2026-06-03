<?php

namespace Wasaas;

use Wasaas\Exceptions\WasaasException;
use Wasaas\Http\WasaasHttp;
use Wasaas\Resources\Messages;
use Wasaas\Resources\Numbers;
use Wasaas\Resources\Sessions;

class WasaasClient
{
    public readonly Messages $messages;
    public readonly Sessions $sessions;
    public readonly Numbers  $numbers;

    public function __construct(string $apiKey, string $baseUrl = 'https://wasaas.org')
    {
        if (empty($apiKey)) {
            throw new WasaasException('apiKey is required.');
        }

        if (! str_starts_with($apiKey, 'wsa_')) {
            throw new WasaasException("apiKey must start with 'wsa_'.");
        }

        $http = new WasaasHttp($apiKey, $baseUrl);

        $this->messages = new Messages($http);
        $this->sessions = new Sessions($http);
        $this->numbers  = new Numbers($http);
    }
}
