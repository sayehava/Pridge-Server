<?php

declare(strict_types=1);

namespace PrintBridge\Repositories;

use PDO;
use PrintBridge\Database;
use PrintBridge\Support\Clock;

final class AdminRepository
{
    public static function hasAdmins(): bool
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM admin_users');

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $username, string $password): void
    {
        $now = Clock::now();
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_users (username, password_hash, created_at, updated_at) VALUES (:username, :password_hash, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    /**
     * @return array{id:int,username:string,password_hash:string}|null
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($admin) ? $admin : null;
    }
}
