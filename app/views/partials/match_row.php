<?php

use App\Csrf;
use App\Repositories\MatchRepo;
use App\Time;

/** @var array $m */
/** @var ?array $pred */

$hasTeams    = MatchRepo::hasTeams($m);
$finished    = MatchRepo::isFinished($m);
$live        = MatchRepo::isLive($m);
$predictable = MatchRepo::isPredictable($m);

$flag = static fn(?string $cc): ?string =>
    $cc ? 'https://api.fifa.com/api/v3/picture/flags-sq-4/' . rawurlencode($cc) : null;

$teamBlock = static function (string $side) use ($m, $flag): string {
    $name = $m[$side . '_team'] ?? null;
    $cc   = $m[$side . '_country'] ?? null;
    $ph   = $m[$side . '_ph'] ?? null;
    $img  = ($name && $cc) ? '<img src="' . e($flag($cc)) . '" alt="" loading="lazy">' : '';
    $label = $name
        ? '<span class="name">' . e($name) . '</span>'
        : '<span class="name ph">' . e($ph ?: 'A definir') . '</span>';
    return $side === 'home' ? $label . $img : $img . $label;
};

// classe de cor dos pontos (semântica, independe dos valores configurados)
$ptsClass = static function (?array $p): string {
    if (!$p || $p['points'] === null) {
        return 'pts-0';
    }
    if ((int) $p['is_exact'] === 1) {
        return 'pts-5';
    }
    if ((int) $p['is_three'] === 1) {
        return 'pts-3';
    }
    return (int) $p['points'] > 0 ? 'pts-1' : 'pts-0';
};
?>
<div class="match" id="m<?= (int) $m['id'] ?>">
    <div class="match-meta">
        <span><?= e($m['grp'] ?? $m['stage']) ?> · Jogo <?= (int) $m['match_number'] ?></span>
        <span>
            <?php if ($finished): ?>Encerrado<?php elseif ($live): ?><span class="badge badge-live">● AO VIVO</span>
            <?php elseif (!$hasTeams): ?><span class="badge badge-tbd">A definir</span>
            <?php else: ?><?= e(Time::timeOnly($m['utc_kickoff'])) ?><?php endif; ?>
            <?php if ($m['stadium']): ?> · <?= e($m['stadium']) ?><?php endif; ?>
        </span>
    </div>

    <?php if ($predictable): ?>
        <form method="post" action="<?= e(base_url('/jogos/' . (int) $m['id'] . '/palpite')) ?>">
            <?= Csrf::field() ?>
            <div class="match-row">
                <div class="team home"><?= $teamBlock('home') ?></div>
                <div class="score-box">
                    <input type="number" name="pred_home" min="0" max="30" inputmode="numeric"
                           value="<?= $pred !== null ? (int) $pred['pred_home'] : '' ?>" required aria-label="Gols mandante">
                    <span class="x">×</span>
                    <input type="number" name="pred_away" min="0" max="30" inputmode="numeric"
                           value="<?= $pred !== null ? (int) $pred['pred_away'] : '' ?>" required aria-label="Gols visitante">
                </div>
                <div class="team away"><?= $teamBlock('away') ?></div>
            </div>
            <div class="match-actions">
                <span class="countdown" data-kickoff="<?= e($m['utc_kickoff']) ?>"></span>
                <button type="submit"><?= $pred !== null ? 'Atualizar' : 'Salvar' ?></button>
            </div>
        </form>
    <?php else: ?>
        <div class="match-row">
            <div class="team home"><?= $teamBlock('home') ?></div>
            <div>
                <?php if ($finished || $live): ?>
                    <span class="final-score"><?= (int) ($m['home_goals'] ?? 0) ?>×<?= (int) ($m['away_goals'] ?? 0) ?></span>
                    <?php if ($m['home_pens'] !== null && $m['away_pens'] !== null): ?>
                        <span class="pens">pên <?= (int) $m['home_pens'] ?>×<?= (int) $m['away_pens'] ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="x">×</span>
                <?php endif; ?>
            </div>
            <div class="team away"><?= $teamBlock('away') ?></div>
        </div>

        <?php if (!$hasTeams): ?>
            <p class="your-pick">Definido após a fase de grupos.</p>
        <?php elseif ($pred !== null): ?>
            <p class="your-pick">
                Seu palpite: <strong><?= (int) $pred['pred_home'] ?>×<?= (int) $pred['pred_away'] ?></strong>
                <?php if ($finished && $pred['points'] !== null): ?>
                    — <span class="pts <?= $ptsClass($pred) ?>"><?= (int) $pred['points'] ?> pt<?= (int) $pred['points'] === 1 ? '' : 's' ?></span>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="your-pick"><span class="badge badge-lock">🔒 sem palpite</span></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
