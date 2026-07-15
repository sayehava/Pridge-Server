<?php

declare(strict_types=1);

namespace Pridge\Controllers;

use Pridge\Database;
use Pridge\Repositories\AdminRepository;
use Pridge\Services\AdminAuth;
use Pridge\Support\Http;
use Pridge\Support\View;

final class AdminController
{
    public static function dashboard(): void
    {
        if (!AdminRepository::hasAdmins()) {
            Http::redirect('/setup');
            return;
        }

        AdminAuth::requireLogin();

        $db = Database::connection();
        $counts = [
            'endpoints' => (int) $db->query('SELECT COUNT(*) FROM endpoints')->fetchColumn(),
            'clients' => (int) $db->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
            'pending' => (int) $db->query("SELECT COUNT(*) FROM print_jobs WHERE status IN ('pending', 'reserved', 'printing')")->fetchColumn(),
            'failed' => (int) $db->query("SELECT COUNT(*) FROM print_jobs WHERE status = 'failed'")->fetchColumn(),
        ];

        View::render('dashboard', ['counts' => $counts]);
    }
}
