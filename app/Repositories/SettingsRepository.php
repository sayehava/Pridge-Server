<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;
use PrintBridge\Support\Clock;

final class SettingsRepository
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::connection()->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (key, value, updated_at) VALUES (:key, :value, :now)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
        );
        $stmt->execute([':key' => $key, ':value' => $value, ':now' => Clock::now()]);
    }
}
