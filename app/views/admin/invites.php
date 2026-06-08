<?php

use App\Csrf;
use App\Repositories\InviteRepo;
use App\Time;

/** @var array $invites */
?>
<hgroup>
    <h2>Convites</h2>
    <p>Gere um link e mande para os amigos entrarem no bolão.</p>
</hgroup>

<p><a href="<?= e(base_url('/admin/config')) ?>">← Configurações</a></p>

<article>
    <form method="post" action="<?= e(base_url('/admin/convites')) ?>">
        <?= Csrf::field() ?>
        <label>
            Apelido do convite (opcional)
            <input type="text" name="label" placeholder="ex.: turma do trabalho">
        </label>
        <div class="grid">
            <label>
                Máximo de usos (0 = ilimitado)
                <input type="number" name="max_uses" value="0" min="0">
            </label>
            <label>
                Expira em (dias, 0 = nunca)
                <input type="number" name="expires_days" value="0" min="0">
            </label>
        </div>
        <button type="submit">Gerar convite</button>
    </form>
</article>

<?php if (count($invites) === 0): ?>
    <p>Nenhum convite ainda.</p>
<?php else: ?>
    <?php foreach ($invites as $inv): ?>
        <?php
        $valid = InviteRepo::isValid($inv);
        $url = abs_url('/convite/' . $inv['token']);
        ?>
        <article>
            <header style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
                <strong><?= e($inv['label'] ?: 'Convite') ?></strong>
                <?php if ((int) $inv['revoked'] === 1): ?>
                    <span class="badge badge-lock">revogado</span>
                <?php elseif (!$valid): ?>
                    <span class="badge badge-lock">expirado/esgotado</span>
                <?php else: ?>
                    <span class="badge" style="background:#e4f5ec;color:#0a6b3b">ativo</span>
                <?php endif; ?>
            </header>
            <div class="invite-link"><?= e($url) ?></div>
            <footer style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
                <small class="rank-sub">
                    usos: <?= (int) $inv['used_count'] ?><?= (int) $inv['max_uses'] > 0 ? '/' . (int) $inv['max_uses'] : '' ?>
                    <?php if (!empty($inv['expires_at'])): ?> · expira <?= e(Time::friendly($inv['expires_at'])) ?><?php endif; ?>
                </small>
                <?php if ((int) $inv['revoked'] === 0): ?>
                    <form method="post" action="<?= e(base_url('/admin/convites/' . (int) $inv['id'] . '/revogar')) ?>" class="logout-form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="secondary outline" style="width:auto;padding:.2rem .8rem;font-size:.8rem;margin:0">Revogar</button>
                    </form>
                <?php endif; ?>
            </footer>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
