# Client Agent Development Guide

This guide describes how to build a client-side application that works with Pridge Server.

The client agent is a local desktop or tray application that stays running on the office computer. It discovers local printers, shows a simple GUI, pulls print jobs from Pridge Server, and sends the raw job payload to a selected local printer.

The server remains PHP and SQLite for shared hosting. The client agent can be written in Python for the first fast version and rewritten later in another language.

If you are building the plugin/module that submits print jobs from a CMS or e-commerce platform (WordPress, Joomla, PrestaShop, and so on), see [`module-development.md`](module-development.md) instead. This guide is about the other side: pulling jobs out of Pridge and printing them.

## Responsibilities

The client agent should:

- Stay live on the client computer.
- Start automatically with the operating system when possible.
- Store server URL and client token locally.
- Authenticate with Pridge Server using the client token.
- Send regular heartbeat requests.
- Fetch assigned jobs.
- Reserve one job at a time.
- Decode the raw payload.
- Print the raw payload to the chosen local printer.
- Report `printing`, `printed`, or `failed`.
- Show a simple GUI for status, settings, printer selection, and recent jobs.

## Recommended First Version

Python is acceptable for a first implementation because it is fast to build and test.

Suggested libraries:

- `requests` for HTTP API calls.
- `tkinter` for a simple built-in GUI.
- `pystray` for an optional tray icon.
- `keyring` for storing tokens more safely when available.
- Windows: `pywin32` for printer discovery and printing.
- Linux: `pycups` for CUPS printer discovery and printing.
- macOS: shell out to `lpstat` and `lp`, or use a native printing bridge later.

Avoid adding this Python agent to the PHP shared-hosting server runtime. Treat it as a separate client-side app.

## Local Configuration

The client agent needs these settings:

```text
server_url=https://pridge.example.com
client_token=CLIENT_TOKEN_FROM_ADMIN_UI
default_printer=Local Printer Name
poll_interval_seconds=5
heartbeat_interval_seconds=30
```

Store the client token outside logs and never display it in full after setup.

## GUI Requirements

The first GUI can be simple:

- Server URL field.
- Client token field.
- Connect or test connection button.
- Local printer list.
- Default printer selector.
- Current connection status.
- Last heartbeat time.
- Last job status.
- Start or pause polling toggle.
- Small recent jobs table.

The GUI should not be required for printing once configured. The background worker should continue running while the window is minimized or hidden.

## Discover Local Printers

The client agent should show all installed local printers.

Windows example direction:

```python
import win32print

printers = [
    printer[2]
    for printer in win32print.EnumPrinters(
        win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
    )
]
```

Linux CUPS example direction:

```python
import cups

connection = cups.Connection()
printers = list(connection.getPrinters().keys())
```

macOS command-line direction:

```bash
lpstat -p
```

The app should refresh this list from the GUI.

## Authentication Flow

Clients authenticate with the client token created in the Pridge admin UI.

Request:

```http
POST /api/client/auth
Content-Type: application/json

{"token":"CLIENT_TOKEN","client_version":"1.2.1"}
```

`client_version` is optional but recommended: send the running agent's own version so the server can tell you, and only you, when it and the server have drifted onto incompatible major versions.

Response:

```json
{
  "token": "TEMPORARY_SESSION_TOKEN",
  "token_type": "Bearer",
  "expires_in": 86400,
  "client": {
    "id": 2,
    "name": "Warehouse Laptop"
  },
  "server_version": "1.1.1",
  "compatibility_warning": null
}
```

`compatibility_warning` is a human-readable string ("update the client" or "update the server") when `client_version` was sent and its major version differs from `server_version`; otherwise it is omitted or null. Treat it as advisory only — log it and/or surface it in your agent's UI, but never refuse to authenticate or stop polling because of it.

Use the returned temporary token in the `Authorization` header:

```http
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

If the server returns `401`, authenticate again with the stored client token.

## Heartbeat

Send a heartbeat while the agent is running.

Request:

```http
POST /api/client/heartbeat
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Recommended interval:

```text
30 seconds
```

## Polling and Printing Flow

Recommended loop:

1. Authenticate if no temporary session token exists.
2. Send heartbeat periodically.
3. Reserve one job.
4. If no job exists, wait and retry.
5. Mark the job as `printing`.
6. Decode `payload_base64`.
7. Send decoded bytes to the selected local printer.
8. If printing succeeds, mark the job as `printed`.
9. If printing fails, mark the job as `failed` with a safe error message.

Reserve request:

```http
POST /api/client/jobs/reserve
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Example response:

```json
{
  "job": {
    "id": 123,
    "endpoint_id": 1,
    "content_type": "application/octet-stream",
    "metadata": {
      "source": "woocommerce",
      "order_id": "1001"
    },
    "status": "reserved",
    "created_at": "2026-07-09 10:20:00",
    "payload_base64": "BASE64_ENCODED_RAW_PAYLOAD"
  }
}
```

Mark printing:

```http
POST /api/client/jobs/123/printing
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Mark printed:

```http
POST /api/client/jobs/123/printed
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Mark failed:

```http
POST /api/client/jobs/123/failed
Authorization: Bearer TEMPORARY_SESSION_TOKEN
Content-Type: application/json

{"error":"Printer is offline"}
```

## Raw Printing

The server returns the print payload as base64 because JSON cannot safely carry arbitrary binary bytes.

The client agent must:

1. Base64-decode `payload_base64`.
2. Send the decoded bytes to the printer without changing them.

Python decode example:

```python
import base64

raw_payload = base64.b64decode(job["payload_base64"])
```

For ESC/POS, ZPL, PDF, or other print formats, do not convert line endings or character encodings unless the specific printer driver requires it.

## Windows Raw Print Direction

For Windows raw printer output, use the `RAW` data type through `pywin32`.

Example direction:

```python
import win32print

def send_raw_to_printer(printer_name, payload):
    handle = win32print.OpenPrinter(printer_name)
    try:
        job = win32print.StartDocPrinter(handle, 1, ("Pridge Job", None, "RAW"))
        try:
            win32print.StartPagePrinter(handle)
            win32print.WritePrinter(handle, payload)
            win32print.EndPagePrinter(handle)
        finally:
            win32print.EndDocPrinter(handle)
    finally:
        win32print.ClosePrinter(handle)
```

## Linux CUPS Print Direction

For Linux, write the payload to a temporary file and submit it through CUPS or `lp`.

Example direction:

```python
import cups
import tempfile

def send_raw_to_printer(printer_name, payload):
    connection = cups.Connection()
    with tempfile.NamedTemporaryFile(delete=False) as temp:
        temp.write(payload)
        temp_path = temp.name
    connection.printFile(printer_name, temp_path, "Pridge Job", {"raw": "true"})
```

## Failure Handling

The client agent should report failure when:

- No default printer is selected.
- The selected printer is no longer installed.
- The OS print API rejects the job.
- The server session token is invalid and re-authentication fails.
- The decoded payload is empty.

Failure messages should be short and safe. Do not include local filesystem paths or secrets.

## Logging

Logs should include:

- App start and stop.
- Server connection status.
- Printer selected.
- Job ID and status transitions.
- Safe error messages.

Logs must not include:

- Full client token.
- Temporary session token.
- Raw print payload.
- Passwords or reset tokens.

## Packaging

For a quick Python build:

- Windows: package with PyInstaller.
- Linux: provide a systemd user service or desktop autostart entry.
- macOS: package later as a signed app if needed.

The app should support auto-start because it must stay live on the client side.

## Minimal Worker Pseudocode

```python
while running:
    if not session_token:
        session_token = authenticate(server_url, client_token)

    maybe_send_heartbeat()

    job = reserve_job(session_token)
    if not job:
        sleep(poll_interval_seconds)
        continue

    try:
        mark_printing(session_token, job["id"])
        payload = base64.b64decode(job["payload_base64"])
        send_raw_to_printer(default_printer, payload)
        mark_printed(session_token, job["id"])
    except Exception as exc:
        mark_failed(session_token, job["id"], safe_error_message(exc))
```

## Development Checklist

- Can save server URL and client token.
- Can authenticate and refresh session after `401`.
- Can list local printers.
- Can select and persist default printer.
- Can heartbeat while running.
- Can reserve jobs.
- Can decode payloads without corruption.
- Can print raw bytes.
- Can report success.
- Can report failure.
- Can keep running in the background.
- Can start automatically after reboot.
