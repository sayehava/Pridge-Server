<?php

declare(strict_types=1);

namespace Pridge\Support;

final class Version
{
    public static function major(string $version): string
    {
        $parts = explode('.', trim($version));

        return $parts[0] !== '' ? $parts[0] : $version;
    }

    /**
     * Returns an advisory message when the given peer's major version does not
     * match the server's, or null when they are compatible or the peer did not
     * report a version. A version mismatch is never an error: it only produces
     * a message for the caller to log or display, never a blocked request.
     */
    public static function compatibilityWarning(?string $peerVersion, string $peerLabel): ?string
    {
        if ($peerVersion === null || trim($peerVersion) === '') {
            return null;
        }

        $serverMajor = self::major(PRIDGE_VERSION);
        $peerMajor = self::major($peerVersion);

        if ($serverMajor === $peerMajor) {
            return null;
        }

        if ((int) $peerMajor < (int) $serverMajor) {
            return "This {$peerLabel} (v{$peerVersion}) is older than this server (v" . PRIDGE_VERSION . "). Please update the {$peerLabel}.";
        }

        return "This server (v" . PRIDGE_VERSION . ") is older than the connected {$peerLabel} (v{$peerVersion}). Please update the server.";
    }
}
