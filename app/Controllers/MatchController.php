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

        $matchId = (int) $id;
        $m = MatchRepo::find($matchId);
        if ($m === null) {
            Flash::error('Jogo não encontrado.');
            redirect('/jogos');
        }
        if (!MatchRepo::isPredictable($m)) {
            Flash::error('Os palpites para este jogo estão fechados.');
            redirect('/jogos');
        }

        $ph = $_POST['pred_home'] ?? null;
        $pa = $_POST['pred_away'] ?? null;
        if (!is_numeric($ph) || !is_numeric($pa)) {
            Flash::error('Informe o placar dos dois times.');
            redirect('/jogos#m' . $matchId);
        }
        $ph = (int) $ph;
        $pa = (int) $pa;
        if ($ph < 0 || $pa < 0 || $ph > 30 || $pa > 30) {
            Flash::error('Placar inválido (use de 0 a 30).');
            redirect('/jogos#m' . $matchId);
        }

        PredictionRepo::upsert((int) Auth::id(), $matchId, $ph, $pa);
        Flash::success('Palpite salvo! ⚽');
        redirect('/jogos#m' . $matchId);
    }
}
