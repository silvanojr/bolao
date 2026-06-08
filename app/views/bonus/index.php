<?php

use App\Csrf;
use App\Repositories\BonusRepo;
use App\Time;

/** @var array $teams */
/** @var array $picks */
/** @var bool $locked */
/** @var ?string $deadline */
/** @var array $points */

$ptMap = [
    'champion'  => (int) ($points['bonus_champion']  ?? 20),
    'runner_up' => (int) ($points['bonus_runner_up'] ?? 10),
    'third'     => (int) ($points['bonus_third']     ?? 7),
];
$flag = static fn(string $cc): string => 'https://api.fifa.com/api/v3/picture/flags-sq-4/' . rawurlencode($cc);
?>
<hgroup>
    <h2>⭐ Palpite de campeão</h2>
    <p>Quem leva a taça, o vice e o 3º lugar. Vale pontos extras no ranking.</p>
</hgroup>

<p>
    <?php foreach (BonusRepo::KINDS as $k => $label): ?>
        <span class="badge" style="background:#eef8f1;color:#0a6b3b"><?= e($label) ?> = <?= $ptMap[$k] ?> pts</span>
    <?php endforeach; ?>
</p>

<?php if ($locked): ?>
    <p class="deadline-note">🔒 Os palpites de bônus fecharam<?= $deadline ? ' em ' . e(Time::friendly($deadline)) : '' ?>.</p>
    <article>
        <?php foreach (BonusRepo::KINDS as $kind => $label): ?>
            <?php $p = $picks[$kind] ?? null; ?>
            <div class="bonus-pick">
                <span>
                    <strong><?= e($label) ?>:</strong>
                    <?php if ($p): ?>
                        <img class="bonus-flag" src="<?= e($flag($p['country'])) ?>" alt=""><?= e($p['team_name']) ?>
                    <?php else: ?>
                        <span class="rank-sub">— sem palpite</span>
                    <?php endif; ?>
                </span>
                <?php if ($p && $p['points'] !== null): ?>
                    <span class="pts <?= (int) $p['points'] > 0 ? 'pts-5' : 'pts-0' ?>"><?= (int) $p['points'] ?> pts</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </article>
<?php else: ?>
    <?php if ($deadline): ?>
        <p class="deadline-note">⏳ Você pode editar até o 1º jogo da Copa (<?= e(Time::friendly($deadline)) ?>).</p>
    <?php endif; ?>
    <article>
        <form method="post" action="<?= e(base_url('/campeao')) ?>" class="bonus-grid">
            <?= Csrf::field() ?>
            <?php foreach (BonusRepo::KINDS as $kind => $label): ?>
                <?php $cur = $picks[$kind]['country'] ?? ''; ?>
                <label>
                    <?= e($label) ?> (<?= $ptMap[$kind] ?> pts)
                    <select name="<?= e($kind) ?>">
                        <option value="">— escolha —</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= e($t['country']) ?>" <?= $t['country'] === $cur ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
            <button type="submit">Salvar palpites</button>
        </form>
    </article>
<?php endif; ?>
