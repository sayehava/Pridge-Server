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
        'action.disable' => 'Disable',
        'action.enable' => 'Enable',
        'setup.title' => 'Create the first admin account',
        'setup.help' => 'This setup screen is available only until the first admin user exists.',
        'login.title' => 'Admin login',
        'field.username' => 'Username',
        'field.password' => 'Password',
        'field.name' => 'Name',
        'field.assign_endpoints' => 'Assigned endpoints',
        'dashboard.title' => 'Dashboard',
        'dashboard.subtitle' => 'Server status and print queue overview',
        'metric.endpoints' => 'Endpoints',
        'metric.clients' => 'Clients',
        'metric.waiting_jobs' => 'Waiting jobs',
        'metric.failed_jobs' => 'Failed jobs',
        'status.ready' => 'Ready',
        'status.enabled' => 'Enabled',
        'status.disabled' => 'Disabled',
        'endpoints.title' => 'Endpoints',
        'endpoints.subtitle' => 'Plugin destinations that can submit print jobs',
        'endpoints.create' => 'Create endpoint',
        'endpoints.token_created' => 'Endpoint token created. Store it now because it will not be shown again.',
        'clients.title' => 'Clients',
        'clients.subtitle' => 'Office applications that can pull and report print jobs',
        'clients.create' => 'Create client',
        'clients.token_created' => 'Client token created. Store it now because it will not be shown again.',
        'table.name' => 'Name',
        'table.status' => 'Status',
        'table.created' => 'Created',
        'table.actions' => 'Actions',
        'table.assignments' => 'Assignments',
        'empty.endpoints' => 'No endpoints have been created.',
        'empty.clients' => 'No clients have been created.',
        'error.invalid_login' => 'Invalid username or password.',
        'error.setup_exists' => 'Setup is already complete.',
        'error.required_fields' => 'All fields are required.',
        'error.password_length' => 'Password must be at least 12 characters.',
        'error.name_required' => 'Name is required.',
    ];

    public static function get(string $key): string
    {
        return self::STRINGS[$key] ?? $key;
    }
}
