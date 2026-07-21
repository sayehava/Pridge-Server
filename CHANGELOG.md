# Changelog

All notable changes to this project are documented in this file.

Changelog tracking starts at 1.1.0. Add a new `## [x.y.z]` section here before tagging a release; the release workflow publishes that section as the GitHub release notes and fails if it can't find one for the tag.

## [1.3.0]

Includes the 1.2.0 changes below — 1.2.0 was never tagged as its own release, since 1.3.0 was ready within the same development session before either was published.

### Added
- New `/updates` admin page: checks GitHub for new releases and lets an admin update the installation from the browser. Updating is a two-step, explicit process — a full backup is taken and the new release is downloaded and staged first, and nothing on the live site changes until a separate "Apply update now" confirmation. The database and everything else under `storage/` are never touched by an update. The last 5 backups are kept automatically, each with a one-click restore. Requires the PHP `curl` and `zip` extensions and write access to the installation directory. See the README's "Updates" section.

## [1.2.0]

Not published as a separate release; folded into 1.3.0 above.

### Added
- Version compatibility checking between the server and its clients/modules. Clients can send `client_version` and endpoint modules can send an `X-Pridge-Module-Version` header; the server responds with its own `server_version` and, only when the peer's major version differs from the server's, a `compatibility_warning` string saying which side to update. This is advisory only — it never blocks authentication or a print job. See `docs/client-integration.md`, `docs/client-agent-development.md`, and `docs/module-development.md` for the exact request/response shape.

## [1.1.1]

This is the first published release of Pridge Server: a self-hosted PHP + SQLite print job broker for shared hosting. It sits between whatever generates a print job (a WooCommerce store, a Dolibarr install, a custom ERP module) and the desktop clients that actually print it, with no database server and no long-running process required.

### Core

- Admin web UI for setup, endpoints, clients, queue, archive, and settings — plain PHP pages, no separate dashboard app.
- SQLite storage created automatically on first run at `storage/database/pridge.sqlite`; `.htaccess` rules in `storage`, `app`, and `views` block direct web access to it and to internal PHP/view files.
- Runs from either a cPanel-style document root or a stricter dedicated `public/` web root, with no code differences between the two.

### Plugin-side job submission

- `POST /api/plugin/jobs` accepts a raw print payload (ESC/POS, PDF, image, etc.) authenticated by a per-endpoint bearer token, with an optional `X-Pridge-Metadata` header for source context.
- `POST /api/plugin/clients` lets an authenticated endpoint discover which clients are assigned to it.
- Guides for building source-side integrations in `docs/module-development.md` (WordPress, Joomla, PrestaShop, Magento, OpenCart, Drupal, TYPO3, or plain PHP).

### Client-side job pulling

- `POST /api/client/auth` exchanges a long-lived client token for a temporary session bearer token.
- `GET /api/client/endpoints` / `PUT /api/client/endpoints` let a client discover and self-manage which virtual printer endpoints it is assigned to.
- `POST /api/client/jobs/reserve` reserves the next pending job as a base64 payload; unconfirmed reservations automatically return to `pending` after the reservation timeout.
- `POST /api/client/jobs/{id}/printing|printed|failed` report job progress and outcome.
- `POST /api/client/heartbeat` keeps the client's last-seen status current.
- Guide for building a client in `docs/client-agent-development.md`.

### Endpoints, clients, and the queue

- Endpoints and clients each get a one-time-displayed token, and can be renamed, disabled, deleted (once they have no job history), and have their token regenerated.
- Assignment between clients and endpoints is managed from either side, in the admin UI or via the client API.
- Jobs move through `pending → reserved → printing → printed`/`failed`/`cancelled`. The `/queue` page shows only active jobs; `/archive` holds completed and cancelled history, with pagination, bulk delete, and configurable retention.
- Every job's payload can be previewed inline (image, PDF, text) or downloaded (unrecognized binary, e.g. raw ESC/POS), detected from the stored content type first and the payload's own bytes as a fallback.

### Security and account management

- First-run `/setup` creates the initial admin account (12+ character password minimum) and disables itself afterward.
- Escalating login throttling: 3 failed attempts locks an account for 15 minutes; repeat offenses within that window escalate to a 24-hour lockout.
- Password recovery via PHP `mail()` or a dependency-free raw-socket SMTP client configurable from Settings (host, port, STARTTLS/SSL/none, credentials, from-address).
- GPL-3.0-or-later licensed, with an additional attribution term requiring modified or redistributed versions to keep a visible author credit in their UI.

### Fixed
- Client sessions now slide their expiry forward on activity instead of expiring exactly 24 hours after creation, so a client left running continuously past that point no longer loses its session from uptime alone.
- The Authorization header is now explicitly forwarded to PHP via `.htaccess`. Depending on the hosting environment's default Apache/PHP-CGI configuration, this header could fail to reach PHP at all, causing every Bearer-token API call (heartbeat, job reservation, endpoint sync) to be rejected with "Invalid client session" even immediately after a successful, fresh authentication.

## [1.1.0]

### Added
- Escalating admin login lockout: 3 failed login attempts locks the account for 15 minutes; 2 more failed attempts after that lockout expires escalate to a 24-hour lockout.
- SMTP email delivery as an alternative to PHP's built-in `mail()`. Configurable from the Settings page: host, port, encryption (STARTTLS, SSL, or none), username, password, and from address/name. No external mail library dependency — a small raw-socket SMTP client.
- GitHub Actions release workflow: pushing a `v*.*.*` tag packages a source zip and publishes a GitHub release with these notes.

### Changed
- Renamed the internal PHP namespace, `PRINTBRIDGE_*` constants, and the SQLite database filename from `PrintBridge`/`printbridge.sqlite` to `Pridge`/`pridge.sqlite`, matching the repository name. Existing deployments must rename `storage/database/printbridge.sqlite` to `storage/database/pridge.sqlite` on upgrade or the server starts with a fresh empty database.
