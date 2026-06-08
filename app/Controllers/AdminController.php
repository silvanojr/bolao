<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\Scoring;
use App\SyncService;
use App\Time;
use App\View;
use App\Repositories\InviteRepo;
use App\Repositories\MatchRepo;
use App\Repositories\SettingRepo;
use App\Repositories\UserRepo;

final class AdminController
{
    public function invites(): void
    {
        Auth::requireAdmin();
        View::render('admin/invites', [
            'title'   => 'Convites',
            'invites' => InviteRepo::all(),
        ]);
    }

    public function createInvite(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $label    = trim((string) ($_POST['label'] ?? ''));
        $maxUses  = max(0, (int) ($_POST['max_uses'] ?? 0));
        $days     = (int) ($_POST['expires_days'] ?? 0);
        $expires  = $days > 0
            ? Time::nowUtc()->modify("+{$days} days")->format('Y-m-d\TH:i:s\Z')
            : null;

        InviteRepo::create((int) Auth::id(), $label, $maxUses, $expires);
        Flash::success('Convite criado! Copie o link e mande para a galera.');
        redirect('/admin/convites');
    }

    public function revokeInvite(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        InviteRepo::revoke((int) $id);
        Flash::success('Convite revogado.');
        redirect('/admin/convites');
    }

    public function settings(): void
    {
        Auth::requireAdmin();
        View::render('admin/config', [
            'title'        => 'Configurações',
            'settings'     => SettingRepo::all(),
            'matchCount'   => MatchRepo::count(),
            'userCount'    => UserRepo::count(),
        ]);
    }

    public function saveSettings(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $before = SettingRepo::scoring();
        $map = [
            'points_exact'  => 'exact',
            'points_diff'   => 'diff',
            'points_winner' => 'winner',
            'points_miss'   => 'miss',
        ];
        foreach ($map as $key => $_) {
            if (isset($_POST[$key]) && is_numeric($_POST[$key])) {
                SettingRepo::set($key, (string) (int) $_POST[$key]);
            }
        }
        $after = SettingRepo::scoring();

        if ($before !== $after) {
            Scoring::recomputeAll();
            Flash::success('Pontuação atualizada e ranking recalculado.');
        } else {
            Flash::info('Nenhuma mudança na pontuação.');
        }
        redirect('/admin/config');
    }

    public function sync(): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $res = SyncService::run();
        if ($res['status'] === 'ok') {
            Flash::success('Sincronizado: ' . $res['message']);
        } else {
            Flash::error('Falha no sync: ' . $res['message']);
        }
        redirect('/admin/config');
    }
}
