<?php

declare(strict_types=1);

namespace Pridge\Repositories;

use PDO;
use Pridge\Database;
use Pridge\Support\Clock;

final class AdminRepository
{
    public static function hasAdmins(): bool
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM admin_users');

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $username, string $password, ?string $email = null): void
    {
        $now = Clock::now();
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_users (username, email, password_hash, created_at, updated_at) VALUES (:username, :email, :password_hash, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    /**
     * @return array{id:int,username:string,email:?string,password_hash:string}|null
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, username, email, password_hash FROM admin_users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($admin) ? $admin : null;
    }

    /**
     * @return array{id:int,username:string,email:?string,password_hash:string}|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, username, email, password_hash FROM admin_users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($admin) ? $admin : null;
    }

    public static function updatePassword(int $id, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE admin_users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':updated_at' => Clock::now(),
        ]);
    }
}
