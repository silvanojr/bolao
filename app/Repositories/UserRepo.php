<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class UserRepo
{
    public static function findByEmail(string $email): ?array
    {
        return Db::one('SELECT * FROM users WHERE lower(email) = lower(?)', [$email]);
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function create(string $name, string $email, string $hash, bool $isAdmin = false): int
    {
        Db::run(
            'INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, ?)',
            [$name, $email, $hash, $isAdmin ? 1 : 0]
        );
        return Db::lastInsertId();
    }

    public static function all(): array
    {
        return Db::all('SELECT id, name, email, is_admin, created_at FROM users ORDER BY name COLLATE NOCASE');
    }

    public static function count(): int
    {
        $r = Db::one('SELECT COUNT(*) AS c FROM users');
        return (int) ($r['c'] ?? 0);
    }
}
