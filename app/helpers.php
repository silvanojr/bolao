<?php

declare(strict_types=1);

/**
 * Funções globais de conveniência (carregadas via "files" do Composer).
 */

/**
 * Guarda/recupera o array de configuração do app.
 */
function app_config(?array $set = null): array
{
    static $c = [];
    if ($set !== null) {
        $c = $set;
    }
    return $c;
}

function config(string $key, mixed $default = null): mixed
{
    return app_config()[$key] ?? $default;
}

/**
 * Escapa para HTML. Use SEMPRE ao imprimir dados nas views.
 */
function e(mixed $s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Monta uma URL absoluta respeitando o prefixo app_url (subpasta).
 */
function base_url(string $path = ''): string
{
    $base = rtrim((string) config('app_url', ''), '/');
    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base;
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * URL absoluta (scheme + host + caminho). Usada em links de convite.
 */
function abs_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . base_url($path);
}

/**
 * Redireciona e encerra a execução.
 */
function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}
