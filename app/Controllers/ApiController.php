<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\ApiRepository;
use PrintBridge\Support\Http;

final class ApiController
{
    public static function receiveJob(): void
    {
        $endpoint = self::endpointFromBearer();

        if ($endpoint === null) {
            Http::json(['error' => 'Invalid endpoint token.'], 401);
            return;
        }

        $payload = file_get_contents('php://input');

        if ($payload === false || $payload === '') {
            Http::json(['error' => 'Print payload is required.'], 400);
            return;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? 'application/octet-stream';
        $metadataJson = self::metadataJson();
        $jobId = ApiRepository::storeJob((int) $endpoint['id'], $payload, $contentType, $metadataJson);

        Http::json(['job_id' => $jobId, 'status' => 'pending'], 201);
    }

    public static function listEndpointClients(): void
    {
        $endpoint = self::endpointFromPostToken();

        if ($endpoint === null) {
            Http::json(['error' => 'Invalid endpoint token.'], 401);
            return;
        }

        Http::json([
            'endpoint' => ['id' => (int) $endpoint['id'], 'name' => $endpoint['name']],
            'clients' => ApiRepository::listClientsForEndpoint((int) $endpoint['id']),
        ]);
    }

    public static function authenticateClient(): void
    {
        $input = Http::jsonInput();
        $token = isset($input['token']) && is_string($input['token']) ? $input['token'] : null;

        if ($token === null || $token === '') {
            $token = Http::bearerToken();
        }

        if ($token === null) {
            Http::json(['error' => 'Client token is required.'], 400);
            return;
        }

        $client = ApiRepository::findClientByToken($token);

        if ($client === null) {
            Http::json(['error' => 'Invalid client token.'], 401);
            return;
        }

        $sessionToken = ApiRepository::createClientSession((int) $client['id']);
        ApiRepository::updateHeartbeat((int) $client['id']);

        Http::json([
            'token' => $sessionToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'client' => ['id' => (int) $client['id'], 'name' => $client['name']],
        ]);
    }

    public static function listClientJobs(): void
    {
        $client = self::clientFromBearer();

        if ($client === null) {
            Http::json(['error' => 'Invalid client session.'], 401);
            return;
        }

        Http::json(['jobs' => ApiRepository::listJobsForClient((int) $client['id'])]);
    }

    public static function reserveClientJob(): void
    {
        $client = self::clientFromBearer();

        if ($client === null) {
            Http::json(['error' => 'Invalid client session.'], 401);
            return;
        }

        $job = ApiRepository::reserveJobForClient((int) $client['id']);

        if ($job === null) {
            Http::json(['job' => null]);
            return;
        }

        Http::json(['job' => self::jobPayloadResponse($job)]);
    }

    public static function markPrinting(int $jobId): void
    {
        self::markJob($jobId, 'printing');
    }

    public static function markPrinted(int $jobId): void
    {
        self::markJob($jobId, 'printed');
    }

    public static function markFailed(int $jobId): void
    {
        $input = Http::jsonInput();
        $error = isset($input['error']) && is_string($input['error']) ? substr($input['error'], 0, 500) : null;
        self::markJob($jobId, 'failed', $error);
    }

    public static function heartbeat(): void
    {
        $client = self::clientFromBearer();

        if ($client === null) {
            Http::json(['error' => 'Invalid client session.'], 401);
            return;
        }

        ApiRepository::updateHeartbeat((int) $client['id']);
        Http::json(['status' => 'ok']);
    }

    /**
     * @return array{id:int,name:string}|null
     */
    private static function endpointFromBearer(): ?array
    {
        $token = Http::bearerToken();

        if ($token === null) {
            $token = $_SERVER['HTTP_X_ENDPOINT_TOKEN'] ?? null;
        }

        return is_string($token) ? ApiRepository::findEndpointByToken($token) : null;
    }

    /**
     * @return array{id:int,name:string}|null
     */
    private static function endpointFromPostToken(): ?array
    {
        $input = Http::jsonInput();
        $token = isset($input['token']) && is_string($input['token']) ? $input['token'] : null;

        if ($token === null || $token === '') {
            $token = Http::bearerToken();
        }

        if ($token === null || $token === '') {
            $token = $_POST['token'] ?? null;
        }

        return is_string($token) ? ApiRepository::findEndpointByToken($token) : null;
    }

    /**
     * @return array{id:int,name:string}|null
     */
    private static function clientFromBearer(): ?array
    {
        $token = Http::bearerToken();

        return $token === null ? null : ApiRepository::findClientBySessionToken($token);
    }

    private static function metadataJson(): ?string
    {
        $metadata = $_SERVER['HTTP_X_PRINTBRIDGE_METADATA'] ?? null;

        if (!is_string($metadata) || $metadata === '') {
            return null;
        }

        json_decode($metadata);

        return json_last_error() === JSON_ERROR_NONE ? $metadata : json_encode(['raw' => $metadata]);
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private static function jobPayloadResponse(array $job): array
    {
        return [
            'id' => (int) $job['id'],
            'endpoint_id' => (int) $job['endpoint_id'],
            'content_type' => $job['content_type'],
            'metadata' => isset($job['metadata_json']) && is_string($job['metadata_json']) ? json_decode($job['metadata_json'], true) : null,
            'status' => $job['status'],
            'created_at' => $job['created_at'],
            'payload_base64' => base64_encode((string) $job['payload']),
        ];
    }

    private static function markJob(int $jobId, string $status, ?string $error = null): void
    {
        $client = self::clientFromBearer();

        if ($client === null) {
            Http::json(['error' => 'Invalid client session.'], 401);
            return;
        }

        if (!ApiRepository::markJob((int) $client['id'], $jobId, $status, $error)) {
            Http::json(['error' => 'Job not found for this client.'], 404);
            return;
        }

        Http::json(['job_id' => $jobId, 'status' => $status]);
    }
}
