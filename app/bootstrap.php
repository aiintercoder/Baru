<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$GLOBALS['config'] = require BASE_PATH . '/config.php';
date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'Asia/Jakarta');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/access.php';
require __DIR__ . '/menu.php';
