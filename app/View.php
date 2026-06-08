<?php

declare(strict_types=1);

namespace App;

final class View
{
    /**
     * Renderiza um template de app/views dentro do layout.
     * As chaves de $data viram variáveis no template e no layout.
     */
    public static function render(string $template, array $data = [], ?int $status = null): void
    {
        if ($status !== null && !headers_sent()) {
            http_response_code($status);
        }

        $file = dirname(__DIR__) . '/app/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Template não encontrado: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        require dirname(__DIR__) . '/app/views/layout.php';
    }
}
