<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\View;
use App\Repositories\UserRepo;

final class ProfileController
{
    public function show(): void
    {
        Auth::requireAuth();
        View::render('profile/index', [
            'title' => 'Meu perfil',
            'user'  => Auth::user(),
        ]);
    }

    public function updateName(): void
    {
        Auth::requireAuth();
        Csrf::verify();

        $name = trim((string) ($_POST['name'] ?? ''));
        if (mb_strlen($name) < 2) {
            Flash::error('Informe um nome com pelo menos 2 caracteres.');
            redirect('/perfil');
        }
        $name = mb_substr($name, 0, 60);
        UserRepo::updateName((int) Auth::id(), $name);
        Flash::success('Nome atualizado!');
        redirect('/perfil');
    }

    public function updatePassword(): void
    {
        Auth::requireAuth();
        Csrf::verify();

        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        $u = Auth::user();
        if ($u === null || !password_verify($current, (string) $u['password_hash'])) {
            Flash::error('Senha atual incorreta.');
            redirect('/perfil');
        }
        if (strlen($new) < 8) {
            Flash::error('A nova senha deve ter ao menos 8 caracteres.');
            redirect('/perfil');
        }
        if ($new !== $confirm) {
            Flash::error('A confirmação não confere com a nova senha.');
            redirect('/perfil');
        }

        UserRepo::updatePassword((int) $u['id'], password_hash($new, PASSWORD_DEFAULT));
        session_regenerate_id(true); // mantém a sessão atual válida após trocar a senha
        Flash::success('Senha alterada com sucesso!');
        redirect('/perfil');
    }
}
