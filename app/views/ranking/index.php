<?php

/** @var array $rows */
/** @var int $meId */
/** @var array $leagues */
/** @var ?array $selected */
?>
<hgroup>
    <h2>🏆 Ranking</h2>
    <p>Exato 5 · vencedor+saldo 3 · resultado 1 · + bônus de campeão.</p>
</hgroup>

<?php if (count($leagues) > 1): ?>
    <div class="league-chips">
        <?php foreach ($leagues as $l): ?>
            <a href="<?= e(base_url('/ranking?liga=' . $l['code'])) ?>"
               class="<?= ($selected && $l['code'] === $selected['code']) ? 'active' : '' ?>">
                <?= e($l['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($selected === null): ?>
    <p>Você ainda não está em nenhuma liga. <a href="<?= e(base_url('/ligas')) ?>">Ver ligas →</a></p>
<?php else: ?>
    <p class="rank-sub"><?= e($selected['name']) ?> · <?= count($rows) ?> participante(s)</p>
    <table class="rank-table">
        <thead>
            <tr><th>#</th><th>Participante</th><th style="text-align:right">Pts</th></tr>
        </thead>
        <tbody>
        <?php $pos = 0; foreach ($rows as $r): $pos++; ?>
            <tr class="<?= (int) $r['id'] === $meId ? 'rank-me' : '' ?>">
                <td class="rank-pos <?= $pos <= 3 ? 'rank-top' : '' ?>">
                    <?= $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : $pos)) ?>
                </td>
                <td>
                    <?= e($r['name']) ?><?= (int) $r['id'] === $meId ? ' <small>(você)</small>' : '' ?>
                    <div class="rank-sub">
                        <?= (int) $r['exacts'] ?> exatos · <?= (int) $r['palpites'] ?> palpites<?php if ((int) $r['bonus'] > 0): ?> · 🏆 <?= (int) $r['bonus'] ?> bônus<?php endif; ?>
                    </div>
                </td>
                <td class="rank-total"><?= (int) $r['total'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
