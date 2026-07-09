<?php

declare(strict_types=1);

const PRINTBRIDGE_ROOT = __DIR__ . '/..';
const PRINTBRIDGE_STORAGE = PRINTBRIDGE_ROOT . '/storage';
const PRINTBRIDGE_DATABASE = PRINTBRIDGE_STORAGE . '/database/printbridge.sqlite';

date_default_timezone_set('UTC');

foreach ([PRINTBRIDGE_STORAGE, dirname(PRINTBRIDGE_DATABASE)] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0750, true);
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'PrintBridge\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

\PrintBridge\Database::migrate();
