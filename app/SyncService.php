<?php

declare(strict_types=1);

namespace App;

use App\Repositories\MatchRepo;
use App\Repositories\SettingRepo;

/**
 * Busca os jogos na FIFA, faz upsert e recalcula a pontuação dos que mudaram.
 * Usado tanto pelo cron (bin/sync.php) quanto pelo botão do admin.
 */
final class SyncService
{
    /** @return array{status:string,message:string,total?:int,changed?:int} */
    public static function run(): array
    {
        try {
            $rows = (new FifaClient())->fetchMatches();
        } catch (\Throwable $e) {
            return self::record('error', 'erro ao buscar FIFA: ' . $e->getMessage());
        }

        if (count($rows) === 0) {
            return self::record('error', 'a FIFA não retornou nenhum jogo (temporada ainda vazia?)');
        }

        $changedFifaIds = [];
        $pdo = Db::conn();
        $pdo->beginTransaction();
        try {
            foreach ($rows as $r) {
                if ($r['fifa_id'] === '' || $r['utc_kickoff'] === '') {
                    continue;
                }
                $old = MatchRepo::getByFifaId($r['fifa_id']);
                MatchRepo::upsert($r);
                if (self::resultChanged($old, $r)) {
                    $changedFifaIds[] = $r['fifa_id'];
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return self::record('error', 'erro ao salvar: ' . $e->getMessage());
        }

        foreach ($changedFifaIds as $fid) {
            $m = MatchRepo::getByFifaId($fid);
            if ($m !== null) {
                Scoring::recomputeMatch((int) $m['id']);
            }
        }

        $total = count($rows);
        $changed = count($changedFifaIds);
        $res = self::record('ok', "{$total} jogos sincronizados, {$changed} com placar atualizado");
        $res['total'] = $total;
        $res['changed'] = $changed;
        return $res;
    }

    /** Houve mudança que exige recálculo de pontos? (placar ou status) */
    private static function resultChanged(?array $old, array $new): bool
    {
        if ($old === null) {
            return false; // jogo novo: ainda não há palpites para pontuar
        }
        return (int) ($old['status'] ?? -1) !== (int) $new['status']
            || (string) ($old['home_goals'] ?? '') !== (string) ($new['home_goals'] ?? '')
            || (string) ($old['away_goals'] ?? '') !== (string) ($new['away_goals'] ?? '');
    }

    private static function record(string $status, string $message): array
    {
        SettingRepo::set('last_sync_at', gmdate('Y-m-d\TH:i:s\Z'));
        SettingRepo::set('last_sync_status', $status);
        SettingRepo::set('last_sync_message', $message);
        return ['status' => $status, 'message' => $message];
    }
}
