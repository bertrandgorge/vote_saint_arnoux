<?php
$pageTitle = 'Accueil';
$nbCriteria = count(criteria());
$voters = count(array_filter(get_jury(), fn ($m) => $m['role'] === 'votant'));
?>
<section class="section">
    <div class="section-head">
        <h1>Classement</h1>
        <p class="muted">Score pondéré sur 100, calculé sur les <?= $voters ?> membres votants (hors consultatifs). Mise à jour automatique.</p>
    </div>
    <div class="table-wrap">
        <table class="grid ranking" id="ranking"
               data-src="/api/classement" data-voters="<?= $voters ?>"
               data-criteria='<?= e(json_encode(array_map(fn ($c) => ['short' => $c['short'], 'title' => $c['title'], 'weight' => $c['weight']], criteria()), JSON_UNESCAPED_UNICODE)) ?>'>
            <thead></thead>
            <tbody></tbody>
        </table>
    </div>
    <script type="application/json" id="ranking-data"><?= json_encode($ranking, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</section>

<section class="section">
    <div class="section-head">
        <h2>Dossiers à évaluer</h2>
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
                    <a class="btn small" href="/architecte/<?= $a['id'] ?>/tableau">Tableau de bord</a>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
