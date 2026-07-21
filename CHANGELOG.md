# Changelog

All notable changes to this project are documented in this file.

Changelog tracking starts at 1.1.0. Add a new `## [x.y.z]` section here before tagging a release; the release workflow publishes that section as the GitHub release notes and fails if it can't find one for the tag.

## [1.1.1]

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
