<?php

declare(strict_types=1);

/**
 * Entrada do cron. Sincroniza os jogos/resultados da FIFA.
 *
 * Crontab sugerido (a cada 10 min, sem sobreposição):
 *   *\/10 * * * * /usr/bin/flock -n /tmp/bolao-sync.lock /usr/bin/php /caminho/bolao/bin/sync.php >> /caminho/bolao/storage/logs/sync.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$res = App\SyncService::run();

fwrite(STDOUT, sprintf("[%s UTC] %s: %s\n", gmdate('Y-m-d H:i:s'), strtoupper($res['status']), $res['message']));

exit($res['status'] === 'ok' ? 0 : 1);
