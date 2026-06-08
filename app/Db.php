<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Fábrica de conexão PDO (SQLite) compartilhada. Aplica os PRAGMAs necessários
 * em toda conexão: foreign_keys, WAL e busy_timeout.
 */
final class Db
{
    private static ?PDO $pdo = null;
    private static string $path = '';

    public static function init(string $path): void
    {
        self::$path = $path;
        self::$pdo = null;
    }

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . self::$path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }
        return self::$pdo;
    }

    /** Executa um SELECT e devolve todas as linhas. */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Executa um SELECT e devolve a primeira linha (ou null). */
    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /** Executa INSERT/UPDATE/DELETE e devolve nº de linhas afetadas. */
    public static function run(string $sql, array $params = []): int
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int) self::conn()->lastInsertId();
    }
}
