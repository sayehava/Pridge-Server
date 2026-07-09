<?php

declare(strict_types=1);

namespace PrintBridge\Support;

final class Clock
{
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function addSeconds(int $seconds): string
    {
        return gmdate('Y-m-d H:i:s', time() + $seconds);
    }
}
