<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

define('APP_ROOT', dirname(__DIR__));

$GLOBALS['config'] = require APP_ROOT . '/config/config.php';
$GLOBALS['criteria'] = require __DIR__ . '/criteria.php';

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/scoring.php';
require __DIR__ . '/import.php';
require __DIR__ . '/mailer.php';
