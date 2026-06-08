<article class="error-page">
    <hgroup>
        <h2><?= e($code ?? 'Ops') ?></h2>
        <p><?= e($message ?? 'Algo deu errado.') ?></p>
    </hgroup>
    <a href="<?= e(base_url('/')) ?>" role="button" class="contrast">Voltar ao início</a>
</article>
