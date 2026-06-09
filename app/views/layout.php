<?php

/** @var string $content */
$u = \App\Auth::user();
$flashes = \App\Flash::pull();
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a6b3b">
    <title><?= e($title ?? 'Bolão Copa 2026') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="<?= e(base_url('assets/app.css')) ?>">
</head>
<body>
<header class="app-header">
    <nav class="container">
        <a href="<?= e(base_url('/')) ?>" class="brand">⚽ Bolão&nbsp;2026</a>
        <?php if ($u): ?>
            <div class="header-right">
                <a href="<?= e(base_url('/perfil')) ?>" class="header-gear" aria-label="Meu perfil">👤</a>
                <?php if ((int) $u['is_admin'] === 1): ?>
                    <a href="<?= e(base_url('/admin/config')) ?>" class="header-gear" aria-label="Admin">⚙️</a>
                <?php endif; ?>
                <form method="post" action="<?= e(base_url('/logout')) ?>" class="logout-form">
                    <?= \App\Csrf::field() ?>
                    <button type="submit" class="link-btn">Sair</button>
                </form>
            </div>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<?php if ($u): ?>
<nav class="tabbar">
    <a href="<?= e(base_url('/jogos')) ?>"><span>🗓️</span>Jogos</a>
    <a href="<?= e(base_url('/campeao')) ?>"><span>⭐</span>Campeão</a>
    <a href="<?= e(base_url('/ranking')) ?>"><span>🏆</span>Ranking</a>
    <a href="<?= e(base_url('/ligas')) ?>"><span>👥</span>Ligas</a>
    <a href="<?= e(base_url('/minhas-apostas')) ?>"><span>🎯</span>Minhas</a>
</nav>
<?php endif; ?>

<footer class="container site-footer">
    <small>Bolão da Copa do Mundo 2026 · placares oficiais da FIFA</small>
</footer>

<script src="<?= e(base_url('assets/app.js')) ?>" defer></script>
</body>
</html>
