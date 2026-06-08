<?php

declare(strict_types=1);

namespace App;

final class Flash
{
    public static function add(string $type, string $msg): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
    }

    public static function success(string $m): void
    {
        self::add('success', $m);
    }

    public static function error(string $m): void
    {
        self::add('error', $m);
    }

    public static function info(string $m): void
    {
        self::add('info', $m);
    }

    /** Retorna e limpa as mensagens. */
    public static function pull(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($f) ? $f : [];
    }
}
