<?php

declare(strict_types=1);

/**
 * Bootstrap compartilhado por web (public/index.php) e CLI (bin/sync.php, db/migrate.php).
 * NÃO inicia sessão (isso é responsabilidade só do front controller web).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';
app_config($config);

// No banco e na lógica tudo é UTC; conversão para SP só na renderização.
date_default_timezone_set('UTC');

$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

if (($config['env'] ?? 'production') === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/php-error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

App\Db::init((string) $config['db_path']);
