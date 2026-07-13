<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;

final class QueueRepository
{
    private const LIST_COLUMNS = 'j.id, j.status, j.content_type, j.created_at, j.picked_up_at, j.completed_at, j.failed_at, j.last_error,
                e.name AS endpoint_name, c.name AS reserved_client_name,
                GROUP_CONCAT(assigned_clients.name, ", ") AS assigned_client_names';

    private const LIST_JOINS = 'FROM print_jobs j
             INNER JOIN endpoints e ON e.id = j.endpoint_id
             LEFT JOIN clients c ON c.id = j.client_id
             LEFT JOIN client_endpoint_assignments cea ON cea.endpoint_id = j.endpoint_id
             LEFT JOIN clients assigned_clients ON assigned_clients.id = cea.client_id';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function waiting(int $limit = 100): array
    {
        return self::listByStatus("j.status IN ('pending', 'reserved', 'printing', 'failed')", $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function archived(int $limit = 100): array
    {
        return self::listByStatus("j.status IN ('printed', 'cancelled')", $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT j.id, j.status, j.payload, j.content_type, j.metadata_json, j.created_at, j.picked_up_at, j.completed_at, j.failed_at, j.last_error,
                e.name AS endpoint_name, c.name AS reserved_client_name,
                GROUP_CONCAT(assigned_clients.name, ", ") AS assigned_client_names
             ' . self::LIST_JOINS . '
             WHERE j.id = :id
             GROUP BY j.id'
        );
        $stmt->execute([':id' => $id]);
        $job = $stmt->fetch();

        return is_array($job) ? $job : null;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM print_jobs WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function listByStatus(string $condition, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::LIST_COLUMNS . '
             ' . self::LIST_JOINS . '
             WHERE ' . $condition . '
             GROUP BY j.id
             ORDER BY j.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
