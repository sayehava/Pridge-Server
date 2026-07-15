<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\AdminRepository;
use PrintBridge\Repositories\LoginAttemptRepository;
use PrintBridge\Repositories\PasswordResetRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Services\Mailer;
use PrintBridge\Support\Http;
use PrintBridge\Support\Security;
use PrintBridge\Support\View;

final class AuthController
{
    public static function setupForm(): void
    {
        if (AdminRepository::hasAdmins()) {
            Http::redirect('/login');
            return;
        }

        View::render('auth/setup');
    }

    public static function setup(): void
    {
        if (AdminRepository::hasAdmins()) {
            View::render('auth/setup', ['error' => 'error.setup_exists']);
            return;
        }

        $username = Http::post('username');
        $email = Http::post('email');
        $password = Http::post('password');

        if ($username === '' || $password === '') {
            View::render('auth/setup', ['error' => 'error.required_fields']);
            return;
        }

        if (strlen($password) < 12) {
            View::render('auth/setup', ['error' => 'error.password_length']);
            return;
        }

        AdminRepository::create($username, $password, $email !== '' ? $email : null);
        AdminAuth::login($username, $password);
        Http::redirect('/');
    }

    public static function loginForm(): void
    {
        if (!AdminRepository::hasAdmins()) {
            Http::redirect('/setup');
            return;
        }

        View::render('auth/login');
    }

    public static function login(): void
    {
        $username = Http::post('username');
        $password = Http::post('password');
        $ipHash = Security::requestIpHash();

        if (LoginAttemptRepository::isLocked($username, $ipHash)) {
            View::render('auth/login', ['error' => 'error.login_throttled']);
            return;
        }

        if (AdminAuth::login($username, $password)) {
            LoginAttemptRepository::clear($username, $ipHash);
            Http::redirect('/');
            return;
        }

        LoginAttemptRepository::recordFailure($username, $ipHash);
        View::render('auth/login', ['error' => 'error.invalid_login']);
    }

    public static function logout(): void
    {
        AdminAuth::logout();
        Http::redirect('/login');
    }

    public static function forgotPasswordForm(): void
    {
        View::render('auth/forgot-password');
    }

    public static function forgotPassword(): void
    {
        $username = Http::post('username');
        $admin = $username !== '' ? AdminRepository::findByUsername($username) : null;

        if ($admin !== null && !empty($admin['email'])) {
            $token = PasswordResetRepository::create((int) $admin['id']);
            $link = self::baseUrl() . '/reset-password?token=' . rawurlencode($token);
            $subject = 'PrintBridge password reset';
            $body = "Use this link to reset your PrintBridge admin password:\n\n" . $link . "\n\nThis link expires in one hour.";
            Mailer::send((string) $admin['email'], $subject, $body);
        }

        View::render('auth/forgot-password', ['message' => 'password.forgot_sent']);
    }

    public static function resetPasswordForm(): void
    {
        $token = $_GET['token'] ?? '';
        View::render('auth/reset-password', ['token' => is_string($token) ? $token : '']);
    }

    public static function resetPassword(): void
    {
        $token = Http::post('token');
        $password = Http::post('password');
        $reset = $token !== '' ? PasswordResetRepository::findValid($token) : null;

        if ($reset === null) {
            View::render('auth/reset-password', ['error' => 'error.invalid_reset_token', 'token' => '']);
            return;
        }

        if (strlen($password) < 12) {
            View::render('auth/reset-password', ['error' => 'error.password_length', 'token' => $token]);
            return;
        }

        AdminRepository::updatePassword((int) $reset['admin_user_id'], $password);
        PasswordResetRepository::markUsed((int) $reset['id']);
        View::render('auth/login', ['message' => 'password.reset_complete']);
    }

    private static function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
