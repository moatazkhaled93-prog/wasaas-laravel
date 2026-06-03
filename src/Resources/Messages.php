<?php

namespace Wasaas\Resources;

use Wasaas\Http\WasaasHttp;

class Messages
{
    public function __construct(private readonly WasaasHttp $http) {}

    public function sendText(string $sessionId, string $to, string $message): array
    {
        return $this->http->post('/api/v1/messages', [
            'session_id' => $sessionId,
            'to'         => $to,
            'message'    => $message,
        ]);
    }

    public function sendImage(string $sessionId, string $to, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'session_id' => $sessionId,
            'to'         => $to,
            'image_url'  => $imageUrl,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->http->post('/api/v1/messages/image', $payload);
    }

    public function sendDocument(
        string $sessionId,
        string $to,
        string $base64,
        string $filename,
        string $mime,
        ?string $caption = null,
    ): array {
        $payload = [
            'session_id' => $sessionId,
            'to'         => $to,
            'base64'     => $base64,
            'filename'   => $filename,
            'mime'       => $mime,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->http->post('/api/v1/messages/document', $payload);
    }
}
