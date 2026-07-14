<?php

declare(strict_types=1);

namespace PrintBridge\Services;

use PrintBridge\Repositories\QueueRepository;
use PrintBridge\Repositories\SettingsRepository;
use PrintBridge\Support\Clock;

final class ArchiveCleanupService
{
    private const MIN_INTERVAL_SECONDS = 300;
    private const BATCH_SIZE = 500;

    public static function runIfDue(): void
    {
        $mode = ArchiveRetention::currentMode();

        if ($mode === ArchiveRetention::MODE_NEVER) {
            return;
        }

        $days = ArchiveRetention::currentDays();

        if ($days < 1) {
            return;
        }

        $lastRun = SettingsRepository::get('archive_last_cleanup_at');

        if ($lastRun !== null && (time() - strtotime($lastRun)) < self::MIN_INTERVAL_SECONDS) {
            return;
        }

        // Claim the run before deleting so concurrent requests don't repeat the batch.
        SettingsRepository::set('archive_last_cleanup_at', Clock::now());

        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
        QueueRepository::deleteArchivedOlderThan($cutoff, self::BATCH_SIZE);
    }
}
