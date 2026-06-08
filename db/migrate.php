<?php

declare(strict_types=1);

/**
 * Cria/atualiza o banco SQLite a partir de db/schema.sql, semeia as settings
 * padrão e cria (ou atualiza a senha do) primeiro usuário admin.
 *
 * Uso:
 *   php db/migrate.php --admin-name="Silvano" --admin-email="voce@exemplo.com" --admin-pass="suaSenha"
 *
 * Reexecutar é seguro (idempotente): tabelas usam IF NOT EXISTS e as settings
 * só são inseridas se ainda não existirem.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Db;

// --- parse de argumentos --admin-x=valor ou --admin-x valor ---
$args = [];
$argvv = $argv;
array_shift($argvv);
for ($i = 0; $i < count($argvv); $i++) {
    $a = $argvv[$i];
    if (str_starts_with($a, '--')) {
        $key = substr($a, 2);
        if (str_contains($key, '=')) {
            [$key, $val] = explode('=', $key, 2);
        } else {
            $val = $argvv[$i + 1] ?? '';
            $i++;
        }
        $args[$key] = $val;
    }
}

$pdo = Db::conn();

// --- schema ---
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Não consegui ler schema.sql\n");
    exit(1);
}
$pdo->exec($schema);
echo "✓ Tabelas criadas/atualizadas.\n";

// --- settings padrão ---
$defaults = [
    'points_exact'      => '5',
    'points_diff'       => '3',
    'points_winner'     => '1',
    'points_miss'       => '0',
    'fifa_competition'  => (string) config('fifa')['competition'],
    'fifa_season'       => (string) config('fifa')['season'],
    'last_sync_at'      => '',
    'last_sync_status'  => '',
    'last_sync_message' => '',
];
$ins = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
echo "✓ Settings padrão semeadas.\n";

// --- admin ---
$name  = trim((string) ($args['admin-name']  ?? getenv('ADMIN_NAME')  ?: ''));
$email = trim((string) ($args['admin-email'] ?? getenv('ADMIN_EMAIL') ?: ''));
$pass  = (string) ($args['admin-pass']  ?? getenv('ADMIN_PASS')  ?: '');

if ($email !== '' && $pass !== '') {
    if ($name === '') {
        $name = strstr($email, '@', true) ?: 'Admin';
    }
    if (strlen($pass) < 8) {
        fwrite(STDERR, "A senha do admin deve ter ao menos 8 caracteres.\n");
        exit(1);
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $existing = Db::one('SELECT id FROM users WHERE lower(email) = lower(?)', [$email]);
    if ($existing) {
        Db::run(
            'UPDATE users SET name = ?, password_hash = ?, is_admin = 1 WHERE id = ?',
            [$name, $hash, $existing['id']]
        );
        echo "✓ Admin atualizado: {$email}\n";
    } else {
        Db::run(
            'INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)',
            [$name, $email, $hash]
        );
        echo "✓ Admin criado: {$email}\n";
    }
} else {
    echo "ℹ Nenhum admin criado (informe --admin-email e --admin-pass para criar).\n";
}

echo "Pronto. Banco em: " . config('db_path') . "\n";
