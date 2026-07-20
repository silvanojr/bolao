<?php

declare(strict_types=1);

namespace App;

use App\Repositories\MatchRepo;
use App\Repositories\SettingRepo;

final class Scoring
{
    /**
     * Pontua um palpite contra o placar real.
     * @return array{0:int,1:int,2:int} [pontos, is_exact, is_three]
     */
    public static function score(int $ph, int $pa, int $ah, int $aa, array $cfg): array
    {
        $po = $ph <=> $pa;   // 1 = vitória mandante, -1 = visitante, 0 = empate
        $ao = $ah <=> $aa;

        if ($ph === $ah && $pa === $aa) {
            return [$cfg['exact'], 1, 0];                    // placar exato
        }
        if ($po === $ao) {
            // mesmo resultado. O saldo (3 pts) só vale para jogos COM vencedor,
            // pois empate tem saldo sempre 0 (convenção comum de bolão).
            if ($po !== 0 && ($ph - $pa) === ($ah - $aa)) {
                return [$cfg['diff'], 0, 1];                 // vencedor + saldo
            }
            return [$cfg['winner'], 0, 0];                   // só o resultado
        }
        return [$cfg['miss'], 0, 0];                         // errou
    }

    /** Recalcula os pontos de todos os palpites de um jogo. Idempotente. */
    public static function recomputeMatch(int $matchId): void
    {
        $m = MatchRepo::find($matchId);
        if ($m === null) {
            return;
        }
        $cfg = SettingRepo::scoring();
        $pdo = Db::conn();
        $pdo->beginTransaction();
        try {
            if (MatchRepo::isFinished($m)) {
                $ah = (int) $m['home_goals'];
                $aa = (int) $m['away_goals'];
                foreach (Db::all('SELECT * FROM predictions WHERE match_id = ?', [$matchId]) as $p) {
                    [$pts, $ex, $th] = self::score((int) $p['pred_home'], (int) $p['pred_away'], $ah, $aa, $cfg);
                    Db::run(
                        "UPDATE predictions SET points = ?, is_exact = ?, is_three = ?, updated_at = datetime('now') WHERE id = ?",
                        [$pts, $ex, $th, $p['id']]
                    );
                }
            } else {
                // ainda não encerrado (ou resultado revertido): limpa pontuação
                Db::run(
                    "UPDATE predictions SET points = NULL, is_exact = 0, is_three = 0 WHERE match_id = ?",
                    [$matchId]
                );
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Recalcula TODOS os jogos + bônus (usado quando a config de pontos muda). */
    public static function recomputeAll(): void
    {
        foreach (Db::all('SELECT id FROM matches') as $row) {
            self::recomputeMatch((int) $row['id']);
        }
        self::recomputeBonus();
    }

    /**
     * Recalcula os palpites de bônus (campeão/vice/3º) a partir do resultado
     * da final e da disputa de 3º lugar. Idempotente.
     */
    public static function recomputeBonus(): void
    {
        $cfg = SettingRepo::all();
        $pts = [
            'champion'  => (int) ($cfg['bonus_champion']  ?? 20),
            'runner_up' => (int) ($cfg['bonus_runner_up'] ?? 10),
            'third'     => (int) ($cfg['bonus_third']     ?? 7),
        ];

        $resolved = ['champion' => null, 'runner_up' => null, 'third' => null];

        $final = MatchRepo::getByStage('Final');
        if ($final !== null && MatchRepo::isFinished($final)) {
            $side = MatchRepo::actualWinnerSide($final);
            if ($side !== null) {
                $resolved['champion']  = $side === 'HOME' ? $final['home_country'] : $final['away_country'];
                $resolved['runner_up'] = $side === 'HOME' ? $final['away_country'] : $final['home_country'];
            }
        }

        // A FIFA renomeou a etapa de "Play-off for third place" para "Bronze final"
        // durante a Copa; aceita os dois nomes.
        $third = MatchRepo::getByStage('Bronze final')
            ?? MatchRepo::getByStage('Play-off for third place');
        if ($third !== null && MatchRepo::isFinished($third)) {
            $side = MatchRepo::actualWinnerSide($third);
            if ($side !== null) {
                $resolved['third'] = $side === 'HOME' ? $third['home_country'] : $third['away_country'];
            }
        }

        $pdo = Db::conn();
        $pdo->beginTransaction();
        try {
            foreach (Db::all('SELECT * FROM bonus_picks') as $p) {
                $target = $resolved[$p['kind']] ?? null;
                if ($target === null) {
                    Db::run("UPDATE bonus_picks SET points = NULL, updated_at = datetime('now') WHERE id = ?", [$p['id']]);
                } else {
                    $val = ((string) $p['country'] === (string) $target) ? $pts[$p['kind']] : 0;
                    Db::run("UPDATE bonus_picks SET points = ?, updated_at = datetime('now') WHERE id = ?", [$val, $p['id']]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
