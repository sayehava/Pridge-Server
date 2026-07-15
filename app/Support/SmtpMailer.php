<?php

declare(strict_types=1);

namespace PrintBridge\Support;

final class SmtpMailer
{
    private const TIMEOUT_SECONDS = 10;

    public static function send(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName,
        string $to,
        string $subject,
        string $body
    ): bool {
        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $stream = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errNo,
            $errStr,
            self::TIMEOUT_SECONDS
        );

        if ($stream === false) {
            return false;
        }

        stream_set_timeout($stream, self::TIMEOUT_SECONDS);

        $ok = self::transact($stream, $encryption, $username, $password, $fromAddress, $fromName, $to, $subject, $body);

        fclose($stream);

        return $ok;
    }

    /** @param resource $stream */
    private static function transact(
        $stream,
        string $encryption,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName,
        string $to,
        string $subject,
        string $body
    ): bool {
        $localName = self::sanitizeHeaderValue((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        if (!self::expect($stream, [220])) {
            return false;
        }

        if (!self::command($stream, 'EHLO ' . $localName, [250])) {
            return false;
        }

        if ($encryption === 'tls') {
            if (!self::command($stream, 'STARTTLS', [220])) {
                return false;
            }

            if (!@stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return false;
            }

            if (!self::command($stream, 'EHLO ' . $localName, [250])) {
                return false;
            }
        }

        if ($username !== '') {
            if (!self::command($stream, 'AUTH LOGIN', [334])) {
                return false;
            }

            if (!self::command($stream, base64_encode($username), [334])) {
                return false;
            }

            if (!self::command($stream, base64_encode($password), [235])) {
                return false;
            }
        }

        $from = self::sanitizeHeaderValue($fromAddress);
        $recipient = self::sanitizeHeaderValue($to);

        if (!self::command($stream, 'MAIL FROM:<' . $from . '>', [250])) {
            return false;
        }

        if (!self::command($stream, 'RCPT TO:<' . $recipient . '>', [250, 251])) {
            return false;
        }

        if (!self::command($stream, 'DATA', [354])) {
            return false;
        }

        $headers = [
            'From: ' . ($fromName !== '' ? self::sanitizeHeaderValue($fromName) . ' <' . $from . '>' : $from),
            'To: <' . $recipient . '>',
            'Subject: ' . self::sanitizeHeaderValue($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Date: ' . date('r'),
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . self::dotStuff($body) . "\r\n.";

        if (!self::command($stream, $message, [250])) {
            return false;
        }

        self::command($stream, 'QUIT', [221]);

        return true;
    }

    /** @param resource $stream @param array<int, int> $expectedCodes */
    private static function command($stream, string $line, array $expectedCodes): bool
    {
        if (@fwrite($stream, $line . "\r\n") === false) {
            return false;
        }

        return self::expect($stream, $expectedCodes);
    }

    /** @param resource $stream @param array<int, int> $expectedCodes */
    private static function expect($stream, array $expectedCodes): bool
    {
        $response = self::readResponse($stream);

        return $response !== null && in_array((int) substr($response, 0, 3), $expectedCodes, true);
    }

    /** @param resource $stream */
    private static function readResponse($stream): ?string
    {
        $lastLine = '';

        while (!feof($stream)) {
            $line = fgets($stream, 515);

            if ($line === false) {
                return $lastLine === '' ? null : $lastLine;
            }

            $lastLine = $line;

            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        return $lastLine === '' ? null : $lastLine;
    }

    private static function dotStuff(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalized);

        foreach ($lines as $index => $line) {
            if (isset($line[0]) && $line[0] === '.') {
                $lines[$index] = '.' . $line;
            }
        }

        return implode("\r\n", $lines);
    }

    private static function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
