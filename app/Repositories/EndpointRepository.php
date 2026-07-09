<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;
use PrintBridge\Support\Clock;
use PrintBridge\Support\Security;

final class EndpointRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, enabled, created_at, updated_at FROM endpoints ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function create(string $name): string
    {
        $token = Security::randomToken();
        $now = Clock::now();
        $stmt = Database::connection()->prepare(
            'INSERT INTO endpoints (name, token_hash, enabled, created_at, updated_at) VALUES (:name, :token_hash, 1, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':token_hash' => Security::hashToken($token),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $token;
    }

    public static function toggle(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE endpoints SET enabled = CASE enabled WHEN 1 THEN 0 ELSE 1 END, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([':id' => $id, ':updated_at' => Clock::now()]);
    }
}
