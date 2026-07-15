<?php

declare(strict_types=1);

namespace Pridge\Support;

final class PayloadPreview
{
    /**
     * @return array{type: string, mime: string}
     */
    public static function detect(string $bytes, string $declaredContentType): array
    {
        $declared = strtolower(trim($declaredContentType));

        if (str_starts_with($declared, 'image/')) {
            return ['type' => 'image', 'mime' => $declared];
        }

        if ($declared === 'application/pdf') {
            return ['type' => 'pdf', 'mime' => $declared];
        }

        if (str_starts_with($bytes, '%PDF-')) {
            return ['type' => 'pdf', 'mime' => 'application/pdf'];
        }

        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return ['type' => 'image', 'mime' => 'image/png'];
        }

        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return ['type' => 'image', 'mime' => 'image/jpeg'];
        }

        if (str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a')) {
            return ['type' => 'image', 'mime' => 'image/gif'];
        }

        if (self::looksLikeText($bytes)) {
            $mime = str_starts_with($declared, 'text/') ? $declared : 'text/plain';
            return ['type' => 'text', 'mime' => $mime . '; charset=utf-8'];
        }

        return ['type' => 'binary', 'mime' => $declared !== '' ? $declared : 'application/octet-stream'];
    }

    private static function looksLikeText(string $bytes): bool
    {
        if ($bytes === '') {
            return true;
        }

        $sample = substr($bytes, 0, 4000);

        if (!mb_check_encoding($sample, 'UTF-8')) {
            return false;
        }

        $length = strlen($sample);
        $controlBytes = 0;

        for ($i = 0; $i < $length; $i++) {
            $byte = ord($sample[$i]);

            if ($byte < 0x20 && !in_array($byte, [0x09, 0x0A, 0x0D], true)) {
                $controlBytes++;
            }
        }

        return ($controlBytes / $length) < 0.02;
    }
}
