<?php

declare(strict_types=1);

namespace Pridge\Support;

final class Security
{
    public static function randomToken(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function tokenMatches(string $providedToken, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hashToken($providedToken));
    }

    public static function requestIpHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return hash('sha256', is_string($ip) ? $ip : 'unknown');
    }
}
