<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/health') {
    \Pridge\Support\Http::text('ok');
    exit;
}

if ($path === '/') {
    \Pridge\Controllers\AdminController::dashboard();
    exit;
}

if ($path === '/setup' && $method === 'GET') {
    \Pridge\Controllers\AuthController::setupForm();
    exit;
}

if ($path === '/setup' && $method === 'POST') {
    \Pridge\Controllers\AuthController::setup();
    exit;
}

if ($path === '/login' && $method === 'GET') {
    \Pridge\Controllers\AuthController::loginForm();
    exit;
}

if ($path === '/login' && $method === 'POST') {
    \Pridge\Controllers\AuthController::login();
    exit;
}

if ($path === '/forgot-password' && $method === 'GET') {
    \Pridge\Controllers\AuthController::forgotPasswordForm();
    exit;
}

if ($path === '/forgot-password' && $method === 'POST') {
    \Pridge\Controllers\AuthController::forgotPassword();
    exit;
}

if ($path === '/reset-password' && $method === 'GET') {
    \Pridge\Controllers\AuthController::resetPasswordForm();
    exit;
}

if ($path === '/reset-password' && $method === 'POST') {
    \Pridge\Controllers\AuthController::resetPassword();
    exit;
}

if ($path === '/logout' && $method === 'POST') {
    \Pridge\Controllers\AuthController::logout();
    exit;
}

if ($path === '/endpoints' && $method === 'GET') {
    \Pridge\Controllers\EndpointController::index();
    exit;
}

if ($path === '/endpoints' && $method === 'POST') {
    \Pridge\Controllers\EndpointController::create();
    exit;
}

if (preg_match('#^/endpoints/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\EndpointController::toggle((int) $matches[1]);
    exit;
}

if (preg_match('#^/endpoints/(\d+)/clients$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\EndpointController::assignClients((int) $matches[1]);
    exit;
}

if (preg_match('#^/endpoints/(\d+)/rename$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\EndpointController::rename((int) $matches[1]);
    exit;
}

if (preg_match('#^/endpoints/(\d+)/regenerate$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\EndpointController::regenerateToken((int) $matches[1]);
    exit;
}

if (preg_match('#^/endpoints/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\EndpointController::delete((int) $matches[1]);
    exit;
}

if ($path === '/clients' && $method === 'GET') {
    \Pridge\Controllers\ClientController::index();
    exit;
}

if ($path === '/clients' && $method === 'POST') {
    \Pridge\Controllers\ClientController::create();
    exit;
}

if (preg_match('#^/clients/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ClientController::toggle((int) $matches[1]);
    exit;
}

if (preg_match('#^/clients/(\d+)/rename$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ClientController::rename((int) $matches[1]);
    exit;
}

if (preg_match('#^/clients/(\d+)/regenerate$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ClientController::regenerateToken((int) $matches[1]);
    exit;
}

if (preg_match('#^/clients/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ClientController::delete((int) $matches[1]);
    exit;
}

if ($path === '/api/plugin/jobs' && $method === 'POST') {
    \Pridge\Controllers\ApiController::receiveJob();
    exit;
}

if ($path === '/api/plugin/clients' && $method === 'POST') {
    \Pridge\Controllers\ApiController::listEndpointClients();
    exit;
}

if ($path === '/queue' && $method === 'GET') {
    \Pridge\Controllers\QueueController::index();
    exit;
}

if ($path === '/archive' && $method === 'GET') {
    \Pridge\Controllers\QueueController::archive();
    exit;
}

if (preg_match('#^/queue/(\d+)$#', $path, $matches) && $method === 'GET') {
    \Pridge\Controllers\QueueController::show((int) $matches[1]);
    exit;
}

if (preg_match('#^/queue/(\d+)/payload$#', $path, $matches) && $method === 'GET') {
    \Pridge\Controllers\QueueController::payload((int) $matches[1]);
    exit;
}

if (preg_match('#^/queue/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\QueueController::delete((int) $matches[1]);
    exit;
}

if ($path === '/queue/delete-selected' && $method === 'POST') {
    \Pridge\Controllers\QueueController::deleteSelectedWaiting();
    exit;
}

if ($path === '/queue/delete-all' && $method === 'POST') {
    \Pridge\Controllers\QueueController::deleteAllWaiting();
    exit;
}

if ($path === '/archive/delete-selected' && $method === 'POST') {
    \Pridge\Controllers\QueueController::deleteSelectedArchived();
    exit;
}

if ($path === '/archive/delete-all' && $method === 'POST') {
    \Pridge\Controllers\QueueController::deleteAllArchived();
    exit;
}

if ($path === '/settings' && $method === 'GET') {
    \Pridge\Controllers\SettingsController::index();
    exit;
}

if ($path === '/settings/password' && $method === 'POST') {
    \Pridge\Controllers\SettingsController::changePassword();
    exit;
}

if ($path === '/settings/archive-retention' && $method === 'POST') {
    \Pridge\Controllers\SettingsController::updateArchiveRetention();
    exit;
}

if ($path === '/settings/mail' && $method === 'POST') {
    \Pridge\Controllers\SettingsController::updateMail();
    exit;
}

if ($path === '/api/client/auth' && $method === 'POST') {
    \Pridge\Controllers\ApiController::authenticateClient();
    exit;
}

if ($path === '/api/client/jobs' && $method === 'GET') {
    \Pridge\Controllers\ApiController::listClientJobs();
    exit;
}

if ($path === '/api/client/endpoints' && $method === 'GET') {
    \Pridge\Controllers\ApiController::listClientEndpoints();
    exit;
}

if ($path === '/api/client/endpoints' && $method === 'PUT') {
    \Pridge\Controllers\ApiController::syncClientEndpoints();
    exit;
}

if ($path === '/api/client/jobs/reserve' && $method === 'POST') {
    \Pridge\Controllers\ApiController::reserveClientJob();
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/printing$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ApiController::markPrinting((int) $matches[1]);
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/printed$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ApiController::markPrinted((int) $matches[1]);
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/failed$#', $path, $matches) && $method === 'POST') {
    \Pridge\Controllers\ApiController::markFailed((int) $matches[1]);
    exit;
}

if ($path === '/api/client/heartbeat' && $method === 'POST') {
    \Pridge\Controllers\ApiController::heartbeat();
    exit;
}

\Pridge\Support\Http::notFound();
