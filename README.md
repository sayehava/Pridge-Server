# PrintBridge Server

PrintBridge Server is a plain PHP and SQLite print job server for shared hosting environments.

## Requirements

- PHP with PDO SQLite support
- HTTPS hosting for production
- Writable `storage` directory outside direct public access where possible

## Run Locally

```bash
php -S 127.0.0.1:8080 -t public
```

Then open:

```text
http://127.0.0.1:8080
```
