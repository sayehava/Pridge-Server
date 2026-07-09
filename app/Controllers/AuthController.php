<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\AdminRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Http;
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
        $password = Http::post('password');

        if ($username === '' || $password === '') {
            View::render('auth/setup', ['error' => 'error.required_fields']);
            return;
        }

        if (strlen($password) < 12) {
            View::render('auth/setup', ['error' => 'error.password_length']);
            return;
        }

        AdminRepository::create($username, $password);
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
        if (AdminAuth::login(Http::post('username'), Http::post('password'))) {
            Http::redirect('/');
            return;
        }

        View::render('auth/login', ['error' => 'error.invalid_login']);
    }

    public static function logout(): void
    {
        AdminAuth::logout();
        Http::redirect('/login');
    }
}
