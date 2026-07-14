<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\AdminRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Services\ArchiveRetention;
use PrintBridge\Support\Flash;
use PrintBridge\Support\Http;
use PrintBridge\Support\View;

final class SettingsController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        View::render('settings/index', [
            'message' => Flash::pull('message'),
            'error' => Flash::pull('error'),
            'databasePath' => PRINTBRIDGE_DATABASE,
            'archiveMode' => ArchiveRetention::currentMode(),
            'archiveDays' => ArchiveRetention::currentDays(),
            'archivePresets' => ArchiveRetention::presets(),
        ]);
    }

    public static function updateArchiveRetention(): void
    {
        AdminAuth::requireLogin();

        $submission = ArchiveRetention::resolveSubmission(
            Http::post('mode'),
            Http::post('preset_days'),
            Http::post('custom_days')
        );

        if ($submission === null) {
            Flash::set('error', 'error.invalid_archive_retention');
            Http::redirect('/settings');
            return;
        }

        ArchiveRetention::save($submission['mode'], $submission['days']);
        Flash::set('message', 'settings.archive_retention_saved');
        Http::redirect('/settings');
    }

    public static function changePassword(): void
    {
        AdminAuth::requireLogin();
        $userId = AdminAuth::userId();
        $admin = $userId === null ? null : AdminRepository::findById($userId);

        if ($admin === null || !password_verify(Http::post('current_password'), $admin['password_hash'])) {
            Flash::set('error', 'error.invalid_current_password');
            Http::redirect('/settings');
            return;
        }

        $newPassword = Http::post('new_password');

        if (strlen($newPassword) < 12) {
            Flash::set('error', 'error.password_length');
            Http::redirect('/settings');
            return;
        }

        AdminRepository::updatePassword((int) $admin['id'], $newPassword);
        Flash::set('message', 'settings.password_changed');
        Http::redirect('/settings');
    }
}
