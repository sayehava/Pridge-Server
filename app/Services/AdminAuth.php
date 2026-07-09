<?php

declare(strict_types=1);

namespace PrintBridge\Services;

use PrintBridge\Repositories\AdminRepository;
use PrintBridge\Support\Http;

final class AdminAuth
{
    public static function userId(): ?int
    {
        $id = $_SESSION['admin_user_id'] ?? null;

        return is_int($id) ? $id : null;
    }

    public static function requireLogin(): void
    {
        if (self::userId() === null) {
            Http::redirect('/login');
            exit;
        }
    }

    public static function login(string $username, string $password): bool
    {
        $admin = AdminRepository::findByUsername($username);

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}
