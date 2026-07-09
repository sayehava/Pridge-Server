<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\QueueRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\View;

final class QueueController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        View::render('queue/index', ['jobs' => QueueRepository::recent()]);
    }
}
