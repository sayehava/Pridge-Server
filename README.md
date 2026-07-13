# PrintBridge Server

PrintBridge Server is a plain PHP and SQLite print job server for shared hosting environments.

It provides an admin web UI, plugin-side print job receiving, client-side job pulling, endpoint tokens, client authentication, queue status tracking, and password recovery.

## Requirements

- PHP with PDO SQLite support
- HTTPS hosting for production
- Writable `storage` directory
- Optional PHP `mail()` support for password recovery email

## Installation

### cPanel Subdomain Installation

This project can run directly from the folder that cPanel creates for a subdomain.

Upload the project files into the subdomain document root, for example:

```text
public_html/printbridge
```

or:

```text
printbridge.yourdomain.com
```

The root `index.php` and `.htaccess` files handle requests from that folder. You do not need to point cPanel to the `public` folder.

Make sure PHP can write to:

```text
storage
storage/database
```

The application creates the SQLite database automatically at:

```text
storage/database/printbridge.sqlite
```

### Optional Public Web Root Installation

If your hosting panel supports custom document roots, the stricter deployment is still available:

```text
public
```

Both layouts use the same application code.

### Local Development

For the cPanel-style root layout:

```bash
php -S 127.0.0.1:8080
```

For the stricter public web root layout:

```bash
php -S 127.0.0.1:8080 -t public
```

Then open:

```text
http://127.0.0.1:8080
```

## SQLite Protection

The `storage`, `storage/database`, `app`, and `views` directories include `.htaccess` files that deny direct web access on Apache-compatible hosting.

The root `.htaccess` also disables directory indexes, routes clean URLs to `index.php`, and blocks direct access to project documentation files.

For best production security, keep `storage` outside the public web root when your host allows it. Never expose `printbridge.sqlite` through HTTP.

## First Admin Setup

Open the site in a browser. If no admin exists, the server redirects to:

```text
/setup
```

Create the first admin account with a password of at least 12 characters. Add an email address if you want password recovery links to be sent through PHP `mail()`.

After setup, the setup screen is disabled automatically.

Admin login is protected by throttling. Repeated failed attempts for the same username and source address are temporarily locked.

## Endpoint Token Usage

Create endpoints in the admin UI at:

```text
/endpoints
```

Each endpoint token is shown once. Store it in the plugin or connector that submits print jobs.

Endpoints can be enabled, disabled, deleted when they have no job history, and assigned to clients from the endpoint management page.

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

Assignments can be managed from the client page or from the endpoint page.

Authenticate:

```http
POST /api/client/auth
Content-Type: application/json

{"token":"CLIENT_TOKEN"}
```

The response contains a temporary bearer token for client API calls.

## API Routes

More detailed integration instructions are available in:

```text
docs/client-integration.md
docs/endpoint-agent-development.md
```

Plugin route:

- `POST /api/plugin/jobs` receives a raw print payload for the authenticated endpoint.
- `POST /api/plugin/clients` returns clients assigned to an authenticated endpoint.

Client routes:

- `POST /api/client/auth` authenticates a client token and returns a temporary bearer token.
- `GET /api/client/jobs` lists assigned jobs and recent status.
- `GET /api/client/endpoints` lists all virtual printer endpoints and their assignment state for the authenticated client.
- `PUT /api/client/endpoints` synchronizes the authenticated client's endpoint assignments.
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

Printed jobs are removed from the waiting queue by status, not deleted. The admin Queue page splits jobs into an active queue (pending, reserved, printing, failed) and an archive (printed, cancelled) for history review. Any job can be force-deleted from either view.

Open a job to preview its payload: images and PDFs render inline, plain text renders as text, and unrecognized binary payloads (for example raw ESC/POS data) offer a download instead of a preview. Preview type is detected from the stored content type first and the payload's own bytes as a fallback, since connectors often submit everything as `application/octet-stream`.

## Backup Recommendations

Back up these paths regularly:

```text
storage/database/printbridge.sqlite
storage/
```

Stop write traffic before copying the SQLite file when possible. At minimum, keep timestamped backups and test restoration on a separate installation.
