<article class="auth-card">
    <hgroup>
        <h2>Entrar</h2>
        <p>Bolão da Copa do Mundo 2026</p>
    </hgroup>
    <form method="post" action="<?= e(base_url('/login')) ?>">
        <?= \App\Csrf::field() ?>
        <label>
            E-mail
            <input type="email" name="email" required autocomplete="email" placeholder="voce@exemplo.com">
        </label>
        <label>
            Senha
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </label>
        <button type="submit">Entrar</button>
    </form>
    <p class="muted"><small>Não tem conta? Peça um link de convite ao organizador do bolão.</small></p>
</article>
