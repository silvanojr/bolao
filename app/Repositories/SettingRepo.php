<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class SettingRepo
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $r = Db::one('SELECT value FROM settings WHERE key = ?', [$key]);
        return $r === null ? $default : (string) $r['value'];
    }

    public static function set(string $key, string $value): void
    {
        Db::run(
            'INSERT INTO settings (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value',
            [$key, $value]
        );
    }

    /** @return array<string,string> */
    public static function all(): array
    {
        $out = [];
        foreach (Db::all('SELECT key, value FROM settings') as $r) {
            $out[$r['key']] = (string) $r['value'];
        }
        return $out;
    }

    /** Configuração de pontuação como inteiros. */
    public static function scoring(): array
    {
        $a = self::all();
        return [
            'exact'  => (int) ($a['points_exact']  ?? 5),
            'diff'   => (int) ($a['points_diff']   ?? 3),
            'winner' => (int) ($a['points_winner'] ?? 1),
            'miss'   => (int) ($a['points_miss']   ?? 0),
        ];
    }
}
