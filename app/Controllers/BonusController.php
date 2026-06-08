<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\View;
use App\Repositories\BonusRepo;
use App\Repositories\MatchRepo;
use App\Repositories\SettingRepo;

final class BonusController
{
    public function index(): void
    {
        Auth::requireAuth();
        View::render('bonus/index', [
            'title'    => 'Palpite de campeão',
            'teams'    => MatchRepo::teams(),
            'picks'    => BonusRepo::forUser((int) Auth::id()),
            'locked'   => MatchRepo::bonusLocked(),
            'deadline' => MatchRepo::firstKickoff(),
            'points'   => SettingRepo::all(),
        ]);
    }

    public function save(): void
    {
        Auth::requireAuth();
        Csrf::verify();

        if (MatchRepo::bonusLocked()) {
            Flash::error('Os palpites de bônus já estão fechados (a Copa começou).');
            redirect('/campeao');
        }

        $valid = [];
        foreach (MatchRepo::teams() as $t) {
            $valid[$t['country']] = $t['name'];
        }

        $any = false;
        foreach (array_keys(BonusRepo::KINDS) as $kind) {
            $cc = trim((string) ($_POST[$kind] ?? ''));
            if ($cc === '') {
                continue;
            }
            if (!isset($valid[$cc])) {
                Flash::error('Seleção de time inválida.');
                redirect('/campeao');
            }
            BonusRepo::upsert((int) Auth::id(), $kind, $cc, $valid[$cc]);
            $any = true;
        }

        Flash::success($any ? 'Palpites de campeão salvos! 🏆' : 'Nenhum palpite informado.');
        redirect('/campeao');
    }
}
