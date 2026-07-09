<?php

declare(strict_types=1);

const PRINTBRIDGE_ROOT = __DIR__ . '/..';
const PRINTBRIDGE_STORAGE = PRINTBRIDGE_ROOT . '/storage';
const PRINTBRIDGE_DATABASE = PRINTBRIDGE_STORAGE . '/database/printbridge.sqlite';

date_default_timezone_set('UTC');

foreach ([PRINTBRIDGE_STORAGE, dirname(PRINTBRIDGE_DATABASE)] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0750, true);
    }
}
