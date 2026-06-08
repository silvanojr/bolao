<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\View;
use App\Repositories\LeagueRepo;

final class LeagueController
{
    public function index(): void
    {
        Auth::requireAuth();
        View::render('leagues/index', [
            'title'   => 'Ligas',
            'leagues' => LeagueRepo::forUser((int) Auth::id()),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        Csrf::verify();
        $name = trim((string) ($_POST['name'] ?? ''));
        if (mb_strlen($name) < 2) {
            Flash::error('Dê um nome para a liga.');
            redirect('/ligas');
        }
        $name = mb_substr($name, 0, 40);
        LeagueRepo::create($name, (int) Auth::id());
        Flash::success('Liga criada! Compartilhe o link para a galera entrar.');
        redirect('/ligas');
    }

    /** Entrada via link compartilhável /liga/{code}. */
    public function join(string $code): void
    {
        if (!Auth::check()) {
            $_SESSION['pending_league'] = $code;
            Flash::info('Entre ou crie sua conta para entrar na liga.');
            redirect('/login');
        }
        $l = LeagueRepo::joinByCode((int) Auth::id(), $code);
        if ($l === null) {
            Flash::error('Liga não encontrada.');
            redirect('/ligas');
        }
        Flash::success('Você entrou na liga "' . $l['name'] . '"!');
        redirect('/ranking?liga=' . $l['code']);
    }

    /** Entrada via formulário (digitando o código). */
    public function joinByForm(): void
    {
        Auth::requireAuth();
        Csrf::verify();
        $code = trim((string) ($_POST['code'] ?? ''));
        $l = LeagueRepo::joinByCode((int) Auth::id(), $code);
        if ($l === null) {
            Flash::error('Código de liga inválido.');
            redirect('/ligas');
        }
        Flash::success('Você entrou na liga "' . $l['name'] . '"!');
        redirect('/ranking?liga=' . $l['code']);
    }

    public function leave(string $id): void
    {
        Auth::requireAuth();
        Csrf::verify();
        $l = LeagueRepo::find((int) $id);
        if ($l !== null && (int) $l['is_default'] === 1) {
            Flash::error('Não dá para sair da liga Geral.');
            redirect('/ligas');
        }
        LeagueRepo::leave((int) $id, (int) Auth::id());
        Flash::success('Você saiu da liga.');
        redirect('/ligas');
    }
}
