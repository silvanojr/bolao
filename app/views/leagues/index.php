<?php

use App\Csrf;

/** @var array $leagues */
?>
<hgroup>
    <h2>👥 Ligas</h2>
    <p>Seus palpites valem em todas as ligas. Crie uma liga ou entre numa pelo link/código.</p>
</hgroup>

<?php foreach ($leagues as $l): ?>
    <article>
        <header style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <strong><?= e($l['name']) ?><?= (int) $l['is_default'] === 1 ? ' <small class="rank-sub">(padrão)</small>' : '' ?></strong>
            <span class="rank-sub"><?= (int) $l['members'] ?> membro(s)</span>
        </header>
        <?php if ((int) $l['is_default'] !== 1): ?>
            <div class="invite-link"><?= e(abs_url('/liga/' . $l['code'])) ?></div>
        <?php endif; ?>
        <footer style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <a href="<?= e(base_url('/ranking?liga=' . $l['code'])) ?>">Ver ranking →</a>
            <?php if ((int) $l['is_default'] !== 1): ?>
                <form method="post" action="<?= e(base_url('/ligas/' . (int) $l['id'] . '/sair')) ?>" class="logout-form"
                      onsubmit="return confirm('Sair da liga \'<?= e($l['name']) ?>\'?')">
                    <?= Csrf::field() ?>
                    <button type="submit" class="secondary outline" style="width:auto;padding:.2rem .8rem;font-size:.8rem;margin:0">Sair</button>
                </form>
            <?php endif; ?>
        </footer>
    </article>
<?php endforeach; ?>

<article>
    <h3>Criar uma liga</h3>
    <form method="post" action="<?= e(base_url('/ligas')) ?>">
        <?= Csrf::field() ?>
        <label>
            Nome da liga
            <input type="text" name="name" maxlength="40" required placeholder="ex.: Amigos da pelada">
        </label>
        <button type="submit">Criar liga</button>
    </form>
</article>

<article>
    <h3>Entrar numa liga</h3>
    <form method="post" action="<?= e(base_url('/ligas/entrar')) ?>">
        <?= Csrf::field() ?>
        <label>
            Código da liga
            <input type="text" name="code" required placeholder="cole o código aqui">
        </label>
        <button type="submit" class="secondary">Entrar</button>
    </form>
    <small class="rank-sub">Dica: o link de convite da liga já entra direto, sem precisar do código.</small>
</article>
