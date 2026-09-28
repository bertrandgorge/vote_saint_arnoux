<?php
$pageTitle = 'Classement';
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
