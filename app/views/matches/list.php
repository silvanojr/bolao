<?php

use App\Time;

/** @var array $matches */
/** @var array $preds */
$curDay = null;

// Dia-alvo do scroll automático: os jogos de hoje ou, na falta, o próximo dia
// com jogos (e, se a Copa já acabou, o último dia disputado).
$today = Time::todayKey();
$scrollDay = null;
foreach ($matches as $sm) {
    if (Time::dayKey($sm['utc_kickoff']) >= $today) {
        $scrollDay = Time::dayKey($sm['utc_kickoff']);
        break;
    }
}
if ($scrollDay === null && $matches) {
    $scrollDay = Time::dayKey($matches[array_key_last($matches)]['utc_kickoff']);
}
?>
<hgroup>
    <h2>Jogos</h2>
    <p>Dê seu palpite no placar. Fecha no apito inicial (horário de Brasília).</p>
</hgroup>

<?php if (count($matches) === 0): ?>
    <p>Nenhum jogo carregado ainda. Peça ao admin para sincronizar.</p>
<?php else: ?>
    <?php foreach ($matches as $m): ?>
        <?php
        $dk = Time::dayKey($m['utc_kickoff']);
        if ($dk !== $curDay):
            $curDay = $dk;
            ?>
            <h3 class="day-head"<?= $dk === $scrollDay ? ' id="today-head" data-scroll-target' : '' ?>><?= e(Time::dayLabel($m['utc_kickoff'])) ?></h3>
        <?php endif; ?>
        <?php
        $pred = $preds[(int) $m['id']] ?? null;
        require __DIR__ . '/../partials/match_row.php';
        ?>
    <?php endforeach; ?>
<?php endif; ?>
