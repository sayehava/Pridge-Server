<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;

final class QueueRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recent(int $limit = 100): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT j.id, j.status, j.content_type, j.created_at, j.picked_up_at, j.completed_at, j.failed_at, j.last_error,
                e.name AS endpoint_name, c.name AS reserved_client_name,
                GROUP_CONCAT(assigned_clients.name, ", ") AS assigned_client_names
             FROM print_jobs j
             INNER JOIN endpoints e ON e.id = j.endpoint_id
             LEFT JOIN clients c ON c.id = j.client_id
             LEFT JOIN client_endpoint_assignments cea ON cea.endpoint_id = j.endpoint_id
             LEFT JOIN clients assigned_clients ON assigned_clients.id = cea.client_id
             GROUP BY j.id
             ORDER BY j.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
