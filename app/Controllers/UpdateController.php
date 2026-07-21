<?php

declare(strict_types=1);

namespace Pridge\Controllers;

use Pridge\Services\AdminAuth;
use Pridge\Services\UpdateService;
use Pridge\Support\Flash;
use Pridge\Support\Http;
use Pridge\Support\View;
use RuntimeException;

final class UpdateController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        UpdateService::checkForUpdate();

        self::render();
    }

    public static function check(): void
    {
        AdminAuth::requireLogin();
        UpdateService::checkForUpdate(true);
        Http::redirect('/updates');
    }

    public static function prepare(): void
    {
        AdminAuth::requireLogin();

        try {
            UpdateService::prepareUpdate();
            Flash::set('message', 'updates.prepared');
        } catch (RuntimeException $exception) {
            Flash::set('error', $exception->getMessage());
        }

        Http::redirect('/updates');
    }

    public static function discard(): void
    {
        AdminAuth::requireLogin();
        UpdateService::discardStaged();
        Flash::set('message', 'updates.discarded');
        Http::redirect('/updates');
    }

    public static function apply(): void
    {
        AdminAuth::requireLogin();

        if (!self::checkCsrf()) {
            Flash::set('error', 'error.invalid_csrf');
            Http::redirect('/updates');
            return;
        }

        try {
            UpdateService::applyStaged();
            Flash::set('message', 'updates.applied');
        } catch (RuntimeException $exception) {
            Flash::set('error', $exception->getMessage());
        }

        Http::redirect('/updates');
    }

    public static function rollback(): void
    {
        AdminAuth::requireLogin();

        if (!self::checkCsrf()) {
            Flash::set('error', 'error.invalid_csrf');
            Http::redirect('/updates');
            return;
        }

        try {
            UpdateService::rollback(UpdateService::backupsDir() . '/' . basename(Http::post('backup')));
            Flash::set('message', 'updates.rolled_back');
        } catch (RuntimeException $exception) {
            Flash::set('error', $exception->getMessage());
        }

        Http::redirect('/updates');
    }

    private static function render(): void
    {
        View::render('updates/index', [
            'message' => Flash::pull('message'),
            'error' => Flash::pull('error'),
            'currentVersion' => PRIDGE_VERSION,
            'latest' => UpdateService::latestKnown(),
            'updateAvailable' => UpdateService::isUpdateAvailable(),
            'lastCheckedAt' => UpdateService::lastCheckedAt(),
            'lastCheckError' => UpdateService::lastCheckError(),
            'staged' => UpdateService::stagedInfo(),
            'backups' => UpdateService::listBackups(),
            'csrfToken' => self::csrfToken(),
        ]);
    }

    private static function csrfToken(): string
    {
        if (empty($_SESSION['update_csrf_token'])) {
            $_SESSION['update_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['update_csrf_token'];
    }

    private static function checkCsrf(): bool
    {
        $token = $_SESSION['update_csrf_token'] ?? null;

        return is_string($token) && $token !== '' && hash_equals($token, Http::post('csrf_token'));
    }
}
