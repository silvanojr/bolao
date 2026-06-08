<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;
use App\Repositories\LeagueRepo;

final class RankingController
{
    public function index(): void
    {
        Auth::requireAuth();
        $uid = (int) Auth::id();
        $leagues = LeagueRepo::forUser($uid);

        // liga selecionada via ?liga=code (precisa ser uma do usuário)
        $code = (string) ($_GET['liga'] ?? '');
        $selected = null;
        if ($code !== '') {
            foreach ($leagues as $l) {
                if ($l['code'] === $code) {
                    $selected = $l;
                    break;
                }
            }
        }
        if ($selected === null) {
            $selected = $leagues[0] ?? null;
        }

        $rows = $selected !== null ? LeagueRepo::leaderboard((int) $selected['id']) : [];

        View::render('ranking/index', [
            'title'    => 'Ranking',
            'rows'     => $rows,
            'meId'     => $uid,
            'leagues'  => $leagues,
            'selected' => $selected,
        ]);
    }
}
