<?php

declare(strict_types=1);

/**
 * Configuração do app. Tudo pode ser sobrescrito por variáveis de ambiente,
 * o que facilita o deploy sem editar arquivo (ex.: APP_ENV=production).
 *
 * Para segredos/locais, crie config/config.local.php retornando um array que
 * será mesclado por cima deste (não versionado).
 */

$config = [
    // Caminho do arquivo SQLite (fica fora do web root).
    'db_path' => dirname(__DIR__) . '/storage/bolao.sqlite',

    // Prefixo de URL caso o app rode em subpasta. '' = raiz do domínio.
    'app_url' => getenv('APP_URL') !== false ? getenv('APP_URL') : '',

    // 'production' esconde erros; qualquer outro valor mostra erros (dev).
    'env' => getenv('APP_ENV') !== false ? getenv('APP_ENV') : 'production',

    // Fuso para EXIBIÇÃO (no banco tudo é UTC).
    'tz_display' => 'America/Sao_Paulo',

    // Nome do cookie de sessão.
    'session_name' => 'bolao_sess',

    // Dados da fonte oficial FIFA (usados pelo sync). IdSeason 285023 = Copa 2026.
    'fifa' => [
        'base'        => 'https://api.fifa.com/api/v3',
        'competition' => '17',
        'season'      => '285023',
        'language'    => 'en',
    ],
];

$local = dirname(__DIR__) . '/config/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
