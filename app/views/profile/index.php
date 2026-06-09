<?php

use App\Csrf;

/** @var array $user */
?>
<hgroup>
    <h2>👤 Meu perfil</h2>
    <p>Atualize seu nome de exibição e sua senha.</p>
</hgroup>

<article>
    <h3>Nome de exibição</h3>
    <form method="post" action="<?= e(base_url('/perfil/nome')) ?>">
        <?= Csrf::field() ?>
        <label>
            Nome
            <input type="text" name="name" value="<?= e($user['name']) ?>" maxlength="60" required>
        </label>
        <button type="submit">Salvar nome</button>
    </form>
</article>

<article>
    <h3>Trocar senha</h3>
    <form method="post" action="<?= e(base_url('/perfil/senha')) ?>">
        <?= Csrf::field() ?>
        <label>
            Senha atual
            <input type="password" name="current" required autocomplete="current-password">
        </label>
        <label>
            Nova senha (mín. 8 caracteres)
            <input type="password" name="new" required minlength="8" autocomplete="new-password">
        </label>
        <label>
            Confirmar nova senha
            <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit">Alterar senha</button>
    </form>
</article>

<p class="rank-sub">E-mail de login: <strong><?= e($user['email']) ?></strong></p>
