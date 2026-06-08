<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class PredictionRepo
{
    /** @return array<int,array> indexado por match_id */
    public static function forUser(int $userId): array
    {
        $out = [];
        foreach (Db::all('SELECT * FROM predictions WHERE user_id = ?', [$userId]) as $r) {
            $out[(int) $r['match_id']] = $r;
        }
        return $out;
    }

    public static function get(int $userId, int $matchId): ?array
    {
        return Db::one('SELECT * FROM predictions WHERE user_id = ? AND match_id = ?', [$userId, $matchId]);
    }

    public static function upsert(int $userId, int $matchId, int $predHome, int $predAway): void
    {
        Db::run(
            "INSERT INTO predictions (user_id, match_id, pred_home, pred_away, created_at, updated_at)
             VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
             ON CONFLICT(user_id, match_id) DO UPDATE SET
                pred_home = excluded.pred_home,
                pred_away = excluded.pred_away,
                updated_at = datetime('now')",
            [$userId, $matchId, $predHome, $predAway]
        );
    }

    /** Palpites do usuário com os dados do jogo (para "minhas apostas"). */
    public static function withMatchForUser(int $userId): array
    {
        return Db::all(
            'SELECT p.pred_home, p.pred_away, p.points, p.is_exact, p.is_three,
                    m.id AS match_id, m.home_team, m.away_team, m.home_country, m.away_country,
                    m.home_ph, m.away_ph, m.home_goals, m.away_goals, m.home_pens, m.away_pens,
                    m.utc_kickoff, m.stage, m.grp, m.status, m.match_number
               FROM predictions p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ?
              ORDER BY m.utc_kickoff, m.match_number',
            [$userId]
        );
    }

    /** Totais agregados do usuário. */
    public static function statsForUser(int $userId): array
    {
        $r = Db::one(
            'SELECT COALESCE(SUM(points),0) AS total,
                    COALESCE(SUM(is_exact),0) AS exacts,
                    COALESCE(SUM(is_three),0) AS threes,
                    COUNT(*) AS palpites
               FROM predictions WHERE user_id = ?',
            [$userId]
        );
        return [
            'total'    => (int) ($r['total'] ?? 0),
            'exacts'   => (int) ($r['exacts'] ?? 0),
            'threes'   => (int) ($r['threes'] ?? 0),
            'palpites' => (int) ($r['palpites'] ?? 0),
        ];
    }
}
