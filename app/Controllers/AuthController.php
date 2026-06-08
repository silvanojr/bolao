<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Flash;
use App\View;
use App\Repositories\InviteRepo;
use App\Repositories\UserRepo;

final class AuthController
{
    public function showLogin(): void
    {
        Auth::requireGuest();
        View::render('auth/login', ['title' => 'Entrar']);
    }

    public function login(): void
    {
        Auth::requireGuest();
        Csrf::verify();
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');

        if (Auth::attempt($email, $pass)) {
            Flash::success('Bem-vindo de volta! 👋');
            redirect('/ranking');
        }
        Flash::error('E-mail ou senha incorretos.');
        redirect('/login');
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/login');
    }

    public function showRegister(string $token): void
    {
        Auth::requireGuest();
        $inv = InviteRepo::findByToken($token);
        if (!$inv || !InviteRepo::isValid($inv)) {
            View::render('error', [
                'title'   => 'Convite inválido',
                'code'    => 'Convite inválido',
                'message' => 'Este convite não existe, expirou ou já atingiu o limite de uso. Peça um novo link ao organizador.',
            ], 410);
            return;
        }
        View::render('auth/register', ['title' => 'Criar conta', 'token' => $token]);
    }

    public function register(string $token): void
    {
        Auth::requireGuest();
        Csrf::verify();

        $inv = InviteRepo::findByToken($token);
        if (!$inv || !InviteRepo::isValid($inv)) {
            Flash::error('Convite inválido ou expirado.');
            redirect('/login');
        }

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Informe seu nome.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }
        if (strlen($pass) < 8) {
            $errors[] = 'A senha deve ter ao menos 8 caracteres.';
        }
        if (!$errors && UserRepo::findByEmail($email)) {
            $errors[] = 'Já existe uma conta com esse e-mail.';
        }

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('/convite/' . $token);
        }

        $id = UserRepo::create($name, $email, password_hash($pass, PASSWORD_DEFAULT), false);
        InviteRepo::consume((int) $inv['id']);
        Auth::login($id);
        Flash::success('Conta criada! Bora palpitar 🎯');
        redirect('/jogos');
    }
}
