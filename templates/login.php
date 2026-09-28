<?php $pageTitle = 'Connexion'; ?>
<section class="card narrow">
    <?php if ($sent): ?>
        <h1>Vérifiez votre messagerie</h1>
        <p>Un lien de connexion vient d’être envoyé à <strong><?= e($email) ?></strong>.
           Il est valable <?= (int) config('email_link_ttl_hours') ?> heures.</p>
        <p class="muted">Rien reçu ? Vérifiez les courriers indésirables, ou contactez un administrateur qui pourra vous transmettre un lien personnel.</p>
        <p><a href="/connexion">← Saisir une autre adresse</a></p>
    <?php else: ?>
        <h1>Connexion</h1>
        <p>Saisissez votre adresse e-mail : vous recevrez un lien de connexion personnel, sans mot de passe.</p>
        <?php if (!empty($error)): ?>
            <p class="flash error"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="post" action="/connexion" class="stack">
            <?= csrf_field() ?>
            <label>Adresse e-mail
                <input type="email" name="email" required value="<?= e($email) ?>" autofocus autocomplete="email">
            </label>
            <button class="btn primary">Recevoir mon lien</button>
        </form>
    <?php endif; ?>
</section>
