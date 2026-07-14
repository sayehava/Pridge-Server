<?php

declare(strict_types=1);

namespace PrintBridge\Support;

final class Pagination
{
    /** @var array<int, int> */
    public const PAGE_SIZES = [10, 25, 50, 100, 500];

    private const DEFAULT_PAGE_SIZE = 25;

    /**
     * Null means "all rows, no limit".
     */
    public static function resolvePerPage(mixed $raw): ?int
    {
        if (!is_string($raw) || $raw === '') {
            return self::DEFAULT_PAGE_SIZE;
        }

        if ($raw === 'all') {
            return null;
        }

        $value = (int) $raw;

        return in_array($value, self::PAGE_SIZES, true) ? $value : self::DEFAULT_PAGE_SIZE;
    }

    public static function resolvePage(mixed $raw): int
    {
        $value = is_string($raw) ? (int) $raw : 0;

        return $value > 0 ? $value : 1;
    }

    public static function totalPages(int $total, ?int $perPage): int
    {
        if ($perPage === null || $perPage < 1) {
            return 1;
        }

        return max(1, (int) ceil($total / $perPage));
    }
}
