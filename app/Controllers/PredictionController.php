<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;
use App\Repositories\PredictionRepo;

final class PredictionController
{
    public function mine(): void
    {
        Auth::requireAuth();
        $uid = (int) Auth::id();
        View::render('predictions/mine', [
            'title' => 'Minhas apostas',
            'stats' => PredictionRepo::statsForUser($uid),
            'rows'  => PredictionRepo::withMatchForUser($uid),
        ]);
    }
}
