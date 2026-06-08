<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class LeagueRepo
{
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM leagues WHERE id = ?', [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        return Db::one('SELECT * FROM leagues WHERE code = ?', [$code]);
    }

    public static function defaultLeague(): ?array
    {
        return Db::one('SELECT * FROM leagues WHERE is_default = 1');
    }

    public static function create(string $name, int $ownerId): array
    {
        do {
            $code = bin2hex(random_bytes(4)); // 8 hex chars
        } while (self::findByCode($code) !== null);

        Db::run('INSERT INTO leagues (name, code, owner_id) VALUES (?, ?, ?)', [$name, $code, $ownerId]);
        $id = Db::lastInsertId();
        self::join($id, $ownerId);
        return self::find($id) ?? [];
    }

    public static function join(int $leagueId, int $userId): void
    {
        Db::run('INSERT OR IGNORE INTO league_members (league_id, user_id) VALUES (?, ?)', [$leagueId, $userId]);
    }

    /** Entra numa liga pelo código. Retorna a liga ou null se o código não existe. */
    public static function joinByCode(int $userId, string $code): ?array
    {
        $l = self::findByCode($code);
        if ($l === null) {
            return null;
        }
        self::join((int) $l['id'], $userId);
        return $l;
    }

    public static function leave(int $leagueId, int $userId): void
    {
        Db::run('DELETE FROM league_members WHERE league_id = ? AND user_id = ?', [$leagueId, $userId]);
    }

    public static function isMember(int $leagueId, int $userId): bool
    {
        return Db::one('SELECT 1 FROM league_members WHERE league_id = ? AND user_id = ?', [$leagueId, $userId]) !== null;
    }

    /** Garante que o usuário está na liga padrão (Geral). */
    public static function ensureDefault(int $userId): void
    {
        $d = self::defaultLeague();
        if ($d !== null) {
            self::join((int) $d['id'], $userId);
        }
    }

    /** Ligas das quais o usuário participa (Geral primeiro). */
    public static function forUser(int $userId): array
    {
        return Db::all(
            'SELECT l.*, (SELECT COUNT(*) FROM league_members m2 WHERE m2.league_id = l.id) AS members
               FROM leagues l
               JOIN league_members m ON m.league_id = l.id AND m.user_id = ?
              ORDER BY l.is_default DESC, l.name COLLATE NOCASE',
            [$userId]
        );
    }

    /** Classificação de uma liga (pontos de palpites + bônus). */
    public static function leaderboard(int $leagueId): array
    {
        return Db::all(
            'SELECT u.id, u.name,
                    COALESCE((SELECT SUM(points) FROM predictions WHERE user_id = u.id), 0)
                  + COALESCE((SELECT SUM(points) FROM bonus_picks WHERE user_id = u.id), 0) AS total,
                    COALESCE((SELECT SUM(is_exact) FROM predictions WHERE user_id = u.id), 0) AS exacts,
                    COALESCE((SELECT SUM(is_three) FROM predictions WHERE user_id = u.id), 0) AS threes,
                    COALESCE((SELECT SUM(points) FROM bonus_picks WHERE user_id = u.id), 0) AS bonus,
                    (SELECT COUNT(*) FROM predictions WHERE user_id = u.id) AS palpites
               FROM users u
               JOIN league_members lm ON lm.user_id = u.id AND lm.league_id = ?
              ORDER BY total DESC, exacts DESC, threes DESC, u.name COLLATE NOCASE',
            [$leagueId]
        );
    }
}
