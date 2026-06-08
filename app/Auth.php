<?php

declare(strict_types=1);

namespace App;

use App\Repositories\UserRepo;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $pass): bool
    {
        $u = UserRepo::findByEmail($email);
        if (!$u || !password_verify($pass, (string) $u['password_hash'])) {
            return false;
        }
        if (password_needs_rehash((string) $u['password_hash'], PASSWORD_DEFAULT)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [
                password_hash($pass, PASSWORD_DEFAULT),
                $u['id'],
            ]);
        }
        self::login((int) $u['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        self::$user = null;
        self::$loaded = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
        self::$loaded = false;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $uid = $_SESSION['uid'] ?? null;
        self::$user = $uid ? (UserRepo::find((int) $uid) ?: null) : null;
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && (int) $u['is_admin'] === 1;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Flash::info('Faça login para continuar.');
            redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('error', ['title' => 'Acesso negado', 'code' => 403, 'message' => 'Área restrita ao administrador.']);
            exit;
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('/ranking');
        }
    }
}
