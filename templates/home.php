<?php
$pageTitle = 'Accueil';
$nbCriteria = count(criteria());
?>
<section class="section">
    <div class="section-head">
        <h1>Dossiers à évaluer</h1>
        <?php if ($canVote):
            $done = count(array_filter($progress, fn ($s) => count($s) >= $nbCriteria)); ?>
            <p class="muted">Vous avez entièrement noté <strong><?= $done ?></strong> dossier(s) sur <?= count($architects) ?>.</p>
        <?php endif; ?>
    </div>
    <ol class="architect-list">
        <?php foreach ($architects as $i => $a):
            $scores = $progress[$a['id']] ?? [];
            $n = count($scores);
            $state = $n === 0 ? 'todo' : ($n >= $nbCriteria ? 'done' : 'partial'); ?>
            <li class="architect-item state-<?= $state ?>">
                <span class="num"><?= $i + 1 ?></span>
                <a class="info" href="/architecte/<?= $a['id'] ?>">
                    <strong><?= e($a['agency']) ?></strong>
                    <span class="muted"><?= e(trim($a['referent'] . ' · ' . $a['city'], ' ·')) ?></span>
                </a>
                <?php if ($canVote): ?>
                    <span class="progress" title="<?= $n ?> critère(s) noté(s) sur <?= $nbCriteria ?>">
                        <?php foreach (criteria() as $k => $c): ?><i<?= isset($scores[$k]) ? ' class="s' . $scores[$k] . '"' : '' ?> title="Critère <?= $k ?> : <?= isset($scores[$k]) ? $scores[$k] . ' / 5' : 'non évalué' ?>"></i><?php endforeach; ?>
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
