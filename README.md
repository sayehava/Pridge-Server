# PrintBridge Server

PrintBridge Server is a plain PHP and SQLite print job server for shared hosting environments.

It provides an admin web UI, plugin-side print job receiving, client-side job pulling, endpoint tokens, client authentication, queue status tracking, and password recovery.

## Requirements

- PHP with PDO SQLite support
- HTTPS hosting for production
- Writable `storage` directory
- Optional PHP `mail()` support for password recovery email

## Installation

Upload the project to your hosting account and point the web root to:

```text
public
```

The application creates the SQLite database automatically at:

```text
storage/database/printbridge.sqlite
```

For local development:

```bash
php -S 127.0.0.1:8080 -t public
```

Then open:

```text
http://127.0.0.1:8080
```

## SQLite Protection

The `storage`, `storage/database`, and `app` directories include `.htaccess` files that deny direct web access on Apache-compatible hosting.

For best production security, keep `storage` outside the public web root when your host allows it. Never expose `printbridge.sqlite` through HTTP.

## First Admin Setup

Open the site in a browser. If no admin exists, the server redirects to:

```text
/setup
```

Create the first admin account with a password of at least 12 characters. Add an email address if you want password recovery links to be sent through PHP `mail()`.

After setup, the setup screen is disabled automatically.

## Endpoint Token Usage

Create endpoints in the admin UI at:

```text
/endpoints
```

Each endpoint token is shown once. Store it in the plugin or connector that submits print jobs.

Submit a print job:

```http
POST /api/plugin/jobs
Authorization: Bearer ENDPOINT_TOKEN
Content-Type: application/octet-stream
X-PrintBridge-Metadata: {"source":"woocommerce","order_id":"1001"}

RAW_PRINT_PAYLOAD
```

The server stores the raw request body as the print payload.

## Client Authentication

Create clients in the admin UI at:

```text
/clients
```

Assign one or more endpoints to each client. The client token is shown once.

Authenticate:

```http
POST /api/client/auth
Content-Type: application/json

{"token":"CLIENT_TOKEN"}
```

The response contains a temporary bearer token for client API calls.

## API Routes

Plugin route:

- `POST /api/plugin/jobs` receives a raw print payload for the authenticated endpoint.

Client routes:

- `POST /api/client/auth` authenticates a client token and returns a temporary bearer token.
- `GET /api/client/jobs` lists assigned jobs and recent status.
- `POST /api/client/jobs/reserve` reserves the next pending job and returns `payload_base64`.
- `POST /api/client/jobs/{id}/printing` marks a job as printing.
- `POST /api/client/jobs/{id}/printed` confirms successful printing.
- `POST /api/client/jobs/{id}/failed` reports failed printing with an optional JSON `error` field.
- `POST /api/client/heartbeat` updates the client heartbeat.

Reserved jobs return to `pending` after the reservation timeout unless the client confirms progress.

## Queue Behavior

Jobs use these statuses:

```text
pending
reserved
printing
printed
failed
cancelled
```

Printed jobs are removed from the waiting queue by status and remain available in history for admin review.

## Backup Recommendations

Back up these paths regularly:

```text
storage/database/printbridge.sqlite
storage/
```

Stop write traffic before copying the SQLite file when possible. At minimum, keep timestamped backups and test restoration on a separate installation.
