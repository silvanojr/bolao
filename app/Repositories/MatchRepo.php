<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;
use App\Time;

final class MatchRepo
{
    /** Códigos de MatchStatus da FIFA. */
    public const ST_SCHEDULED = 1;
    public const ST_LIVE      = 3;

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM matches WHERE id = ?', [$id]);
    }

    public static function getByFifaId(string $fifaId): ?array
    {
        return Db::one('SELECT * FROM matches WHERE fifa_id = ?', [$fifaId]);
    }

    /** Todos os jogos em ordem cronológica. */
    public static function allOrdered(): array
    {
        return Db::all('SELECT * FROM matches ORDER BY utc_kickoff, match_number');
    }

    public static function count(): int
    {
        $r = Db::one('SELECT COUNT(*) AS c FROM matches');
        return (int) ($r['c'] ?? 0);
    }

    /** INSERT ou UPDATE por fifa_id. $r vem do FifaClient::map(). */
    public static function upsert(array $r): void
    {
        $sql = 'INSERT INTO matches
            (fifa_id, match_number, stage, grp, utc_kickoff, status,
             home_country, home_team, home_ph, away_country, away_team, away_ph,
             home_goals, away_goals, home_pens, away_pens, winner, stadium, updated_at)
            VALUES
            (:fifa_id, :match_number, :stage, :grp, :utc_kickoff, :status,
             :home_country, :home_team, :home_ph, :away_country, :away_team, :away_ph,
             :home_goals, :away_goals, :home_pens, :away_pens, :winner, :stadium, datetime(\'now\'))
            ON CONFLICT(fifa_id) DO UPDATE SET
             match_number = excluded.match_number, stage = excluded.stage, grp = excluded.grp,
             utc_kickoff = excluded.utc_kickoff, status = excluded.status,
             home_country = excluded.home_country, home_team = excluded.home_team, home_ph = excluded.home_ph,
             away_country = excluded.away_country, away_team = excluded.away_team, away_ph = excluded.away_ph,
             home_goals = excluded.home_goals, away_goals = excluded.away_goals,
             home_pens = excluded.home_pens, away_pens = excluded.away_pens,
             winner = excluded.winner, stadium = excluded.stadium, updated_at = datetime(\'now\')';
        Db::run($sql, $r);
    }

    /** Os dois times estão definidos? (gate do mata-mata) */
    public static function hasTeams(array $m): bool
    {
        return !empty($m['home_team']) && !empty($m['away_team']);
    }

    /** Pode receber palpite agora? (servidor é a fonte da verdade) */
    public static function isPredictable(array $m): bool
    {
        if (!self::hasTeams($m)) {
            return false;                       // mata-mata ainda indefinido
        }
        if ((int) $m['status'] !== self::ST_SCHEDULED) {
            return false;                       // ao vivo / encerrado / adiado
        }
        if (Time::isPast((string) $m['utc_kickoff'])) {
            return false;                       // passou do horário
        }
        return true;
    }

    public static function isLive(array $m): bool
    {
        return (int) $m['status'] === self::ST_LIVE;
    }

    /** Jogo encerrado com placar disponível (pronto para pontuar). */
    public static function isFinished(array $m): bool
    {
        if ($m['home_goals'] === null || $m['away_goals'] === null) {
            return false;
        }
        $st = (int) $m['status'];
        return $st !== self::ST_SCHEDULED && $st !== self::ST_LIVE;
    }
}
