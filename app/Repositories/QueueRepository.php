<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PrintBridge\Database;

final class QueueRepository
{
    private const WAITING_STATUSES = "'pending', 'reserved', 'printing', 'failed'";
    private const ARCHIVED_STATUSES = "'printed', 'cancelled'";

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
    public static function waiting(?int $limit = null, int $offset = 0): array
    {
        return self::listByStatus('j.status IN (' . self::WAITING_STATUSES . ')', $limit, $offset);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function archived(?int $limit = null, int $offset = 0): array
    {
        return self::listByStatus('j.status IN (' . self::ARCHIVED_STATUSES . ')', $limit, $offset);
    }

    public static function countWaiting(): int
    {
        return self::countByStatus(self::WAITING_STATUSES);
    }

    public static function countArchived(): int
    {
        return self::countByStatus(self::ARCHIVED_STATUSES);
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

    public static function statusOf(int $id): ?string
    {
        $stmt = Database::connection()->prepare('SELECT status FROM print_jobs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $status = $stmt->fetchColumn();

        return is_string($status) ? $status : null;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM print_jobs WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * @param array<int, int> $ids
     */
    public static function deleteByIds(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare('DELETE FROM print_jobs WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    public static function deleteAllWaiting(): int
    {
        $stmt = Database::connection()->prepare('DELETE FROM print_jobs WHERE status IN (' . self::WAITING_STATUSES . ')');
        $stmt->execute();

        return $stmt->rowCount();
    }

    public static function deleteAllArchived(): int
    {
        $stmt = Database::connection()->prepare('DELETE FROM print_jobs WHERE status IN (' . self::ARCHIVED_STATUSES . ')');
        $stmt->execute();

        return $stmt->rowCount();
    }

    public static function deleteArchivedOlderThan(string $cutoff, int $batchSize): int
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM print_jobs WHERE id IN (
                SELECT id FROM print_jobs
                WHERE status IN (' . self::ARCHIVED_STATUSES . ')
                  AND COALESCE(completed_at, failed_at, created_at) < :cutoff
                LIMIT :batch
             )'
        );
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->bindValue(':batch', $batchSize, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    private static function countByStatus(string $statuses): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM print_jobs WHERE status IN (' . $statuses . ')');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function listByStatus(string $condition, ?int $limit, int $offset): array
    {
        $sql = 'SELECT ' . self::LIST_COLUMNS . '
             ' . self::LIST_JOINS . '
             WHERE ' . $condition . '
             GROUP BY j.id
             ORDER BY j.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = Database::connection()->prepare($sql);

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
