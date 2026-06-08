<?php

use App\Csrf;
use App\Time;

/** @var array $settings */
/** @var int $matchCount */
/** @var int $userCount */

$syncAt = $settings['last_sync_at'] ?? '';
$syncStatus = $settings['last_sync_status'] ?? '';
?>
<hgroup>
    <h2>⚙️ Configurações</h2>
    <p>Pontuação, sincronização e convites.</p>
</hgroup>

<div class="stat-row">
    <div class="stat"><b><?= (int) $matchCount ?></b>jogos</div>
    <div class="stat"><b><?= (int) $userCount ?></b>participantes</div>
</div>

<article>
    <h3>Pontuação</h3>
    <form method="post" action="<?= e(base_url('/admin/config')) ?>">
        <?= Csrf::field() ?>
        <div class="grid">
            <label>Placar exato
                <input type="number" name="points_exact" value="<?= (int) ($settings['points_exact'] ?? 5) ?>" min="0">
            </label>
            <label>Vencedor + saldo
                <input type="number" name="points_diff" value="<?= (int) ($settings['points_diff'] ?? 3) ?>" min="0">
            </label>
        </div>
        <div class="grid">
            <label>Só o resultado
                <input type="number" name="points_winner" value="<?= (int) ($settings['points_winner'] ?? 1) ?>" min="0">
            </label>
            <label>Errou
                <input type="number" name="points_miss" value="<?= (int) ($settings['points_miss'] ?? 0) ?>" min="0">
            </label>
        </div>
        <button type="submit">Salvar pontuação</button>
        <small class="rank-sub">Mudar os valores recalcula o ranking inteiro.</small>
    </form>
</article>

<article>
    <h3>Sincronização com a FIFA</h3>
    <p>
        Última: <?= $syncAt ? e(Time::friendly($syncAt)) : 'nunca' ?>
        <?php if ($syncStatus === 'ok'): ?><span class="badge" style="background:#e4f5ec;color:#0a6b3b">ok</span>
        <?php elseif ($syncStatus): ?><span class="badge badge-live"><?= e($syncStatus) ?></span><?php endif; ?>
        <br><small class="rank-sub"><?= e($settings['last_sync_message'] ?? '') ?></small>
    </p>
    <form method="post" action="<?= e(base_url('/admin/sync')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="secondary">Sincronizar agora</button>
    </form>
    <small class="rank-sub">O cron já faz isso automaticamente; este botão é para forçar na hora.</small>
</article>

<article>
    <h3>Convites</h3>
    <p>Gere links para os amigos entrarem.</p>
    <a href="<?= e(base_url('/admin/convites')) ?>" role="button" class="contrast">Gerenciar convites</a>
</article>
