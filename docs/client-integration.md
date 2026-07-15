# Pridge Client and Plugin Integration

This document explains how external systems should send raw print data to Pridge Server and how office-side clients should pull and report jobs.

## Terminology

- Endpoint: the print destination that receives jobs from a plugin or connector.
- Client: the office-side application or computer that pulls jobs and sends them to the real printer.

Do not send tokens in URL query strings. Use POST bodies or headers only.

## Plugin: Submit Raw Print Data

Plugins submit raw print payloads to an endpoint.

This route is POST-only. The endpoint token is sent in a request header so the request body can stay as untouched raw print bytes.

Request:

```http
POST /api/plugin/jobs
Authorization: Bearer ENDPOINT_TOKEN
Content-Type: application/octet-stream
X-Pridge-Metadata: {"source":"woocommerce","order_id":"1001"}

RAW_PRINT_PAYLOAD_BYTES
```

Response:

```json
{
  "job_id": 123,
  "status": "pending"
}
```

Notes:

- The request body is stored raw.
- The endpoint token is not sent in the URL.
- The endpoint token is not sent in the body for this route because the body is the raw print payload.
- Binary payloads are allowed.
- Set `Content-Type` to the payload type when known.
- Use `application/octet-stream` when the payload type is unknown.
- `X-Pridge-Metadata` is optional and should contain JSON when possible.
- The endpoint token must not be sent as a GET parameter.

## Plugin: Fetch Assigned Clients

An endpoint can fetch clients assigned to it with a POST request.

This route is also POST-only. For this JSON request, the endpoint token should be sent in the POST body.

Request:

```http
POST /api/plugin/clients
Content-Type: application/json

{"token":"ENDPOINT_TOKEN"}
```

Response:

```json
{
  "endpoint": {
    "id": 1,
    "name": "Warehouse Label Printer"
  },
  "clients": [
    {
      "id": 2,
      "name": "Warehouse Laptop",
      "enabled": 1,
      "last_seen_at": "2026-07-09 10:15:00",
      "created_at": "2026-07-09 09:30:00"
    }
  ]
}
```

This returns only clients assigned to the authenticated endpoint.

## Client: Authenticate

Clients authenticate with their client token and receive a temporary bearer token.

Request:

```http
POST /api/client/auth
Content-Type: application/json

{"token":"CLIENT_TOKEN"}
```

Response:

```json
{
  "token": "TEMPORARY_SESSION_TOKEN",
  "token_type": "Bearer",
  "expires_in": 86400,
  "client": {
    "id": 2,
    "name": "Warehouse Laptop"
  }
}
```

Use the temporary token for the remaining client API calls:

```http
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

## Client: List Assigned Endpoints

Request:

```http
GET /api/client/endpoints
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response:

```json
{
  "endpoints": [
    {
      "id": 1,
      "name": "Receipt Printer",
      "enabled": true,
      "assigned": false
    }
  ]
}
```

This route lists every virtual printer endpoint, including endpoints without queued jobs. Each item includes an `assigned` boolean for the authenticated client.

## Client: Synchronize Endpoint Assignments

Request:

```http
PUT /api/client/endpoints
Authorization: Bearer TEMPORARY_SESSION_TOKEN
Content-Type: application/json

{"endpoint_ids":[1,2]}
```

The supplied IDs replace the authenticated client's current endpoint assignments. An empty array disables every endpoint for that client. The response uses the same payload as `GET /api/client/endpoints`.

## Client: List Jobs

Request:

```http
GET /api/client/jobs
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response:

```json
{
  "jobs": []
}
```

This route lists assigned job status. It does not include raw payloads.

## Client: Reserve and Pull One Job

Request:

```http
POST /api/client/jobs/reserve
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response when a job is available:

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

Response when no job is available:

```json
{
  "job": null
}
```

The client must base64-decode `payload_base64` before sending it to the printer.

Reserved jobs become available again after the reservation timeout if the client does not confirm progress.

## Client: Mark Job as Printing

Request:

```http
POST /api/client/jobs/123/printing
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response:

```json
{
  "job_id": 123,
  "status": "printing"
}
```

## Client: Confirm Successful Printing

Request:

```http
POST /api/client/jobs/123/printed
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response:

```json
{
  "job_id": 123,
  "status": "printed"
}
```

Only call this after the local print operation has succeeded.

## Client: Report Failed Printing

Request:

```http
POST /api/client/jobs/123/failed
Authorization: Bearer TEMPORARY_SESSION_TOKEN
Content-Type: application/json

{"error":"Printer is offline"}
```

Response:

```json
{
  "job_id": 123,
  "status": "failed"
}
```

## Client: Heartbeat

Request:

```http
POST /api/client/heartbeat
Authorization: Bearer TEMPORARY_SESSION_TOKEN
```

Response:

```json
{
  "status": "ok"
}
```

Send heartbeats periodically so the admin UI can show when a client was last seen.
