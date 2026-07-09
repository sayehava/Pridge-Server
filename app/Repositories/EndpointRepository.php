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
            ->query(
                'SELECT e.id, e.name, e.enabled, e.created_at, e.updated_at,
                    GROUP_CONCAT(c.id) AS client_ids,
                    GROUP_CONCAT(c.name, ", ") AS client_names
                 FROM endpoints e
                 LEFT JOIN client_endpoint_assignments cea ON cea.endpoint_id = e.id
                 LEFT JOIN clients c ON c.id = cea.client_id
                 GROUP BY e.id
                 ORDER BY e.created_at DESC'
            )
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

    public static function delete(int $id): bool
    {
        $jobCount = Database::connection()
            ->prepare('SELECT COUNT(*) FROM print_jobs WHERE endpoint_id = :id');
        $jobCount->execute([':id' => $id]);

        if ((int) $jobCount->fetchColumn() > 0) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM endpoints WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * @param array<int, int> $clientIds
     */
    public static function syncClients(int $endpointId, array $clientIds): void
    {
        $db = Database::connection();
        $now = Clock::now();
        $db->beginTransaction();

        $delete = $db->prepare('DELETE FROM client_endpoint_assignments WHERE endpoint_id = :endpoint_id');
        $delete->execute([':endpoint_id' => $endpointId]);

        $insert = $db->prepare(
            'INSERT OR IGNORE INTO client_endpoint_assignments (client_id, endpoint_id, created_at)
             VALUES (:client_id, :endpoint_id, :created_at)'
        );

        foreach (array_unique($clientIds) as $clientId) {
            if ($clientId <= 0) {
                continue;
            }

            $insert->execute([
                ':client_id' => $clientId,
                ':endpoint_id' => $endpointId,
                ':created_at' => $now,
            ]);
        }

        $db->commit();
    }
}
