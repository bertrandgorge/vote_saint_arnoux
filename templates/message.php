<?php $pageTitle = $title; ?>
<section class="card narrow">
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <?php if (!empty($action)): ?>
        <p><a class="btn primary" href="<?= e($action[0]) ?>"><?= e($action[1]) ?></a></p>
    <?php else: ?>
        <p><a href="/">← Retour à l’accueil</a></p>
    <?php endif; ?>
</section>
