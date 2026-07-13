<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\ClientRepository;
use PrintBridge\Repositories\EndpointRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Flash;
use PrintBridge\Support\Http;
use PrintBridge\Support\View;

final class EndpointController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        View::render('endpoints/index', [
            'endpoints' => EndpointRepository::all(),
            'clients' => ClientRepository::all(),
            'token' => Flash::pull('endpoint_token'),
            'tokenMessage' => Flash::pull('endpoint_token_message') ?? 'endpoints.token_created',
            'error' => Flash::pull('error'),
        ]);
    }

    public static function create(): void
    {
        AdminAuth::requireLogin();
        $name = Http::post('name');

        if ($name === '') {
            Flash::set('error', 'error.name_required');
            Http::redirect('/endpoints');
            return;
        }

        Flash::set('endpoint_token', EndpointRepository::create($name));
        Http::redirect('/endpoints');
    }

    public static function toggle(int $id): void
    {
        AdminAuth::requireLogin();
        EndpointRepository::toggle($id);
        Http::redirect('/endpoints');
    }

    public static function rename(int $id): void
    {
        AdminAuth::requireLogin();
        $name = Http::post('name');

        if ($name === '') {
            Flash::set('error', 'error.name_required');
            Http::redirect('/endpoints');
            return;
        }

        EndpointRepository::rename($id, $name);
        Http::redirect('/endpoints');
    }

    public static function regenerateToken(int $id): void
    {
        AdminAuth::requireLogin();
        Flash::set('endpoint_token', EndpointRepository::regenerateToken($id));
        Flash::set('endpoint_token_message', 'endpoints.token_regenerated');
        Http::redirect('/endpoints');
    }

    public static function delete(int $id): void
    {
        AdminAuth::requireLogin();

        if (!EndpointRepository::delete($id)) {
            Flash::set('error', 'error.endpoint_has_jobs');
        }

        Http::redirect('/endpoints');
    }

    public static function assignClients(int $id): void
    {
        AdminAuth::requireLogin();
        $submittedClientIds = $_POST['client_ids'] ?? [];
        $clientIds = is_array($submittedClientIds) ? array_map('intval', $submittedClientIds) : [];

        EndpointRepository::syncClients($id, $clientIds);
        Http::redirect('/endpoints');
    }
}
