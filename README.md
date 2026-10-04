# wasaas-php

Official PHP/Laravel SDK for the [Wasaas WhatsApp API](https://wasaas.org).

Send WhatsApp messages, validate numbers, and manage sessions — with a clean PHP interface and a first-class Laravel integration.

## Requirements

- PHP 8.1+
- ext-curl
- Laravel 10+ *(optional — plain PHP works too)*

## Install

```bash
composer require wasaas/wasaas-php
```

## Laravel setup

Publish the config file:

```bash
php artisan vendor:publish --tag=wasaas-config
```

Add your API key to `.env`:

```env
WASAAS_API_KEY=wsa_your_key_here
```

Get your API key at [wasaas.org](https://wasaas.org) — plans start at $29/month with a 7-day money-back guarantee.

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

## License

MIT — see [LICENSE](LICENSE) for details.

Made with ❤️ by [Lexora](https://lexora.fyi)
