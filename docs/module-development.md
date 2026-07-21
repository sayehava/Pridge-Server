# Endpoint Module Development Guide

This guide explains how to build a source-side integration module — a plugin for WordPress, Joomla, PrestaShop, Magento, OpenCart, a custom ERP, or any other system — that submits print jobs into Pridge Server. This is the same role played by an official "Pridge for WooCommerce" or "Pridge for Joomla" plugin would play: it watches for an event (a new order, an invoice, a label request) inside the host application and forwards the raw print data to a Pridge endpoint.

If you are building the desktop application that pulls jobs and sends them to a physical printer, see [`client-agent-development.md`](client-agent-development.md) instead. This guide is about the other side: getting print jobs *into* Pridge from a CMS, e-commerce platform, or any other application.

## Terminology

- **Endpoint**: a virtual printer created in the Pridge admin UI at `/endpoints`. Each endpoint has its own bearer token.
- **Module** (also called a plugin or connector): the code you are building. It lives inside the host application (WordPress, Joomla, PrestaShop, a custom backend, etc.) and calls the Pridge API.
- **Client**: the desktop/tray agent on the office computer that later pulls the job from Pridge and sends it to a real printer. Your module never talks to the client directly.

Your module's only job is to turn an event in the host application into an HTTP request to Pridge. Everything downstream (queueing, client assignment, printing) is handled by the server and the client agent.

## How a Module Fits Into the System

```
[Host app event]  --->  [Your module]  --->  POST /api/plugin/jobs  --->  [Pridge queue]  --->  [Client agent]  --->  [Physical printer]
```

For example, in a WooCommerce store: a customer places an order → your module builds an ESC/POS receipt or a PDF invoice → your module POSTs the raw bytes to Pridge with the endpoint token → the job sits in the queue as `pending` → a client agent assigned to that endpoint reserves and prints it.

## Prerequisites

Before writing any module code:

1. Install Pridge Server and confirm it is reachable over HTTPS.
2. Log in to the admin UI and create an endpoint at `/endpoints`. Give it a descriptive name, for example "Kitchen Receipt Printer" or "Shipping Label Printer".
3. Copy the endpoint token shown once at creation time. This token is what your module authenticates with. If it is lost, regenerate it from the same page — the old token stops working immediately.
4. Decide what raw format the destination printer expects (ESC/POS, ZPL, plain text, PDF, or another format). Pridge does not convert or interpret the payload; it stores and forwards exactly the bytes you send.

## Core API Contract

Full request/response details live in [`client-integration.md`](client-integration.md). The two routes a module needs are:

### Submit a print job

```http
POST /api/plugin/jobs
Authorization: Bearer ENDPOINT_TOKEN
Content-Type: application/octet-stream
X-Pridge-Metadata: {"source":"woocommerce","order_id":"1001"}
X-Pridge-Module-Version: 1.0.0

RAW_PRINT_PAYLOAD_BYTES
```

Response (`201 Created`):

```json
{
  "job_id": 123,
  "status": "pending",
  "server_version": "1.1.1",
  "compatibility_warning": null
}
```

Response on bad or missing token (`401 Unauthorized`):

```json
{
  "error": "Invalid endpoint token."
}
```

Response when the body is empty (`400 Bad Request`):

```json
{
  "error": "Print payload is required."
}
```

Rules:

- The request body is the raw payload, untouched. Do not JSON-wrap it, base64-encode it, or add a trailing newline unless the printer format requires one.
- Send the endpoint token as a bearer token in the `Authorization` header, never in a URL query string.
- Set `Content-Type` to the real payload type when known (`text/plain`, `application/pdf`, `image/png`). Use `application/octet-stream` for raw ESC/POS or ZPL data.
- `X-Pridge-Metadata` is optional. Keep it small and JSON-encoded — it is stored alongside the job and shown to the client agent and in the admin queue view, not used for routing.
- `X-Pridge-Module-Version` is optional. When sent, `compatibility_warning` in the response is a human-readable string telling you to update either your module or the server, only when the two major versions differ. It never blocks the job — treat it as something to log or surface to the site admin, not something to act on programmatically.

### List clients assigned to this endpoint (optional)

Use this only if your module needs to show the merchant which office computers will receive jobs from this endpoint, for example in a settings screen.

```http
POST /api/plugin/clients
Content-Type: application/json

{"token":"ENDPOINT_TOKEN"}
```

```json
{
  "endpoint": {"id": 1, "name": "Warehouse Label Printer"},
  "clients": [
    {"id": 2, "name": "Warehouse Laptop", "enabled": 1, "last_seen_at": "2026-07-09 10:15:00", "created_at": "2026-07-09 09:30:00"}
  ]
}
```

Most modules never need this route. Job submission alone is enough for printing to work.

## Building the Print Payload

Pridge is format-agnostic. Your module is responsible for producing the exact bytes the printer expects.

- **ESC/POS receipts**: build the byte sequence directly (escape codes plus text), or use a library in your host language (e.g. `mike42/escpos-php` for PHP).
- **ZPL labels**: generate the ZPL text string. It is plain ASCII, so `Content-Type: text/plain` is appropriate.
- **PDF invoices**: render the PDF with your existing tooling (e.g. `dompdf`, `mPDF`, `TCPDF` in PHP) and send the resulting binary with `Content-Type: application/pdf`.
- **Plain text**: send as-is with `Content-Type: text/plain`.

Do not transform line endings or character encoding after generating the payload — the client agent forwards it byte-for-byte to the printer driver.

## Module Configuration Screen

Every module needs a small settings UI inside the host application where the store owner enters:

- Pridge server URL (e.g. `https://pridge.example.com`)
- Endpoint token (store this as a secret, never display it again after saving — treat it like an API key or password)
- Which event triggers a print (e.g. "print on order status: Processing")

Store the token using the host platform's secret-storage convention (WordPress options table with autoload disabled for sensitive values, Joomla plugin params, PrestaShop `Configuration` table, environment variables for custom apps). Never log the token, and never expose it in client-side JavaScript or admin page source.

## Platform Examples

These are minimal skeletons to adapt, not production-ready plugins. Each one reacts to a host-application event and forwards a payload to `/api/plugin/jobs`.

### WordPress / WooCommerce

```php
<?php
/**
 * Plugin Name: Pridge Connector
 */

add_action('woocommerce_order_status_processing', 'pridge_send_order_receipt');

function pridge_send_order_receipt(int $order_id): void
{
    $server_url = get_option('pridge_server_url');
    $endpoint_token = get_option('pridge_endpoint_token');

    if (!$server_url || !$endpoint_token) {
        return;
    }

    $order = wc_get_order($order_id);
    $payload = pridge_build_receipt_text($order); // your own formatting function

    $response = wp_remote_post(rtrim($server_url, '/') . '/api/plugin/jobs', [
        'headers' => [
            'Authorization' => 'Bearer ' . $endpoint_token,
            'Content-Type' => 'text/plain',
            'X-Pridge-Metadata' => wp_json_encode([
                'source' => 'woocommerce',
                'order_id' => (string) $order_id,
            ]),
        ],
        'body' => $payload,
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        error_log('Pridge: failed to submit job for order ' . $order_id . ': ' . $response->get_error_message());
        return;
    }

    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 201) {
        error_log('Pridge: server rejected job for order ' . $order_id . ' (HTTP ' . $status . ')');
    }
}
```

### Joomla

Joomla plugins hook into events dispatched by core or third-party extensions (for example a Joomla-based ordering component). Use `Joomla\CMS\Http\HttpFactory` to make the request:

```php
<?php

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgSystemPridge extends CMSPlugin
{
    public function onOrderComplete(array $order): void
    {
        $params = $this->params; // plugin parameters set in the Joomla admin UI
        $serverUrl = rtrim((string) $params->get('server_url'), '/');
        $endpointToken = (string) $params->get('endpoint_token');

        if ($serverUrl === '' || $endpointToken === '') {
            return;
        }

        $payload = $this->buildReceipt($order); // your own formatting method

        $http = HttpFactory::getHttp();
        $headers = [
            'Authorization' => 'Bearer ' . $endpointToken,
            'Content-Type' => 'text/plain',
            'X-Pridge-Metadata' => json_encode([
                'source' => 'joomla',
                'order_id' => (string) $order['id'],
            ]),
        ];

        try {
            $response = $http->post($serverUrl . '/api/plugin/jobs', $payload, $headers, 10);
            if ($response->code !== 201) {
                Log::add('Pridge job submission failed: HTTP ' . $response->code, Log::WARNING, 'pridge');
            }
        } catch (\RuntimeException $e) {
            Log::add('Pridge job submission error: ' . $e->getMessage(), Log::ERROR, 'pridge');
        }
    }
}
```

### PrestaShop

```php
<?php

class Pridge extends Module
{
    public function hookActionOrderStatusUpdate(array $params): void
    {
        $serverUrl = rtrim((string) Configuration::get('PRIDGE_SERVER_URL'), '/');
        $endpointToken = (string) Configuration::get('PRIDGE_ENDPOINT_TOKEN');

        if ($serverUrl === '' || $endpointToken === '' || (int) $params['newOrderStatus']->id !== (int) Configuration::get('PS_OS_PREPARATION')) {
            return;
        }

        $order = $params['order'];
        $payload = $this->buildReceipt($order); // your own formatting method

        $ch = curl_init($serverUrl . '/api/plugin/jobs');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $endpointToken,
                'Content-Type: text/plain',
                'X-Pridge-Metadata: ' . json_encode(['source' => 'prestashop', 'order_id' => (string) $order->id]),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
```

### Drupal

Drupal modules typically react to entity hooks (for Drupal Commerce orders) or PSR-14 events. Use the core `http_client` service (Guzzle) and the module's own config object for settings:

```php
<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_ENTITY_TYPE_update() for commerce_order.
 */
function pridge_commerce_order_update(EntityInterface $order) {
  if ($order->getState()->getId() !== 'completed') {
    return;
  }

  $config = \Drupal::config('pridge.settings');
  $serverUrl = rtrim((string) $config->get('server_url'), '/');
  $endpointToken = (string) $config->get('endpoint_token');

  if ($serverUrl === '' || $endpointToken === '') {
    return;
  }

  $payload = pridge_build_receipt($order); // your own formatting function

  try {
    \Drupal::httpClient()->post($serverUrl . '/api/plugin/jobs', [
      'headers' => [
        'Authorization' => 'Bearer ' . $endpointToken,
        'Content-Type' => 'text/plain',
        'X-Pridge-Metadata' => json_encode([
          'source' => 'drupal-commerce',
          'order_id' => (string) $order->id(),
        ]),
      ],
      'body' => $payload,
      'timeout' => 10,
    ]);
  }
  catch (\GuzzleHttp\Exception\GuzzleException $e) {
    \Drupal::logger('pridge')->error('Job submission failed for order @id: @message', [
      '@id' => $order->id(),
      '@message' => $e->getMessage(),
    ]);
  }
}
```

Store `server_url` and `endpoint_token` in the module's config schema (`pridge.settings.yml`) and expose them through a Drupal settings form, not in code.

### TYPO3

TYPO3 extensions built on TYPO3 v10+ should react to a PSR-14 event from the shop extension and use core's `RequestFactory` (TYPO3's Guzzle wrapper) plus `ExtensionConfiguration` for settings:

```php
<?php

namespace Vendor\PridgeConnector\EventListener;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use Vendor\Shop\Event\OrderPlacedEvent;

final class SubmitPrintJob
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function __invoke(OrderPlacedEvent $event): void
    {
        $config = $this->extensionConfiguration->get('pridge_connector');
        $serverUrl = rtrim((string) ($config['serverUrl'] ?? ''), '/');
        $endpointToken = (string) ($config['endpointToken'] ?? '');

        if ($serverUrl === '' || $endpointToken === '') {
            return;
        }

        $order = $event->getOrder();
        $payload = $this->buildReceipt($order); // your own formatting method

        try {
            $this->requestFactory->request($serverUrl . '/api/plugin/jobs', 'POST', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $endpointToken,
                    'Content-Type' => 'text/plain',
                    'X-Pridge-Metadata' => json_encode([
                        'source' => 'typo3',
                        'order_id' => (string) $order->getUid(),
                    ]),
                ],
                'body' => $payload,
                'timeout' => 10,
            ]);
        } catch (\Throwable $e) {
            // Log via TYPO3's core logger ($this->logger if LoggerAwareTrait is used).
        }
    }
}
```

Register the listener in `Configuration/Services.yaml` tagged with the shop extension's event class, and expose `serverUrl` / `endpointToken` through the extension's `ext_conf_template.txt` so they are editable from the TYPO3 backend's Extension Configuration screen.

### Pure PHP (No Framework)

For a custom shop, ERP, or a system like Dolibarr that doesn't need a full plugin scaffold, a single dependency-free function is enough. This only requires the `curl` extension:

```php
<?php

declare(strict_types=1);

function pridge_submit_job(
    string $serverUrl,
    string $endpointToken,
    string $payload,
    string $contentType = 'text/plain',
    array $metadata = []
): int {
    $headers = [
        'Authorization: Bearer ' . $endpointToken,
        'Content-Type: ' . $contentType,
    ];

    if ($metadata !== []) {
        $headers[] = 'X-Pridge-Metadata: ' . json_encode($metadata);
    }

    $ch = curl_init(rtrim($serverUrl, '/') . '/api/plugin/jobs');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('Pridge request failed: ' . $error);
    }

    if ($status !== 201) {
        throw new RuntimeException('Pridge rejected the job: HTTP ' . $status . ' ' . $body);
    }

    $decoded = json_decode($body, true);

    return (int) $decoded['job_id'];
}

// Usage, e.g. from a Dolibarr trigger, a legacy cart, or any plain PHP script:
$jobId = pridge_submit_job(
    'https://pridge.example.com',
    'ENDPOINT_TOKEN',
    "Order #5001\n2x Widget\nTotal: \$19.98\n",
    'text/plain',
    ['source' => 'custom-cart', 'order_id' => '5001']
);
```

Wrap the settings (`server_url`, `endpoint_token`) in whatever configuration mechanism the host system already uses — a database table, an `.env` file, or a config array — rather than hardcoding them.

### Any Other Platform (Language-Agnostic)

If the host application is not PHP-based, the integration is the same three steps in any language: build the payload, set the `Authorization` header, POST the raw bytes.

curl:

```bash
curl -X POST "https://pridge.example.com/api/plugin/jobs" \
  -H "Authorization: Bearer ENDPOINT_TOKEN" \
  -H "Content-Type: text/plain" \
  -H 'X-Pridge-Metadata: {"source":"custom-erp","order_id":"5001"}' \
  --data-binary @receipt.txt
```

Python:

```python
import requests

response = requests.post(
    "https://pridge.example.com/api/plugin/jobs",
    headers={
        "Authorization": "Bearer ENDPOINT_TOKEN",
        "Content-Type": "text/plain",
        "X-Pridge-Metadata": '{"source":"custom-erp","order_id":"5001"}',
    },
    data=receipt_bytes,
    timeout=10,
)
response.raise_for_status()
job_id = response.json()["job_id"]
```

Node.js:

```javascript
const response = await fetch("https://pridge.example.com/api/plugin/jobs", {
  method: "POST",
  headers: {
    Authorization: "Bearer ENDPOINT_TOKEN",
    "Content-Type": "text/plain",
    "X-Pridge-Metadata": JSON.stringify({ source: "custom-erp", order_id: "5001" }),
  },
  body: receiptBytes,
});

if (!response.ok) {
  throw new Error(`Pridge rejected the job: HTTP ${response.status}`);
}

const { job_id: jobId } = await response.json();
```

## Error Handling and Retries

Handle these outcomes explicitly:

- `201` — job accepted. Store `job_id` in your own logs if you want to correlate later, but Pridge does not expose a lookup-by-external-id route.
- `400` — the request body was empty. This is a bug in your module, not a transient failure; do not retry blindly.
- `401` — the token is invalid, disabled, or was regenerated. Stop retrying and surface a clear error to the store owner so they can re-enter the token.
- Network errors / timeouts — safe to retry a small number of times with backoff. Do not retry indefinitely inside a web request that blocks a customer-facing page load; queue the retry asynchronously if the host platform supports it (e.g. WooCommerce action scheduler, Joomla's `#__jobs`, PrestaShop's message queue, or a simple cron-based retry table).

Never let a Pridge outage break checkout or order processing in the host application. Fire the request after the order is committed, and fail silently (with logging) rather than rolling back the host transaction.

## Testing Your Module

1. Trigger the event manually (place a test order, or call your module's send function directly from a debug script).
2. Confirm the job appears in the Pridge admin queue at `/queue` with status `pending`.
3. Open the job to preview the payload and confirm it rendered or downloaded as expected.
4. Test the failure paths: an invalid token should produce a `401` and a clear module-side error; an empty payload should produce a `400`.
5. Run a client agent (see [`client-agent-development.md`](client-agent-development.md)) against a test endpoint and confirm the job reaches `printed`.

You can also test the raw API without your module using curl, before wiring up the host application:

```bash
curl -i -X POST "https://pridge.example.com/api/plugin/jobs" \
  -H "Authorization: Bearer ENDPOINT_TOKEN" \
  -H "Content-Type: text/plain" \
  --data-binary "Hello from a test print job"
```

## Security Checklist

- Send the endpoint token only in the `Authorization` header or POST body — never in a URL query string or GET request.
- Store the token using the host platform's secret-storage mechanism, and never echo it back in full after initial save.
- Always use HTTPS in production. Do not disable TLS certificate verification in your HTTP client to work around a self-signed certificate — fix the certificate instead.
- Do not log the endpoint token, full payload contents, or any customer PII beyond what is already in the host application's own logs.
- Let the store owner regenerate the token from the Pridge admin UI at any time, and make sure your module's settings screen lets them update the stored value without a plugin reinstall.

## Module Development Checklist

- Settings screen collects server URL and endpoint token.
- Token is stored securely and not displayed after initial save.
- Module builds a correctly formatted raw payload for the target printer format.
- `Content-Type` matches the payload format.
- Job submission happens after the triggering event is committed, not inside a transaction that could roll back.
- `401` responses surface a clear, actionable error instead of failing silently forever.
- Network failures are retried with backoff, not retried indefinitely inline.
- Manual test confirms the job reaches `/queue` as `pending`.
- Manual test confirms a client agent can reserve, print, and mark the job `printed`.
- No tokens or raw payloads appear in host-application logs.
