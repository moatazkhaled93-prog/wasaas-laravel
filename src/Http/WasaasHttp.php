<?php

namespace Wasaas\Http;

use Wasaas\Exceptions\WasaasApiException;
use Wasaas\Exceptions\WasaasException;

class WasaasHttp
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    /**
     * @throws WasaasApiException
     * @throws WasaasException
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @throws WasaasApiException
     * @throws WasaasException
     */
    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($method === 'POST' && ! empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new WasaasException('cURL error: ' . $error);
        }

        $data = json_decode($response, true) ?? [];

        if ($status < 200 || $status >= 300) {
            throw new WasaasApiException(
                $status,
                $data['error'] ?? "HTTP {$status}",
                $data['detail'] ?? null,
            );
        }

        return $data;
    }
}
