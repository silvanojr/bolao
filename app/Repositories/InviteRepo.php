<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;
use App\Time;

final class InviteRepo
{
    public static function create(int $createdBy, ?string $label, int $maxUses, ?string $expiresAt): array
    {
        $token = bin2hex(random_bytes(16)); // 128 bits
        Db::run(
            'INSERT INTO invites (token, label, created_by, max_uses, expires_at) VALUES (?, ?, ?, ?, ?)',
            [$token, $label !== '' ? $label : null, $createdBy, $maxUses, $expiresAt]
        );
        return self::findByToken($token) ?? [];
    }

    public static function findByToken(string $token): ?array
    {
        return Db::one('SELECT * FROM invites WHERE token = ?', [$token]);
    }

    public static function all(): array
    {
        return Db::all(
            'SELECT i.*, u.name AS creator
               FROM invites i JOIN users u ON u.id = i.created_by
              ORDER BY i.created_at DESC'
        );
    }

    public static function revoke(int $id): void
    {
        Db::run('UPDATE invites SET revoked = 1 WHERE id = ?', [$id]);
    }

    public static function consume(int $id): void
    {
        Db::run('UPDATE invites SET used_count = used_count + 1 WHERE id = ?', [$id]);
    }

    public static function isValid(array $inv): bool
    {
        if ((int) $inv['revoked'] === 1) {
            return false;
        }
        if (!empty($inv['expires_at']) && Time::isPast((string) $inv['expires_at'])) {
            return false;
        }
        $max = (int) $inv['max_uses'];
        if ($max > 0 && (int) $inv['used_count'] >= $max) {
            return false;
        }
        return true;
    }
}
