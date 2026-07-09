<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PDO;
use PrintBridge\Database;
use PrintBridge\Support\Clock;

final class LoginAttemptRepository
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private const LOCK_SECONDS = 900;

    public static function isLocked(string $username, string $ipHash): bool
    {
        $attempt = self::find($username, $ipHash);

        return $attempt !== null
            && !empty($attempt['locked_until'])
            && (string) $attempt['locked_until'] > Clock::now();
    }

    public static function recordFailure(string $username, string $ipHash): void
    {
        $now = Clock::now();
        $attempt = self::find($username, $ipHash);

        if ($attempt === null) {
            self::insert($username, $ipHash, 1, null, $now);
            return;
        }

        $firstAttemptTime = strtotime((string) $attempt['first_attempt_at']);
        $withinWindow = $firstAttemptTime !== false && $firstAttemptTime >= (time() - self::WINDOW_SECONDS);
        $attempts = $withinWindow ? ((int) $attempt['attempts'] + 1) : 1;
        $lockedUntil = $attempts >= self::MAX_ATTEMPTS ? Clock::addSeconds(self::LOCK_SECONDS) : null;

        $stmt = Database::connection()->prepare(
            'UPDATE login_attempts
             SET attempts = :attempts, locked_until = :locked_until, first_attempt_at = :first_attempt_at, last_attempt_at = :last_attempt_at
             WHERE username = :username AND ip_hash = :ip_hash'
        );
        $stmt->execute([
            ':username' => $username,
            ':ip_hash' => $ipHash,
            ':attempts' => $attempts,
            ':locked_until' => $lockedUntil,
            ':first_attempt_at' => $withinWindow ? $attempt['first_attempt_at'] : $now,
            ':last_attempt_at' => $now,
        ]);
    }

    public static function clear(string $username, string $ipHash): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM login_attempts WHERE username = :username AND ip_hash = :ip_hash');
        $stmt->execute([':username' => $username, ':ip_hash' => $ipHash]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function find(string $username, string $ipHash): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT username, ip_hash, attempts, locked_until, first_attempt_at, last_attempt_at
             FROM login_attempts
             WHERE username = :username AND ip_hash = :ip_hash'
        );
        $stmt->execute([':username' => $username, ':ip_hash' => $ipHash]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($attempt) ? $attempt : null;
    }

    private static function insert(string $username, string $ipHash, int $attempts, ?string $lockedUntil, string $now): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (username, ip_hash, attempts, locked_until, first_attempt_at, last_attempt_at)
             VALUES (:username, :ip_hash, :attempts, :locked_until, :first_attempt_at, :last_attempt_at)'
        );
        $stmt->execute([
            ':username' => $username,
            ':ip_hash' => $ipHash,
            ':attempts' => $attempts,
            ':locked_until' => $lockedUntil,
            ':first_attempt_at' => $now,
            ':last_attempt_at' => $now,
        ]);
    }
}
