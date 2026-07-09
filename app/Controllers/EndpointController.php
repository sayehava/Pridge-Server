<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

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
            'token' => Flash::pull('endpoint_token'),
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
}
