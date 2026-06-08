<?php

use App\Time;

/** @var array $stats */
/** @var array $rows */
?>
<hgroup>
    <h2>🎯 Minhas apostas</h2>
    <p>Seus palpites e pontos.</p>
</hgroup>

<div class="stat-row">
    <div class="stat"><b><?= (int) $stats['total'] ?></b>pontos</div>
    <div class="stat"><b><?= (int) $stats['palpites'] ?></b>palpites</div>
    <div class="stat"><b><?= (int) $stats['exacts'] ?></b>placares exatos</div>
</div>

<?php if (count($rows) === 0): ?>
    <p style="margin-top:1rem">Você ainda não palpitou. <a href="<?= e(base_url('/jogos')) ?>">Ver jogos →</a></p>
<?php else: ?>
    <table style="margin-top:1rem">
        <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
            $finished = $r['home_goals'] !== null && $r['away_goals'] !== null && (int) $r['status'] !== 1 && (int) $r['status'] !== 3;
            $cls = ($r['points'] === null) ? 'pts-0'
                : ((int) $r['is_exact'] === 1 ? 'pts-5' : ((int) $r['is_three'] === 1 ? 'pts-3' : ((int) $r['points'] > 0 ? 'pts-1' : 'pts-0')));
            $home = $r['home_team'] ?: ($r['home_ph'] ?: '?');
            $away = $r['away_team'] ?: ($r['away_ph'] ?: '?');
            ?>
            <tr>
                <td>
                    <small class="rank-sub"><?= e(Time::friendly($r['utc_kickoff'])) ?></small><br>
                    <?= e($home) ?> <strong><?= (int) $r['pred_home'] ?>×<?= (int) $r['pred_away'] ?></strong> <?= e($away) ?>
                </td>
                <td style="text-align:right; white-space:nowrap">
                    <?php if ($finished): ?>
                        <small class="rank-sub">real <?= (int) $r['home_goals'] ?>×<?= (int) $r['away_goals'] ?></small><br>
                        <span class="pts <?= $cls ?>"><?= (int) $r['points'] ?> pts</span>
                    <?php else: ?>
                        <small class="rank-sub">—</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
