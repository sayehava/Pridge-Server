<?php

declare(strict_types=1);

namespace Pridge\Repositories;

use PDO;
use Pridge\Database;
use Pridge\Support\Clock;

final class LoginAttemptRepository
{
    private const TIER1_MAX_ATTEMPTS = 3;
    private const TIER1_WINDOW_SECONDS = 900;
    private const TIER1_LOCK_SECONDS = 900;

    private const TIER2_MAX_ATTEMPTS = 2;
    private const TIER2_LOCK_SECONDS = 86400;

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
            self::insert($username, $ipHash, 1, null, 0, $now);
            return;
        }

        $escalationLevel = (int) $attempt['escalation_level'];

        if ($escalationLevel === 0) {
            $firstAttemptTime = strtotime((string) $attempt['first_attempt_at']);
            $withinWindow = $firstAttemptTime !== false && $firstAttemptTime >= (time() - self::TIER1_WINDOW_SECONDS);
            $attempts = $withinWindow ? ((int) $attempt['attempts'] + 1) : 1;
            $firstAttemptAt = $withinWindow ? (string) $attempt['first_attempt_at'] : $now;

            if ($attempts >= self::TIER1_MAX_ATTEMPTS) {
                $lockedUntil = Clock::addSeconds(self::TIER1_LOCK_SECONDS);
                $escalationLevel = 1;
                $attempts = 0;
            } else {
                $lockedUntil = null;
            }
        } else {
            // Already served one lockout for this username/source: two more failures locks for 24 hours.
            $attempts = (int) $attempt['attempts'] + 1;
            $firstAttemptAt = (string) $attempt['first_attempt_at'];

            if ($attempts >= self::TIER2_MAX_ATTEMPTS) {
                $lockedUntil = Clock::addSeconds(self::TIER2_LOCK_SECONDS);
                $attempts = 0;
            } else {
                $lockedUntil = null;
            }
        }

        $stmt = Database::connection()->prepare(
            'UPDATE login_attempts
             SET attempts = :attempts, locked_until = :locked_until, escalation_level = :escalation_level,
                 first_attempt_at = :first_attempt_at, last_attempt_at = :last_attempt_at
             WHERE username = :username AND ip_hash = :ip_hash'
        );
        $stmt->execute([
            ':username' => $username,
            ':ip_hash' => $ipHash,
            ':attempts' => $attempts,
            ':locked_until' => $lockedUntil,
            ':escalation_level' => $escalationLevel,
            ':first_attempt_at' => $firstAttemptAt,
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
            'SELECT username, ip_hash, attempts, locked_until, escalation_level, first_attempt_at, last_attempt_at
             FROM login_attempts
             WHERE username = :username AND ip_hash = :ip_hash'
        );
        $stmt->execute([':username' => $username, ':ip_hash' => $ipHash]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($attempt) ? $attempt : null;
    }

    private static function insert(string $username, string $ipHash, int $attempts, ?string $lockedUntil, int $escalationLevel, string $now): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (username, ip_hash, attempts, locked_until, escalation_level, first_attempt_at, last_attempt_at)
             VALUES (:username, :ip_hash, :attempts, :locked_until, :escalation_level, :first_attempt_at, :last_attempt_at)'
        );
        $stmt->execute([
            ':username' => $username,
            ':ip_hash' => $ipHash,
            ':attempts' => $attempts,
            ':locked_until' => $lockedUntil,
            ':escalation_level' => $escalationLevel,
            ':first_attempt_at' => $now,
            ':last_attempt_at' => $now,
        ]);
    }
}
