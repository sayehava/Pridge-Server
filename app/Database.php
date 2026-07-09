<?php

declare(strict_types=1);

namespace PrintBridge;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $pdo = new PDO('sqlite:' . PRINTBRIDGE_DATABASE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::$connection = $pdo;

        return $pdo;
    }

    public static function migrate(): void
    {
        $db = self::connection();

        $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    used_at TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    ip_hash TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    locked_until TEXT,
    first_attempt_at TEXT NOT NULL,
    last_attempt_at TEXT NOT NULL,
    UNIQUE(username, ip_hash)
);

CREATE TABLE IF NOT EXISTS endpoints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    enabled INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    enabled INTEGER NOT NULL DEFAULT 1,
    last_seen_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS client_endpoint_assignments (
    client_id INTEGER NOT NULL,
    endpoint_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY (client_id, endpoint_id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (endpoint_id) REFERENCES endpoints(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS client_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    last_seen_at TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS print_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    endpoint_id INTEGER NOT NULL,
    client_id INTEGER,
    payload BLOB NOT NULL,
    content_type TEXT NOT NULL DEFAULT 'application/octet-stream',
    metadata_json TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    reserved_until TEXT,
    created_at TEXT NOT NULL,
    picked_up_at TEXT,
    completed_at TEXT,
    failed_at TEXT,
    last_error TEXT,
    FOREIGN KEY (endpoint_id) REFERENCES endpoints(id) ON DELETE RESTRICT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_print_jobs_status_created ON print_jobs(status, created_at);
CREATE INDEX IF NOT EXISTS idx_print_jobs_endpoint_status ON print_jobs(endpoint_id, status);
CREATE INDEX IF NOT EXISTS idx_client_sessions_expires ON client_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_lock ON login_attempts(username, ip_hash, locked_until);
SQL);

        self::addColumnIfMissing('admin_users', 'email', 'TEXT');
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $db = self::connection();
        $columns = $db->query('PRAGMA table_info(' . $table . ')')->fetchAll();

        foreach ($columns as $existingColumn) {
            if (($existingColumn['name'] ?? null) === $column) {
                return;
            }
        }

        $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }
}
