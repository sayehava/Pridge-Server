<?php

declare(strict_types=1);

namespace Pridge\Services;

use Pridge\Repositories\SettingsRepository;
use Pridge\Support\Clock;
use RuntimeException;
use ZipArchive;

final class UpdateService
{
    private const RELEASES_API = 'https://api.github.com/repos/sayehava/Pridge-Server/releases/latest';
    private const CHECK_INTERVAL_SECONDS = 3600;
    private const DOWNLOAD_TIMEOUT_SECONDS = 120;
    private const MAX_BACKUPS_KEPT = 5;

    /**
     * @return array{tag:string, version:string, notes:string, published_at:string, asset_url:string}|null
     */
    public static function latestKnown(): ?array
    {
        $version = SettingsRepository::get('update_latest_version');
        if ($version === null || $version === '') {
            return null;
        }

        return [
            'tag' => (string) SettingsRepository::get('update_latest_tag', ''),
            'version' => $version,
            'notes' => (string) SettingsRepository::get('update_latest_notes', ''),
            'published_at' => (string) SettingsRepository::get('update_latest_published_at', ''),
            'asset_url' => (string) SettingsRepository::get('update_latest_asset_url', ''),
        ];
    }

    public static function lastCheckError(): ?string
    {
        $error = SettingsRepository::get('update_last_check_error');

        return $error === '' ? null : $error;
    }

    public static function lastCheckedAt(): ?string
    {
        return SettingsRepository::get('update_last_check_at');
    }

    public static function isUpdateAvailable(): bool
    {
        $known = self::latestKnown();

        return $known !== null && version_compare($known['version'], PRIDGE_VERSION) > 0;
    }

    public static function checkForUpdate(bool $force = false): void
    {
        $lastChecked = SettingsRepository::get('update_last_check_at');
        if (!$force && $lastChecked !== null && (time() - strtotime($lastChecked)) < self::CHECK_INTERVAL_SECONDS) {
            return;
        }

        SettingsRepository::set('update_last_check_at', Clock::now());

        try {
            $release = self::fetchLatestRelease();
        } catch (RuntimeException $exception) {
            SettingsRepository::set('update_last_check_error', $exception->getMessage());
            return;
        }

        SettingsRepository::set('update_last_check_error', '');
        SettingsRepository::set('update_latest_tag', $release['tag']);
        SettingsRepository::set('update_latest_version', $release['version']);
        SettingsRepository::set('update_latest_notes', $release['notes']);
        SettingsRepository::set('update_latest_published_at', $release['published_at']);
        SettingsRepository::set('update_latest_asset_url', $release['asset_url']);
    }

    public static function backupsDir(): string
    {
        $dir = PRIDGE_STORAGE . '/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $dir;
    }

    public static function stagingDir(): string
    {
        return PRIDGE_STORAGE . '/staging';
    }

    /**
     * @return array<int, array{name:string, path:string, size:int, created_at:string}>
     */
    public static function listBackups(): array
    {
        $files = glob(self::backupsDir() . '/backup-*.zip') ?: [];
        rsort($files);

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => (int) filesize($file),
                'created_at' => gmdate('Y-m-d H:i:s', (int) filemtime($file)),
            ];
        }

        return $backups;
    }

    public static function createBackup(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to create a backup.');
        }

        @set_time_limit(300);

        $path = self::backupsDir() . '/backup-' . gmdate('Ymd-His') . '-v' . PRIDGE_VERSION . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup archive.');
        }

        self::addDirectoryToZip($zip, PRIDGE_ROOT, PRIDGE_ROOT, ['storage/backups', 'storage/staging']);
        $zip->close();

        SettingsRepository::set('update_last_backup_path', $path);
        SettingsRepository::set('update_last_backup_at', Clock::now());

        foreach (array_slice(self::listBackups(), self::MAX_BACKUPS_KEPT) as $old) {
            @unlink($old['path']);
        }

        return $path;
    }

    /**
     * @return array{version:string, staged_at:string, backup_path:string}|null
     */
    public static function stagedInfo(): ?array
    {
        $version = SettingsRepository::get('update_staged_version');
        if ($version === null || $version === '' || !is_dir(self::stagingDir())) {
            return null;
        }

        return [
            'version' => $version,
            'staged_at' => (string) SettingsRepository::get('update_staged_at', ''),
            'backup_path' => (string) SettingsRepository::get('update_staged_backup_path', ''),
        ];
    }

    /**
     * Takes a fresh backup, then downloads and extracts the latest known release into a
     * staging directory without touching any live file. Nothing here is applied until
     * applyStaged() runs as a separate, explicit step.
     */
    public static function prepareUpdate(): string
    {
        $known = self::latestKnown();
        if ($known === null || $known['asset_url'] === '') {
            throw new RuntimeException('No known update to prepare. Check for updates first.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to install updates.');
        }

        @set_time_limit(300);

        $backupPath = self::createBackup();
        self::clearStaging();

        $tempZip = PRIDGE_STORAGE . '/staging-download.zip';
        self::httpDownloadToFile($known['asset_url'], $tempZip);

        $zip = new ZipArchive();
        if ($zip->open($tempZip) !== true) {
            @unlink($tempZip);
            throw new RuntimeException('The downloaded update is not a valid archive.');
        }

        mkdir(self::stagingDir(), 0750, true);
        $extracted = $zip->extractTo(self::stagingDir());
        $zip->close();
        @unlink($tempZip);

        if (!$extracted) {
            self::clearStaging();
            throw new RuntimeException('Could not extract the downloaded update.');
        }

        $stagedRoot = self::findStagedRoot();
        if ($stagedRoot === null) {
            self::clearStaging();
            throw new RuntimeException('The downloaded update does not look like a valid Pridge Server release.');
        }

        $stagedVersion = self::readStagedVersion($stagedRoot);
        if ($stagedVersion === null) {
            self::clearStaging();
            throw new RuntimeException('Could not determine the version of the downloaded update.');
        }

        SettingsRepository::set('update_staged_version', $stagedVersion);
        SettingsRepository::set('update_staged_at', Clock::now());
        SettingsRepository::set('update_staged_backup_path', $backupPath);
        SettingsRepository::set('update_staged_root', $stagedRoot);

        return $stagedVersion;
    }

    /**
     * Overlays the staged release onto the live application, skipping storage/ entirely so
     * the live database, backups, and any staged files already in flight are never touched.
     * Any file that fails to copy is reported, since a partial copy can leave the app in a
     * mixed state that only a restore from backup can safely resolve.
     */
    public static function applyStaged(): void
    {
        $staged = self::stagedInfo();
        $stagedRoot = SettingsRepository::get('update_staged_root');
        if ($staged === null || $stagedRoot === null || !is_dir($stagedRoot)) {
            throw new RuntimeException('There is no staged update to apply. Prepare it again.');
        }

        @set_time_limit(300);

        $failures = self::copyDirectory($stagedRoot, PRIDGE_ROOT, ['storage']);

        self::clearStaging();
        SettingsRepository::set('update_staged_version', '');
        SettingsRepository::set('update_staged_root', '');
        self::resetOpcache();

        if ($failures !== []) {
            throw new RuntimeException(
                'The update finished, but ' . count($failures) . ' file(s) could not be written (a permission '
                . 'issue?), so the application may be in a mixed state. Restore the backup taken before this '
                . 'update immediately. First failed file: ' . $failures[0]
            );
        }
    }

    public static function discardStaged(): void
    {
        self::clearStaging();
        SettingsRepository::set('update_staged_version', '');
        SettingsRepository::set('update_staged_root', '');
    }

    /**
     * Restores application code and the database from a backup taken by this service,
     * completely overwriting the current installation. $backupPath must be a file inside
     * backupsDir(); anything else is rejected.
     */
    public static function rollback(string $backupPath): void
    {
        $backupsDir = realpath(self::backupsDir());
        $realBackupPath = realpath($backupPath);

        if ($backupsDir === false || $realBackupPath === false || strpos($realBackupPath, $backupsDir . DIRECTORY_SEPARATOR) !== 0) {
            throw new RuntimeException('Invalid backup file.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to restore a backup.');
        }

        @set_time_limit(300);

        $restoreDir = PRIDGE_STORAGE . '/restore-tmp';
        if (is_dir($restoreDir)) {
            self::removeDirectory($restoreDir);
        }
        mkdir($restoreDir, 0750, true);

        $zip = new ZipArchive();
        if ($zip->open($realBackupPath) !== true) {
            self::removeDirectory($restoreDir);
            throw new RuntimeException('Could not open the backup archive.');
        }
        $extracted = $zip->extractTo($restoreDir);
        $zip->close();

        if (!$extracted) {
            self::removeDirectory($restoreDir);
            throw new RuntimeException('Could not extract the backup archive.');
        }

        $failures = self::copyDirectory($restoreDir, PRIDGE_ROOT, []);
        self::removeDirectory($restoreDir);
        self::resetOpcache();

        if ($failures !== []) {
            throw new RuntimeException(
                'The restore finished, but ' . count($failures) . ' file(s) could not be written. '
                . 'First failed file: ' . $failures[0]
            );
        }
    }

    private static function fetchLatestRelease(): array
    {
        $body = self::httpGet(self::RELEASES_API);
        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['tag_name']) || !is_string($data['tag_name'])) {
            throw new RuntimeException('GitHub did not return a valid release.');
        }

        $tag = $data['tag_name'];
        $version = ltrim($tag, 'v');
        $notes = isset($data['body']) && is_string($data['body']) ? substr($data['body'], 0, 4000) : '';
        $publishedAt = isset($data['published_at']) && is_string($data['published_at']) ? $data['published_at'] : '';

        $assetUrl = '';
        if (isset($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (
                    is_array($asset)
                    && isset($asset['name'], $asset['browser_download_url'])
                    && is_string($asset['name'])
                    && is_string($asset['browser_download_url'])
                    && preg_match('/^pridge-server-.*\.zip$/', $asset['name']) === 1
                ) {
                    $assetUrl = $asset['browser_download_url'];
                    break;
                }
            }
        }

        if ($assetUrl === '') {
            throw new RuntimeException('The latest release does not have a downloadable package.');
        }

        return [
            'tag' => $tag,
            'version' => $version,
            'notes' => $notes,
            'published_at' => $publishedAt,
            'asset_url' => $assetUrl,
        ];
    }

    private static function httpGet(string $url): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP curl extension is required to check for updates.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Pridge-Server-Updater/' . PRIDGE_VERSION,
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
        ]);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new RuntimeException('Could not reach GitHub: ' . $error);
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('GitHub returned HTTP ' . $status . '.');
        }

        return (string) $result;
    }

    private static function httpDownloadToFile(string $url, string $destination): void
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP curl extension is required to download updates.');
        }

        $fp = fopen($destination, 'wb');
        if ($fp === false) {
            throw new RuntimeException('Could not create a temporary file for the download.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => self::DOWNLOAD_TIMEOUT_SECONDS,
            CURLOPT_USERAGENT => 'Pridge-Server-Updater/' . PRIDGE_VERSION,
        ]);
        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $status < 200 || $status >= 300) {
            @unlink($destination);
            throw new RuntimeException('Download failed: ' . ($error !== '' ? $error : ('HTTP ' . $status)));
        }
    }

    private static function clearStaging(): void
    {
        if (is_dir(self::stagingDir())) {
            self::removeDirectory(self::stagingDir());
        }
    }

    /**
     * The release archive is a single prefixed folder (pridge-server-<version>/); find it.
     */
    private static function findStagedRoot(): ?string
    {
        $dir = self::stagingDir();
        $entries = array_values(array_diff(scandir($dir) ?: [], ['.', '..']));

        if (count($entries) === 1 && is_dir($dir . '/' . $entries[0])) {
            return $dir . '/' . $entries[0];
        }

        return is_file($dir . '/app/bootstrap.php') ? $dir : null;
    }

    private static function readStagedVersion(string $stagedRoot): ?string
    {
        $contents = @file_get_contents($stagedRoot . '/app/bootstrap.php');
        if ($contents === false) {
            return null;
        }

        if (preg_match("/const\\s+PRIDGE_VERSION\\s*=\\s*'([^']+)'/", $contents, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private static function addDirectoryToZip(ZipArchive $zip, string $sourceRoot, string $currentDir, array $excludeRelative): void
    {
        $entries = scandir($currentDir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $currentDir . '/' . $entry;
            $relativePath = ltrim(substr($fullPath, strlen($sourceRoot)), '/');

            foreach ($excludeRelative as $excluded) {
                if ($relativePath === $excluded || strpos($relativePath, $excluded . '/') === 0) {
                    continue 2;
                }
            }

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($relativePath);
                self::addDirectoryToZip($zip, $sourceRoot, $fullPath, $excludeRelative);
            } else {
                $zip->addFile($fullPath, $relativePath);
            }
        }
    }

    /**
     * @return array<int, string> Destination paths that could not be written
     */
    private static function copyDirectory(string $source, string $destination, array $excludeRelative): array
    {
        $failures = [];
        $entries = scandir($source) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $entry;
            $destinationPath = $destination . '/' . $entry;

            if (in_array($entry, $excludeRelative, true)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                if (!is_dir($destinationPath) && !@mkdir($destinationPath, 0750, true) && !is_dir($destinationPath)) {
                    $failures[] = $destinationPath;
                    continue;
                }
                $failures = array_merge($failures, self::copyDirectory($sourcePath, $destinationPath, []));
            } elseif (!@copy($sourcePath, $destinationPath)) {
                $failures[] = $destinationPath;
            }
        }

        return $failures;
    }

    private static function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private static function removeDirectory(string $directory): void
    {
        $entries = scandir($directory) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
