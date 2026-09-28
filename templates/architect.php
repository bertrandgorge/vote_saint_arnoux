<?php $pageTitle = $architect['agency']; ?>
<nav class="crumbs">
    <a href="/">← Tous les dossiers</a>
    <span class="pager">
        <?php if ($prevId): ?><a href="/architecte/<?= $prevId ?>" title="Dossier précédent">‹ Précédent</a><?php endif; ?>
        <span class="muted">Dossier <?= $position ?> / <?= $count ?></span>
        <?php if ($nextId): ?><a href="/architecte/<?= $nextId ?>" title="Dossier suivant">Suivant ›</a><?php endif; ?>
    </span>
</nav>

<section class="card architect-header">
    <div>
        <h1><?= e($architect['agency']) ?></h1>
        <dl class="facts">
            <?php if ($architect['referent']): ?><div><dt>Référent</dt><dd><?= e($architect['referent']) ?></dd></div><?php endif; ?>
            <?php if ($architect['city']): ?><div><dt>Ville</dt><dd><?= e($architect['city']) ?></dd></div><?php endif; ?>
            <?php if ($architect['website']): ?><div><dt>Site web</dt><dd><a href="<?= e($architect['website']) ?>" target="_blank" rel="noopener"><?= e(preg_replace('~^https?://(www\.)?~', '', rtrim($architect['website'], '/'))) ?></a></dd></div><?php endif; ?>
        </dl>
    </div>
    <div class="header-actions">
        <?php if ($architect['drive_url']): ?>
            <a class="btn primary big" href="<?= e($architect['drive_url']) ?>" target="_blank" rel="noopener"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h4.6l2 2.2h8.4A1.5 1.5 0 0 1 21 8.7v9.8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z" fill="currentColor"/></svg> Dossier de candidature</a>
        <?php else: ?>
            <span class="btn big disabled" title="Lien à renseigner dans les réglages"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h4.6l2 2.2h8.4A1.5 1.5 0 0 1 21 8.7v9.8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z" fill="currentColor"/></svg> Dossier non disponible</span>
        <?php endif; ?>
        <a class="btn" href="/architecte/<?= $architect['id'] ?>/tableau">Tableau de bord</a>
    </div>
</section>

<?php if (!$canVote): ?>
    <div class="flash">Vous n’êtes pas membre du jury : la grille est affichée en lecture seule.</div>
<?php endif; ?>

<form class="scoring" id="scoring" data-architect="<?= $architect['id'] ?>" onsubmit="return false">
    <div class="scoring-summary">
        <div>
            <span class="muted">Votre score pondéré</span>
            <strong id="my-total">—</strong><span class="muted">/ 100</span>
        </div>
        <span class="save-state" id="save-state" aria-live="polite"></span>
    </div>

    <?php foreach (criteria() as $n => $c): $current = $scores[$n] ?? null; ?>
        <fieldset class="criterion" data-criterion="<?= $n ?>" <?= $canVote ? '' : 'disabled' ?>>
            <legend>
                <span class="crit-num">Critère <?= $n ?></span>
                <span class="crit-title"><?= e($c['title']) ?></span>
                <span class="weight"><?= $c['weight'] ?> %</span>
            </legend>
            <?php if (!empty($c['note'])): ?>
                <p class="crit-note">⚠️ <?= e($c['note']) ?></p>
            <?php endif; ?>

            <div class="likert" role="radiogroup" aria-label="Note du critère <?= $n ?>">
                <label class="opt opt-na">
                    <input type="radio" name="c<?= $n ?>" value="" <?= $current === null ? 'checked' : '' ?>>
                    <span>Non évalué</span>
                </label>
                <?php for ($s = 0; $s <= 5; $s++): ?>
                    <label class="opt opt-<?= $s ?>">
                        <input type="radio" name="c<?= $n ?>" value="<?= $s ?>" <?= $current === $s ? 'checked' : '' ?>>
                        <span><?= $s ?></span>
                    </label>
                <?php endfor; ?>
            </div>

            <div class="anchors">
                <p class="hint-low"><?= e($c['low']) ?></p>
                <p class="hint-high"><?= e($c['high']) ?></p>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <?php if ($nextId): ?>
        <p class="next-link"><a class="btn primary" href="/architecte/<?= $nextId ?>">Dossier suivant ›</a></p>
    <?php endif; ?>
</form>
<script type="application/json" id="weights"><?= json_encode(array_map(fn ($c) => $c['weight'], criteria())) ?></script>
