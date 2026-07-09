<?php

declare(strict_types=1);

namespace PrintBridge\Support;

final class Text
{
    /** @var array<string, string> */
    private const STRINGS = [
        'app.name' => 'PrintBridge Server',
        'nav.dashboard' => 'Dashboard',
        'nav.endpoints' => 'Endpoints',
        'nav.clients' => 'Clients',
        'nav.queue' => 'Queue',
        'nav.settings' => 'Settings',
        'action.login' => 'Log in',
        'action.logout' => 'Log out',
        'action.create' => 'Create',
        'setup.title' => 'Create the first admin account',
        'setup.help' => 'This setup screen is available only until the first admin user exists.',
        'login.title' => 'Admin login',
        'field.username' => 'Username',
        'field.password' => 'Password',
        'field.name' => 'Name',
        'dashboard.title' => 'Dashboard',
        'dashboard.subtitle' => 'Server status and print queue overview',
        'metric.endpoints' => 'Endpoints',
        'metric.clients' => 'Clients',
        'metric.waiting_jobs' => 'Waiting jobs',
        'metric.failed_jobs' => 'Failed jobs',
        'status.ready' => 'Ready',
        'error.invalid_login' => 'Invalid username or password.',
        'error.setup_exists' => 'Setup is already complete.',
        'error.required_fields' => 'All fields are required.',
        'error.password_length' => 'Password must be at least 12 characters.',
    ];

    public static function get(string $key): string
    {
        return self::STRINGS[$key] ?? $key;
    }
}
