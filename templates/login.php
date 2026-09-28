<?php $pageTitle = 'Connexion'; ?>
<section class="card narrow">
    <?php if ($sent): ?>
        <h1>Vérifiez votre messagerie</h1>
        <p>Si l’adresse <strong><?= e($email) ?></strong> fait partie du jury, un lien de connexion vient de lui être envoyé.
           Il est valable <?= (int) config('email_link_ttl_hours') ?> heures.</p>
        <p class="muted">Rien reçu ? Vérifiez les courriers indésirables, ou contactez un administrateur qui pourra vous transmettre un lien personnel.</p>
        <p><a href="/connexion">← Saisir une autre adresse</a></p>
    <?php else: ?>
        <h1>Connexion</h1>
        <p>Saisissez votre adresse e-mail : vous recevrez un lien de connexion personnel, sans mot de passe.</p>
        <form method="post" action="/connexion" class="stack">
            <?= csrf_field() ?>
            <label>Adresse e-mail
                <input type="email" name="email" required autofocus autocomplete="email">
            </label>
            <button class="btn primary">Recevoir mon lien</button>
        </form>
    <?php endif; ?>
</section>
