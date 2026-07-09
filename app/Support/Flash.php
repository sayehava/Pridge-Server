<?php

declare(strict_types=1);

namespace PrintBridge\Support;

final class Flash
{
    public static function set(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function pull(string $key): ?string
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);

        return is_string($message) ? $message : null;
    }
}
