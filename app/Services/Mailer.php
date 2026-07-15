<?php

declare(strict_types=1);

namespace PrintBridge\Services;

use PrintBridge\Repositories\SettingsRepository;
use PrintBridge\Support\SmtpMailer;

final class Mailer
{
    public const DRIVER_PHP_MAIL = 'php_mail';
    public const DRIVER_SMTP = 'smtp';

    private const DRIVERS = [self::DRIVER_PHP_MAIL, self::DRIVER_SMTP];
    private const ENCRYPTIONS = ['none', 'ssl', 'tls'];

    public static function currentDriver(): string
    {
        $driver = SettingsRepository::get('mail_driver', self::DRIVER_PHP_MAIL) ?? self::DRIVER_PHP_MAIL;

        return in_array($driver, self::DRIVERS, true) ? $driver : self::DRIVER_PHP_MAIL;
    }

    /**
     * @return array{host: string, port: int, encryption: string, username: string, password: string, from_address: string, from_name: string}
     */
    public static function smtpSettings(): array
    {
        return [
            'host' => SettingsRepository::get('smtp_host', '') ?? '',
            'port' => (int) (SettingsRepository::get('smtp_port', '587') ?? '587'),
            'encryption' => SettingsRepository::get('smtp_encryption', 'tls') ?? 'tls',
            'username' => SettingsRepository::get('smtp_username', '') ?? '',
            'password' => SettingsRepository::get('smtp_password', '') ?? '',
            'from_address' => SettingsRepository::get('smtp_from_address', '') ?? '',
            'from_name' => SettingsRepository::get('smtp_from_name', '') ?? '',
        ];
    }

    /**
     * @return array<string, mixed>|null Null when the submitted values are invalid.
     */
    public static function resolveSubmission(
        string $driver,
        string $host,
        string $port,
        string $encryption,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName
    ): ?array {
        if (!in_array($driver, self::DRIVERS, true)) {
            return null;
        }

        if ($driver === self::DRIVER_PHP_MAIL) {
            return ['driver' => self::DRIVER_PHP_MAIL];
        }

        if (!in_array($encryption, self::ENCRYPTIONS, true)) {
            return null;
        }

        $portNumber = (int) $port;

        if ($host === '' || $fromAddress === '' || $portNumber < 1 || $portNumber > 65535) {
            return null;
        }

        return [
            'driver' => self::DRIVER_SMTP,
            'host' => $host,
            'port' => $portNumber,
            'encryption' => $encryption,
            'username' => $username,
            'password' => $password,
            'from_address' => $fromAddress,
            'from_name' => $fromName,
        ];
    }

    /**
     * @param array<string, mixed> $submission
     */
    public static function save(array $submission): void
    {
        SettingsRepository::set('mail_driver', (string) $submission['driver']);

        if ($submission['driver'] !== self::DRIVER_SMTP) {
            return;
        }

        SettingsRepository::set('smtp_host', (string) $submission['host']);
        SettingsRepository::set('smtp_port', (string) $submission['port']);
        SettingsRepository::set('smtp_encryption', (string) $submission['encryption']);
        SettingsRepository::set('smtp_username', (string) $submission['username']);
        SettingsRepository::set('smtp_from_address', (string) $submission['from_address']);
        SettingsRepository::set('smtp_from_name', (string) $submission['from_name']);

        // An empty password submission keeps the previously saved password.
        if ($submission['password'] !== '') {
            SettingsRepository::set('smtp_password', (string) $submission['password']);
        }
    }

    public static function send(string $to, string $subject, string $body): bool
    {
        if (self::currentDriver() !== self::DRIVER_SMTP) {
            return @mail($to, $subject, $body);
        }

        $settings = self::smtpSettings();

        if ($settings['host'] === '') {
            return false;
        }

        return SmtpMailer::send(
            $settings['host'],
            $settings['port'],
            $settings['encryption'],
            $settings['username'],
            $settings['password'],
            $settings['from_address'] !== '' ? $settings['from_address'] : $settings['username'],
            $settings['from_name'],
            $to,
            $subject,
            $body
        );
    }
}
