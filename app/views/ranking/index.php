<?php

/** @var array $rows */
/** @var int $meId */
?>
<hgroup>
    <h2>🏆 Ranking</h2>
    <p>Placar exato vale 5, vencedor + saldo 3, só o resultado 1.</p>
</hgroup>

<table class="rank-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Participante</th>
            <th style="text-align:right">Pts</th>
        </tr>
    </thead>
    <tbody>
    <?php $pos = 0; foreach ($rows as $r): $pos++; ?>
        <tr class="<?= (int) $r['id'] === $meId ? 'rank-me' : '' ?>">
            <td class="rank-pos <?= $pos <= 3 ? 'rank-top' : '' ?>">
                <?= $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : $pos)) ?>
            </td>
            <td>
                <?= e($r['name']) ?><?= (int) $r['id'] === $meId ? ' <small>(você)</small>' : '' ?>
                <div class="rank-sub"><?= (int) $r['exacts'] ?> exatos · <?= (int) $r['palpites'] ?> palpites</div>
            </td>
            <td class="rank-total"><?= (int) $r['total'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
