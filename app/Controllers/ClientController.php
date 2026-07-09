<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\ClientRepository;
use PrintBridge\Repositories\EndpointRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Flash;
use PrintBridge\Support\Http;
use PrintBridge\Support\View;

final class ClientController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        View::render('clients/index', [
            'clients' => ClientRepository::all(),
            'endpoints' => EndpointRepository::all(),
            'token' => Flash::pull('client_token'),
            'error' => Flash::pull('error'),
        ]);
    }

    public static function create(): void
    {
        AdminAuth::requireLogin();
        $name = Http::post('name');
        $submittedEndpointIds = $_POST['endpoint_ids'] ?? [];
        $endpointIds = is_array($submittedEndpointIds) ? array_map('intval', $submittedEndpointIds) : [];

        if ($name === '') {
            Flash::set('error', 'error.name_required');
            Http::redirect('/clients');
            return;
        }

        Flash::set('client_token', ClientRepository::create($name, $endpointIds));
        Http::redirect('/clients');
    }

    public static function toggle(int $id): void
    {
        AdminAuth::requireLogin();
        ClientRepository::toggle($id);
        Http::redirect('/clients');
    }

    public static function delete(int $id): void
    {
        AdminAuth::requireLogin();
        ClientRepository::delete($id);
        Http::redirect('/clients');
    }
}
