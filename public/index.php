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

\PrintBridge\Support\Http::notFound();
