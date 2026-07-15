<?php

declare(strict_types=1);

namespace Pridge\Support;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require PRIDGE_ROOT . '/views/layout/header.php';
        require PRIDGE_ROOT . '/views/' . $template . '.php';
        require PRIDGE_ROOT . '/views/layout/footer.php';
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
