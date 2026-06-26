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
        if (self::isLive($m) || self::isFinished($m)) {
            return false;                       // ao vivo / encerrado
        }
        if (Time::isPast((string) $m['utc_kickoff'])) {
            return false;                       // passou do horário (apito inicial)
        }
        return true;                            // status pré-jogo (1, 12, ...) segue aberto
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

    /** Lista de seleções (fase de grupos) para os palpites de bônus. */
    public static function teams(): array
    {
        return Db::all(
            "SELECT home_country AS country, home_team AS name FROM matches
              WHERE grp IS NOT NULL AND home_team IS NOT NULL AND home_country IS NOT NULL
              UNION
             SELECT away_country AS country, away_team AS name FROM matches
              WHERE grp IS NOT NULL AND away_team IS NOT NULL AND away_country IS NOT NULL
              ORDER BY name COLLATE NOCASE"
        );
    }

    /** Primeiro kickoff do torneio (deadline dos palpites de bônus). */
    public static function firstKickoff(): ?string
    {
        $r = Db::one('SELECT MIN(utc_kickoff) AS k FROM matches');
        return ($r && !empty($r['k'])) ? (string) $r['k'] : null;
    }

    /** Os palpites de bônus já travaram? (Copa começou) */
    public static function bonusLocked(): bool
    {
        $k = self::firstKickoff();
        return $k !== null && Time::isPast($k);
    }

    /** Primeiro jogo de uma etapa (ex.: 'Final', 'Play-off for third place'). */
    public static function getByStage(string $stage): ?array
    {
        return Db::one('SELECT * FROM matches WHERE stage = ? ORDER BY utc_kickoff LIMIT 1', [$stage]);
    }

    /** Lado vencedor de fato, incluindo pênaltis. Retorna 'HOME'|'AWAY'|null. */
    public static function actualWinnerSide(array $m): ?string
    {
        if ($m['home_goals'] === null || $m['away_goals'] === null) {
            return null;
        }
        $hp = $m['home_pens'];
        $ap = $m['away_pens'];
        if ($hp !== null && $ap !== null && (int) $hp !== (int) $ap) {
            return (int) $hp > (int) $ap ? 'HOME' : 'AWAY';
        }
        if ((int) $m['home_goals'] > (int) $m['away_goals']) {
            return 'HOME';
        }
        if ((int) $m['away_goals'] > (int) $m['home_goals']) {
            return 'AWAY';
        }
        return null;
    }
}
