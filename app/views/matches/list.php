<?php

use App\Time;

/** @var array $matches */
/** @var array $preds */
$curDay = null;
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
            <h3 class="day-head"><?= e(Time::dayLabel($m['utc_kickoff'])) ?></h3>
        <?php endif; ?>
        <?php
        $pred = $preds[(int) $m['id']] ?? null;
        require __DIR__ . '/../partials/match_row.php';
        ?>
    <?php endforeach; ?>
<?php endif; ?>
