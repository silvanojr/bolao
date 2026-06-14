<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\View;
use App\Repositories\MatchRepo;
use App\Repositories\PredictionRepo;

final class MatchController
{
    public function index(): void
    {
        Auth::requireAuth();
        $matches = MatchRepo::allOrdered();
        $preds   = PredictionRepo::forUser((int) Auth::id());
        View::render('matches/list', [
            'title'   => 'Jogos',
            'matches' => $matches,
            'preds'   => $preds,
        ]);
    }

    public function submit(string $id): void
    {
        Auth::requireAuth();
        Csrf::verify();

        $ajax    = self::wantsJson();
        $matchId = (int) $id;
        $m = MatchRepo::find($matchId);
        if ($m === null) {
            self::reject($ajax, 'Jogo não encontrado.', 404, '/jogos');
        }
        if (!MatchRepo::isPredictable($m)) {
            self::reject($ajax, 'Os palpites para este jogo estão fechados.', 403, '/jogos');
        }

        $ph = $_POST['pred_home'] ?? null;
        $pa = $_POST['pred_away'] ?? null;
        if (!is_numeric($ph) || !is_numeric($pa)) {
            self::reject($ajax, 'Informe o placar dos dois times.', 422, '/jogos#m' . $matchId);
        }
        $ph = (int) $ph;
        $pa = (int) $pa;
        if ($ph < 0 || $pa < 0 || $ph > 30 || $pa > 30) {
            self::reject($ajax, 'Placar inválido (use de 0 a 30).', 422, '/jogos#m' . $matchId);
        }

        PredictionRepo::upsert((int) Auth::id(), $matchId, $ph, $pa);

        if ($ajax) {
            self::json(['ok' => true, 'pred_home' => $ph, 'pred_away' => $pa]);
        }
        Flash::success('Palpite salvo! ⚽');
        redirect('/jogos#m' . $matchId);
    }

    /** A requisição veio do autosave (fetch) e espera JSON? */
    private static function wantsJson(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /** Responde JSON (autosave) ou redireciona com flash (formulário normal). */
    private static function reject(bool $ajax, string $msg, int $status, string $path): never
    {
        if ($ajax) {
            self::json(['ok' => false, 'error' => $msg], $status);
        }
        Flash::error($msg);
        redirect($path);
    }

    private static function json(array $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data);
        exit;
    }
}
