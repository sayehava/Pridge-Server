<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/health') {
    \PrintBridge\Support\Http::text('ok');
    exit;
}

if ($path === '/') {
    \PrintBridge\Controllers\AdminController::dashboard();
    exit;
}

if ($path === '/setup' && $method === 'GET') {
    \PrintBridge\Controllers\AuthController::setupForm();
    exit;
}

if ($path === '/setup' && $method === 'POST') {
    \PrintBridge\Controllers\AuthController::setup();
    exit;
}

if ($path === '/login' && $method === 'GET') {
    \PrintBridge\Controllers\AuthController::loginForm();
    exit;
}

if ($path === '/login' && $method === 'POST') {
    \PrintBridge\Controllers\AuthController::login();
    exit;
}

if ($path === '/logout' && $method === 'POST') {
    \PrintBridge\Controllers\AuthController::logout();
    exit;
}

if ($path === '/endpoints' && $method === 'GET') {
    \PrintBridge\Controllers\EndpointController::index();
    exit;
}

if ($path === '/endpoints' && $method === 'POST') {
    \PrintBridge\Controllers\EndpointController::create();
    exit;
}

if (preg_match('#^/endpoints/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
    \PrintBridge\Controllers\EndpointController::toggle((int) $matches[1]);
    exit;
}

if ($path === '/clients' && $method === 'GET') {
    \PrintBridge\Controllers\ClientController::index();
    exit;
}

if ($path === '/clients' && $method === 'POST') {
    \PrintBridge\Controllers\ClientController::create();
    exit;
}

if (preg_match('#^/clients/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
    \PrintBridge\Controllers\ClientController::toggle((int) $matches[1]);
    exit;
}

if ($path === '/api/plugin/jobs' && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::receiveJob();
    exit;
}

if ($path === '/api/client/auth' && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::authenticateClient();
    exit;
}

if ($path === '/api/client/jobs' && $method === 'GET') {
    \PrintBridge\Controllers\ApiController::listClientJobs();
    exit;
}

if ($path === '/api/client/jobs/reserve' && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::reserveClientJob();
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/printing$#', $path, $matches) && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::markPrinting((int) $matches[1]);
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/printed$#', $path, $matches) && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::markPrinted((int) $matches[1]);
    exit;
}

if (preg_match('#^/api/client/jobs/(\d+)/failed$#', $path, $matches) && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::markFailed((int) $matches[1]);
    exit;
}

if ($path === '/api/client/heartbeat' && $method === 'POST') {
    \PrintBridge\Controllers\ApiController::heartbeat();
    exit;
}

\PrintBridge\Support\Http::notFound();
