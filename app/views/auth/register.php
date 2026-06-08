<article class="auth-card">
    <hgroup>
        <h2>Criar conta</h2>
        <p>Você foi convidado para o bolão 🎉</p>
    </hgroup>
    <form method="post" action="<?= e(base_url('/convite/' . $token)) ?>">
        <?= \App\Csrf::field() ?>
        <label>
            Seu nome
            <input type="text" name="name" required autocomplete="name" placeholder="Como aparece no ranking">
        </label>
        <label>
            E-mail
            <input type="email" name="email" required autocomplete="email" placeholder="voce@exemplo.com">
        </label>
        <label>
            Senha (mín. 8 caracteres)
            <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="••••••••">
        </label>
        <button type="submit">Criar conta e entrar</button>
    </form>
    <p class="muted"><small>Já tem conta? <a href="<?= e(base_url('/login')) ?>">Entrar</a></small></p>
</article>
