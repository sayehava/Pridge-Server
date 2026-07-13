<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PDO;
use PrintBridge\Database;
use PrintBridge\Support\Clock;
use PrintBridge\Support\Security;

final class ApiRepository
{
    /**
     * @return array{id:int,name:string}|null
     */
    public static function findEndpointByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name FROM endpoints WHERE token_hash = :token_hash AND enabled = 1');
        $stmt->execute([':token_hash' => Security::hashToken($token)]);
        $endpoint = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($endpoint) ? $endpoint : null;
    }

    /**
     * @return array{id:int,name:string}|null
     */
    public static function findClientByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name FROM clients WHERE token_hash = :token_hash AND enabled = 1');
        $stmt->execute([':token_hash' => Security::hashToken($token)]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($client) ? $client : null;
    }

    /**
     * @return array{id:int,name:string}|null
     */
    public static function findClientBySessionToken(string $token): ?array
    {
        $now = Clock::now();
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.name
             FROM client_sessions s
             INNER JOIN clients c ON c.id = s.client_id
             WHERE s.token_hash = :token_hash AND s.expires_at > :now AND c.enabled = 1'
        );
        $stmt->execute([':token_hash' => Security::hashToken($token), ':now' => $now]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($client)) {
            $update = Database::connection()->prepare('UPDATE client_sessions SET last_seen_at = :now WHERE token_hash = :token_hash');
            $update->execute([':now' => $now, ':token_hash' => Security::hashToken($token)]);

            return $client;
        }

        return null;
    }

    public static function createClientSession(int $clientId): string
    {
        $token = Security::randomToken();
        $now = Clock::now();
        $stmt = Database::connection()->prepare(
            'INSERT INTO client_sessions (client_id, token_hash, expires_at, created_at, last_seen_at)
             VALUES (:client_id, :token_hash, :expires_at, :created_at, :last_seen_at)'
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':token_hash' => Security::hashToken($token),
            ':expires_at' => Clock::addSeconds(86400),
            ':created_at' => $now,
            ':last_seen_at' => $now,
        ]);

        return $token;
    }

    public static function storeJob(int $endpointId, string $payload, string $contentType, ?string $metadataJson): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO print_jobs (endpoint_id, payload, content_type, metadata_json, status, created_at)
             VALUES (:endpoint_id, :payload, :content_type, :metadata_json, :status, :created_at)'
        );
        $stmt->bindValue(':endpoint_id', $endpointId, PDO::PARAM_INT);
        $stmt->bindValue(':payload', $payload, PDO::PARAM_LOB);
        $stmt->bindValue(':content_type', $contentType);
        $stmt->bindValue(':metadata_json', $metadataJson);
        $stmt->bindValue(':status', 'pending');
        $stmt->bindValue(':created_at', Clock::now());
        $stmt->execute();

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listClientsForEndpoint(int $endpointId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.name, c.enabled, c.last_seen_at, c.created_at
             FROM clients c
             INNER JOIN client_endpoint_assignments cea ON cea.client_id = c.id
             WHERE cea.endpoint_id = :endpoint_id
             ORDER BY c.name ASC'
        );
        $stmt->execute([':endpoint_id' => $endpointId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listJobsForClient(int $clientId): array
    {
        self::releaseExpiredReservations();
        $stmt = Database::connection()->prepare(
            "SELECT j.id, j.endpoint_id, e.name AS endpoint_name, j.content_type, j.metadata_json, j.status, j.created_at, j.picked_up_at
             FROM print_jobs j
             INNER JOIN endpoints e ON e.id = j.endpoint_id
             INNER JOIN client_endpoint_assignments cea ON cea.endpoint_id = j.endpoint_id
             WHERE cea.client_id = :client_id
                AND j.status IN ('pending', 'reserved', 'printing', 'failed')
             ORDER BY j.created_at ASC
             LIMIT 100"
        );
        $stmt->execute([':client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listEndpointsForClient(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.id, e.name, e.enabled,
                CASE WHEN cea.client_id IS NULL THEN 0 ELSE 1 END AS assigned
             FROM endpoints e
             LEFT JOIN client_endpoint_assignments cea
                ON cea.endpoint_id = e.id AND cea.client_id = :client_id
             ORDER BY e.name ASC, e.id ASC'
        );
        $stmt->execute([':client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /**
     * @param array<int, int> $endpointIds
     */
    public static function syncEndpointsForClient(int $clientId, array $endpointIds): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        $delete = $db->prepare('DELETE FROM client_endpoint_assignments WHERE client_id = :client_id');
        $delete->execute([':client_id' => $clientId]);

        $insert = $db->prepare(
            'INSERT OR IGNORE INTO client_endpoint_assignments (client_id, endpoint_id, created_at)
             SELECT :client_id, id, :created_at FROM endpoints WHERE id = :endpoint_id'
        );
        foreach (array_unique($endpointIds) as $endpointId) {
            $insert->execute([
                ':client_id' => $clientId,
                ':endpoint_id' => $endpointId,
                ':created_at' => Clock::now(),
            ]);
        }

        $db->commit();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function reserveJobForClient(int $clientId, int $timeoutSeconds = 300): ?array
    {
        self::releaseExpiredReservations();
        $db = Database::connection();
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT j.*
             FROM print_jobs j
             INNER JOIN client_endpoint_assignments cea ON cea.endpoint_id = j.endpoint_id
             WHERE cea.client_id = :client_id AND j.status = 'pending'
             ORDER BY j.created_at ASC
             LIMIT 1"
        );
        $stmt->execute([':client_id' => $clientId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($job)) {
            $db->commit();
            return null;
        }

        $update = $db->prepare(
            "UPDATE print_jobs
             SET status = 'reserved', client_id = :client_id, reserved_until = :reserved_until, picked_up_at = COALESCE(picked_up_at, :now)
             WHERE id = :id AND status = 'pending'"
        );
        $now = Clock::now();
        $update->execute([
            ':client_id' => $clientId,
            ':reserved_until' => Clock::addSeconds($timeoutSeconds),
            ':now' => $now,
            ':id' => (int) $job['id'],
        ]);

        if ($update->rowCount() !== 1) {
            $db->commit();
            return null;
        }

        $db->commit();
        $job['status'] = 'reserved';
        $job['client_id'] = $clientId;

        return $job;
    }

    public static function markJob(int $clientId, int $jobId, string $status, ?string $error = null): bool
    {
        $now = Clock::now();
        $fields = [
            'status = :status',
            'client_id = :client_id',
        ];
        $params = [
            ':status' => $status,
            ':client_id' => $clientId,
            ':id' => $jobId,
        ];

        if ($status === 'printing') {
            $fields[] = 'picked_up_at = COALESCE(picked_up_at, :now)';
            $params[':now'] = $now;
        }

        if ($status === 'printed') {
            $fields[] = 'completed_at = :now';
            $fields[] = 'reserved_until = NULL';
            $params[':now'] = $now;
        }

        if ($status === 'failed') {
            $fields[] = 'failed_at = :now';
            $fields[] = 'last_error = :last_error';
            $fields[] = 'reserved_until = NULL';
            $params[':now'] = $now;
            $params[':last_error'] = $error;
        }

        $sql = 'UPDATE print_jobs SET ' . implode(', ', $fields) . '
            WHERE id = :id
              AND endpoint_id IN (SELECT endpoint_id FROM client_endpoint_assignments WHERE client_id = :client_id)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    public static function updateHeartbeat(int $clientId): void
    {
        $stmt = Database::connection()->prepare('UPDATE clients SET last_seen_at = :last_seen_at WHERE id = :id');
        $stmt->execute([':id' => $clientId, ':last_seen_at' => Clock::now()]);
    }

    private static function releaseExpiredReservations(): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE print_jobs
             SET status = 'pending', client_id = NULL, reserved_until = NULL
             WHERE status = 'reserved' AND reserved_until IS NOT NULL AND reserved_until <= :now"
        );
        $stmt->execute([':now' => Clock::now()]);
    }
}
