<?php

declare(strict_types=1);

namespace App;

/**
 * Roteador minúsculo baseado em regex. Padrões usam {nome} para capturar
 * segmentos (ex.: /jogos/{id}/palpite).
 */
final class Router
{
    /** @var array<int, array{0:string,1:string,2:mixed}> */
    private array $routes = [];

    /**
     * @param string $methods Ex.: 'GET' ou 'GET|POST'
     * @param callable|array{0:class-string,1:string} $handler
     */
    public function map(string $methods, string $pattern, callable|array $handler): void
    {
        foreach (explode('|', $methods) as $m) {
            $this->routes[] = [strtoupper(trim($m)), $this->compile($pattern), $handler];
        }
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $path === null ? '/' : rawurldecode($path);

        $base = rtrim((string) config('app_url', ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);
        $matchedOtherMethod = false;

        foreach ($this->routes as [$m, $re, $handler]) {
            if (preg_match($re, $path, $mt)) {
                if ($m !== $method) {
                    $matchedOtherMethod = true;
                    continue;
                }
                $params = array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->call($handler, array_values($params));
                return;
            }
        }

        if ($matchedOtherMethod) {
            View::render('error', ['title' => 'Método não permitido', 'code' => 405, 'message' => 'Método não permitido.'], 405);
            return;
        }

        View::render('error', ['title' => 'Não encontrado', 'code' => 404, 'message' => 'Página não encontrada.'], 404);
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $obj = new $class();
            $obj->{$method}(...$params);
            return;
        }
        $handler(...$params);
    }
}
