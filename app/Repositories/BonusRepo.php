<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class BonusRepo
{
    /** Tipos de palpite de bônus e seus rótulos (em ordem de exibição). */
    public const KINDS = [
        'champion'  => 'Campeão',
        'runner_up' => 'Vice-campeão',
        'third'     => '3º lugar',
    ];

    /** @return array<string,array> indexado por kind */
    public static function forUser(int $userId): array
    {
        $out = [];
        foreach (Db::all('SELECT * FROM bonus_picks WHERE user_id = ?', [$userId]) as $r) {
            $out[$r['kind']] = $r;
        }
        return $out;
    }

    public static function upsert(int $userId, string $kind, string $country, string $teamName): void
    {
        Db::run(
            "INSERT INTO bonus_picks (user_id, kind, country, team_name, created_at, updated_at)
             VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
             ON CONFLICT(user_id, kind) DO UPDATE SET
                country = excluded.country, team_name = excluded.team_name, updated_at = datetime('now')",
            [$userId, $kind, $country, $teamName]
        );
    }

    public static function totalForUser(int $userId): int
    {
        $r = Db::one('SELECT COALESCE(SUM(points),0) AS t FROM bonus_picks WHERE user_id = ?', [$userId]);
        return (int) ($r['t'] ?? 0);
    }
}
