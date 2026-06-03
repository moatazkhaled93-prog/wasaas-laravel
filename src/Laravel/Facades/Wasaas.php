<?php

namespace Wasaas\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Wasaas\WasaasClient;

/**
 * @method static array sendText(string $sessionId, string $to, string $message)
 * @method static array sendImage(string $sessionId, string $to, string $imageUrl, ?string $caption = null)
 * @method static array sendDocument(string $sessionId, string $to, string $base64, string $filename, string $mime, ?string $caption = null)
 *
 * @see WasaasClient
 */
class Wasaas extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wasaas';
    }
}
