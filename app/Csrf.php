<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /** Verifica o token do POST; encerra com 419 se inválido. */
    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || $sent === '' || !hash_equals(self::token(), $sent)) {
            View::render('error', [
                'title'   => 'Sessão expirada',
                'code'    => 419,
                'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.',
            ], 419);
            exit;
        }
    }
}
