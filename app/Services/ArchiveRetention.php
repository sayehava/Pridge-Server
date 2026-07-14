<?php

declare(strict_types=1);

namespace PrintBridge\Services;

use PrintBridge\Repositories\SettingsRepository;

final class ArchiveRetention
{
    public const MODE_NEVER = 'never';
    public const MODE_PRESET = 'preset';
    public const MODE_CUSTOM = 'custom';

    private const MODES = [self::MODE_NEVER, self::MODE_PRESET, self::MODE_CUSTOM];

    private const MAX_CUSTOM_DAYS = 3650;

    /** @var array<int, string> */
    private const PRESETS = [
        1 => 'archive_retention.preset_1',
        7 => 'archive_retention.preset_7',
        14 => 'archive_retention.preset_14',
        30 => 'archive_retention.preset_30',
        90 => 'archive_retention.preset_90',
        180 => 'archive_retention.preset_180',
        365 => 'archive_retention.preset_365',
    ];

    /**
     * @return array<int, string>
     */
    public static function presets(): array
    {
        return self::PRESETS;
    }

    public static function currentMode(): string
    {
        $mode = SettingsRepository::get('archive_retention_mode', self::MODE_NEVER) ?? self::MODE_NEVER;

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_NEVER;
    }

    public static function currentDays(): int
    {
        return (int) (SettingsRepository::get('archive_retention_days', '0') ?? '0');
    }

    /**
     * @return array{mode: string, days: int}|null Null when the submitted values are invalid.
     */
    public static function resolveSubmission(string $mode, string $presetDays, string $customDays): ?array
    {
        if (!in_array($mode, self::MODES, true)) {
            return null;
        }

        if ($mode === self::MODE_NEVER) {
            return ['mode' => self::MODE_NEVER, 'days' => 0];
        }

        if ($mode === self::MODE_PRESET) {
            $days = (int) $presetDays;

            return array_key_exists($days, self::PRESETS) ? ['mode' => self::MODE_PRESET, 'days' => $days] : null;
        }

        $days = (int) $customDays;

        return ($days >= 1 && $days <= self::MAX_CUSTOM_DAYS) ? ['mode' => self::MODE_CUSTOM, 'days' => $days] : null;
    }

    public static function save(string $mode, int $days): void
    {
        SettingsRepository::set('archive_retention_mode', $mode);
        SettingsRepository::set('archive_retention_days', (string) $days);
    }
}
