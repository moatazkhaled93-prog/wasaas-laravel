# wasaas-php

Official PHP/Laravel SDK for the [Wasaas WhatsApp API](https://wasaas.org).

Send WhatsApp messages, receive real signed webhooks, validate numbers, and manage sessions — with a clean PHP interface and a first-class Laravel integration. No Meta Business account or app review required: connect a regular WhatsApp number via QR code.

Full endpoint reference (auth, fields, error codes): [wasaas.org/docs](https://wasaas.org/docs?utm_source=github&utm_medium=readme&utm_campaign=wasaas-laravel).

## Quickstart (5 minutes)

**1. Install**

- PHP 8.1+, `ext-curl`, Laravel 10+ *(optional — plain PHP works too)*

```bash
composer require wasaas/wasaas-php
```

**2. Auth** — create an API key

Sign up at [wasaas.org](https://wasaas.org/register?utm_source=github&utm_medium=readme&utm_campaign=wasaas-laravel) — [7-day free trial, no card required](https://wasaas.org?ref=github-wasaas-laravel). Then go to **API Keys → Create API Key** in the dashboard. Keys look like `wsa_...` and are shown only once. See [pricing](https://wasaas.org/pricing) for plan limits.

In Laravel, publish the config and set the key in `.env`:

```bash
php artisan vendor:publish --tag=wasaas-config
```

```env
WASAAS_API_KEY=wsa_your_key_here
```

**3. Connect** — link a WhatsApp number

Go to **Sessions → Add Session** in the dashboard and scan the QR code with the WhatsApp number you want to send/receive from. Copy its Session ID (e.g. `customer-5-1`), or fetch it in code:

```php
$sessions = Wasaas::sessions()->list();
$sessionId = $sessions[0]['session_id']; // e.g. "customer-5-1"
```

## Your first message

```php
Wasaas::messages()->sendText(
    sessionId: $sessionId,
    to:        '966501234567', // digits only, with country code
    message:   'Hello from Wasaas! 👋',
);
// ['success' => true, 'to' => '966501234567', 'status' => 'sent']
```

---

## Usage — Laravel (Facade)

```php
use Wasaas\Laravel\Facades\Wasaas;

// Send a text message
Wasaas::messages()->sendText(
    sessionId: 'my-session',
    to:        '966501234567',
    message:   'Your order #1234 has shipped! 🚚',
);

// Send an image
Wasaas::messages()->sendImage(
    sessionId: 'my-session',
    to:        '966501234567',
    imageUrl:  'https://example.com/invoice.png',
    caption:   'Invoice #1234',
);

// List connected sessions
$sessions = Wasaas::sessions()->list();

// Check if a number has WhatsApp
$result = Wasaas::numbers()->validate('my-session', '966501234567');
// ['exists' => true, 'phone' => '966501234567']
```

## Usage — Laravel (Dependency Injection)

```php
use Wasaas\WasaasClient;

class OrderController extends Controller
{
    public function __construct(private readonly WasaasClient $wasaas) {}

    public function notify(Order $order): void
    {
        $this->wasaas->messages->sendText(
            sessionId: config('wasaas.session_id'),
            to:        $order->customer_phone,
            message:   "Order #{$order->id} confirmed! We'll notify you when it ships.",
        );
    }
}
```

## Usage — Plain PHP (no Laravel)

```php
use Wasaas\WasaasClient;

$client = new WasaasClient(apiKey: 'wsa_your_key_here');

// Send text
$client->messages->sendText(
    sessionId: 'my-session',
    to:        '966501234567',
    message:   'Hello from PHP!',
);

// Send document
$base64 = base64_encode(file_get_contents('/path/to/invoice.pdf'));

$client->messages->sendDocument(
    sessionId: 'my-session',
    to:        '966501234567',
    base64:    $base64,
    filename:  'invoice-1234.pdf',
    mime:      'application/pdf',
    caption:   'Your invoice is attached',
);
```

---

## API reference

### `messages`

| Method | Parameters | Description |
|--------|-----------|-------------|
| `sendText()` | `sessionId, to, message` | Send a plain text message |
| `sendImage()` | `sessionId, to, imageUrl, caption?` | Send an image by URL |
| `sendDocument()` | `sessionId, to, base64, filename, mime, caption?` | Send a document |

### `sessions`

| Method | Parameters | Description |
|--------|-----------|-------------|
| `list()` | — | List all connected WhatsApp sessions |

### `numbers`

| Method | Parameters | Description |
|--------|-----------|-------------|
| `validate()` | `sessionId, phone` | Check if a number has WhatsApp |

---

## Error handling

```php
use Wasaas\Exceptions\WasaasApiException;
use Wasaas\Exceptions\WasaasException;

try {
    $client->messages->sendText('my-session', '966501234567', 'Hello!');
} catch (WasaasApiException $e) {
    $e->getStatusCode();  // 401 | 404 | 429 | 500
    $e->getMessage();     // "Invalid API key" | "Session not found" | ...
    $e->getDetail();      // optional server detail
} catch (WasaasException $e) {
    // connection error, invalid apiKey format, etc.
}
```

**Common status codes:**

| Code | Meaning |
|------|---------|
| `401` | Invalid or revoked API key |
| `404` | Session not found |
| `429` | Quota exceeded — upgrade your plan |
| `500` | WhatsApp session error |

---

## Webhooks

Outbound webhook delivery is live in production. Register an HTTPS endpoint in the [Wasaas dashboard](https://wasaas.org/customer/webhooks) under **Customer → Webhooks**, and Wasaas signs and POSTs you an event for `message.received`, `message.status` (a send finished), or `session.status_changed`. The signing secret is shown once, at creation time.

Every delivery carries the envelope below plus an `X-Wasaas-Signature: t=<unix_timestamp>,v1=<hex_hmac>` header. Verify it against the **raw** request body before trusting the payload. The SDK's config only covers API auth (`api_key`, `base_url`) — a webhook's signing secret is separate (one per registered webhook URL), so store it yourself, e.g. `WASAAS_WEBHOOK_SECRET` in `.env`:

```php
Route::post('/wasaas-webhook', function (Illuminate\Http\Request $request) {
    $secret = env('WASAAS_WEBHOOK_SECRET');

    $header = $request->header('X-Wasaas-Signature', '');
    parse_str(str_replace(',', '&', $header), $parts); // "t=...,v1=..." -> ['t' => ..., 'v1' => ...]

    $timestamp = (int) ($parts['t'] ?? 0);
    if ($timestamp === 0 || abs(time() - $timestamp) > 300) {
        abort(401, 'invalid or expired signature');
    }

    $expected = hash_hmac('sha256', "{$timestamp}.{$request->getContent()}", $secret);
    if (! hash_equals($expected, $parts['v1'] ?? '')) {
        abort(401, 'invalid signature');
    }

    $payload = $request->json()->all();
    // $payload['id'] is the idempotency key — dedupe retries on it

    if ($payload['type'] === 'message.received') {
        logger('Inbound message', $payload['data']); // see docs for the per-event data shape
        // trigger your reply logic here
    }

    return response()->noContent(); // must be 2xx within 8s, or Wasaas retries up to 5x
});
```

Payload envelope:

```json
{
  "id": "3f2a2e9e-...-unique-per-event",
  "type": "message.status",
  "created_at": "2026-11-08T12:00:00.000Z",
  "data": { "session_id": "customer-5-1", "to": "966501234567", "status": "sent" }
}
```

Full envelope shape, retry/backoff timing, and the `endpoint.verification` handshake sent when you first register a URL are documented at [wasaas.org/docs#webhooks](https://wasaas.org/docs?utm_source=github&utm_medium=readme&utm_campaign=wasaas-laravel#webhooks).

---

## Get started

**[Free 7-day trial, no card required →](https://wasaas.org?ref=github-wasaas-laravel)**

Full docs: **[wasaas.org/docs](https://wasaas.org/docs?utm_source=github&utm_medium=readme&utm_campaign=wasaas-laravel)**

---

## License

MIT — see [LICENSE](LICENSE) for details.

Made with ❤️ by [Lexora](https://lexora.fyi)
