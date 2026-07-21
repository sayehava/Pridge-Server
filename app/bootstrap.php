<?php

declare(strict_types=1);

const PRIDGE_ROOT = __DIR__ . '/..';
const PRIDGE_STORAGE = PRIDGE_ROOT . '/storage';
const PRIDGE_DATABASE = PRIDGE_STORAGE . '/database/pridge.sqlite';
const PRIDGE_VERSION = '1.1.1';

date_default_timezone_set('UTC');

foreach ([PRIDGE_STORAGE, dirname(PRIDGE_DATABASE)] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0750, true);
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Pridge\\';

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

\Pridge\Database::migrate();
\Pridge\Services\ArchiveCleanupService::runIfDue();
