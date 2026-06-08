<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;

final class RankingController
{
    public function index(): void
    {
        Auth::requireAuth();
        $rows = Db::all(
            'SELECT u.id, u.name,
                    COALESCE(SUM(p.points),0)   AS total,
                    COALESCE(SUM(p.is_exact),0) AS exacts,
                    COALESCE(SUM(p.is_three),0) AS threes,
                    COUNT(p.id)                 AS palpites
               FROM users u
               LEFT JOIN predictions p ON p.user_id = u.id
              GROUP BY u.id
              ORDER BY total DESC, exacts DESC, threes DESC, u.name COLLATE NOCASE'
        );
        View::render('ranking/index', [
            'title' => 'Ranking',
            'rows'  => $rows,
            'meId'  => (int) Auth::id(),
        ]);
    }
}
