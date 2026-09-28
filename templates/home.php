<?php
$pageTitle = 'Accueil';
$nbCriteria = count(criteria());
?>
<section class="section">
    <div class="section-head">
        <h1>Dossiers à évaluer</h1>
        <?php if ($canVote):
            $done = count(array_filter($progress, fn ($n) => $n >= $nbCriteria)); ?>
            <p class="muted">Vous avez entièrement noté <strong><?= $done ?></strong> dossier(s) sur <?= count($architects) ?>.</p>
        <?php endif; ?>
    </div>
    <ol class="architect-list">
        <?php foreach ($architects as $i => $a):
            $n = $progress[$a['id']] ?? 0;
            $state = $n === 0 ? 'todo' : ($n >= $nbCriteria ? 'done' : 'partial'); ?>
            <li class="architect-item state-<?= $state ?>">
                <span class="num"><?= $i + 1 ?></span>
                <a class="info" href="/architecte/<?= $a['id'] ?>">
                    <strong><?= e($a['agency']) ?></strong>
                    <span class="muted"><?= e(trim($a['referent'] . ' · ' . $a['city'], ' ·')) ?></span>
                </a>
                <?php if ($canVote): ?>
                    <span class="progress" title="<?= $n ?> critère(s) noté(s) sur <?= $nbCriteria ?>">
                        <?php for ($k = 1; $k <= $nbCriteria; $k++): ?><i class="<?= $k <= $n ? 'on' : '' ?>"></i><?php endfor; ?>
                    </span>
                <?php endif; ?>
                <span class="actions">
                    <?php if ($canVote): ?><a class="btn small primary" href="/architecte/<?= $a['id'] ?>">Noter</a><?php endif; ?>
                    <?php if ($me['is_admin']): ?><a class="btn small" href="/architecte/<?= $a['id'] ?>/tableau">Tableau de bord</a><?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
