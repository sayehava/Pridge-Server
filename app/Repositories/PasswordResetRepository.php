<?php

declare(strict_types=1);

namespace Pridge\Repositories;

use PDO;
use Pridge\Database;
use Pridge\Support\Clock;
use Pridge\Support\Security;

final class PasswordResetRepository
{
    public static function create(int $adminUserId): string
    {
        $token = Security::randomToken();
        $stmt = Database::connection()->prepare(
            'INSERT INTO password_resets (admin_user_id, token_hash, expires_at, created_at)
             VALUES (:admin_user_id, :token_hash, :expires_at, :created_at)'
        );
        $stmt->execute([
            ':admin_user_id' => $adminUserId,
            ':token_hash' => Security::hashToken($token),
            ':expires_at' => Clock::addSeconds(3600),
            ':created_at' => Clock::now(),
        ]);

        return $token;
    }

    /**
     * @return array{id:int,admin_user_id:int}|null
     */
    public static function findValid(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, admin_user_id
             FROM password_resets
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > :now'
        );
        $stmt->execute([':token_hash' => Security::hashToken($token), ':now' => Clock::now()]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($reset) ? $reset : null;
    }

    public static function markUsed(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = :used_at WHERE id = :id');
        $stmt->execute([':id' => $id, ':used_at' => Clock::now()]);
    }
}
