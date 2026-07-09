<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;
use PrintBridge\Support\Clock;
use PrintBridge\Support\Security;

final class ClientRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT c.id, c.name, c.enabled, c.last_seen_at, c.created_at, c.updated_at,
                    GROUP_CONCAT(e.name, ", ") AS endpoint_names
                 FROM clients c
                 LEFT JOIN client_endpoint_assignments cea ON cea.client_id = c.id
                 LEFT JOIN endpoints e ON e.id = cea.endpoint_id
                 GROUP BY c.id
                 ORDER BY c.created_at DESC'
            )
            ->fetchAll();
    }

    /**
     * @param array<int, int> $endpointIds
     */
    public static function create(string $name, array $endpointIds): string
    {
        $db = Database::connection();
        $token = Security::randomToken();
        $now = Clock::now();

        $db->beginTransaction();
        $stmt = $db->prepare(
            'INSERT INTO clients (name, token_hash, enabled, created_at, updated_at) VALUES (:name, :token_hash, 1, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':token_hash' => Security::hashToken($token),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $clientId = (int) $db->lastInsertId();
        $assignment = $db->prepare(
            'INSERT OR IGNORE INTO client_endpoint_assignments (client_id, endpoint_id, created_at) VALUES (:client_id, :endpoint_id, :created_at)'
        );

        foreach ($endpointIds as $endpointId) {
            $assignment->execute([
                ':client_id' => $clientId,
                ':endpoint_id' => $endpointId,
                ':created_at' => $now,
            ]);
        }

        $db->commit();

        return $token;
    }

    public static function toggle(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clients SET enabled = CASE enabled WHEN 1 THEN 0 ELSE 1 END, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([':id' => $id, ':updated_at' => Clock::now()]);
    }
}
