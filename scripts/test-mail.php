<?php
// Diagnostic de l'envoi d'e-mails : configuration, DNS, échange SMTP complet.
//
//   php scripts/test-mail.php destinataire@example.org             e-mail de test simple
//   php scripts/test-mail.php destinataire@example.org --connexion  vrai lien de connexion (membre du jury)
//   docker compose exec web php scripts/test-mail.php ...          (dev, visible dans Mailpit)

if (PHP_SAPI !== 'cli') {
    exit('CLI uniquement');
}

require dirname(__DIR__) . '/src/bootstrap.php';

$to = $argv[1] ?? '';
$loginMail = in_array('--connexion', $argv, true);
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage : php scripts/test-mail.php destinataire@example.org [--connexion]\n");
    exit(2);
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

function line(string $label, $value): void
{
    printf("  %-22s %s\n", $label, is_bool($value) ? ($value ? 'oui' : 'non') : (string) $value);
}

// ---------------------------------------------------------------- Configuration

section('Configuration');
line('Fichier local', is_file(APP_ROOT . '/config/config.local.php') ? 'config/config.local.php' : 'ABSENT (valeurs par défaut / env)');
foreach (['app_url', 'app_env', 'mail_from', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user'] as $key) {
    line($key, var_export(config($key), true));
}
$pass = (string) config('smtp_pass');
line('smtp_pass', $pass === '' ? '(vide)' : strlen($pass) . ' caractères, ' . substr($pass, 0, 2) . str_repeat('*', max(0, strlen($pass) - 2)));
line('Transport', config('smtp_host') ? 'SMTP' : 'fonction mail() de PHP');

$warnings = [];
$from = (string) config('mail_from');
$fromDomain = substr((string) strrchr($from, '@'), 1);
if (str_ends_with($fromDomain, 'example.org')) {
    $warnings[] = 'mail_from est encore une adresse d\'exemple';
}
if (config('smtp_user') && strcasecmp((string) config('smtp_user'), $from) !== 0) {
    $warnings[] = 'smtp_user différent de mail_from : le serveur peut refuser ou le message être classé en spam';
}
$port = (int) config('smtp_port');
if (config('smtp_host') && (($port === 465 && config('smtp_secure') !== 'ssl') || ($port === 587 && config('smtp_secure') !== 'tls'))) {
    $warnings[] = "combinaison port {$port} / smtp_secure '" . config('smtp_secure') . "' inhabituelle (465 = ssl, 587 = tls)";
}

// ---------------------------------------------------------------- Environnement PHP

section('Environnement PHP');
line('Version', PHP_VERSION . ' (' . PHP_OS . ')');
line('Extension openssl', extension_loaded('openssl') ? (OPENSSL_VERSION_TEXT ?? 'oui') : 'NON CHARGÉE');
line('openssl.cafile', ini_get('openssl.cafile') ?: '(défaut)');
line('sendmail_path', ini_get('sendmail_path') ?: '(vide)');
line('disable_functions', ini_get('disable_functions') ?: '(aucune)');
line('Journal d\'erreurs', ini_get('error_log'));
line('Fuseau horaire', date_default_timezone_get() . ' — ' . date('r'));

// ---------------------------------------------------------------- Destinataire

section('Destinataire');
$member = null;
try {
    $member = db_one('SELECT * FROM members WHERE LOWER(email) = ?', [mb_strtolower($to)]);
    line('Membre du jury', $member ? "oui : {$member['name']} (#{$member['id']}, {$member['role']})" : 'NON');
    if (!$member) {
        $warnings[] = "{$to} n'est pas dans la table members : la page de connexion affiche « Vérifiez votre messagerie » mais n'envoie rien";
        $similar = db_all('SELECT email FROM members WHERE email LIKE ?', ['%' . substr($to, 0, (int) strpos($to, '@')) . '%']);
        if ($similar) {
            line('Adresses proches', implode(', ', array_column($similar, 'email')));
        }
    }
} catch (Throwable $ex) {
    line('Base de données', 'ERREUR : ' . $ex->getMessage());
}
if ($loginMail && !$member) {
    fwrite(STDERR, "\n--connexion demande un membre du jury existant.\n");
    exit(1);
}

// ---------------------------------------------------------------- DNS

section('DNS');
if (config('smtp_host')) {
    $ips = gethostbynamel((string) config('smtp_host'));
    line('smtp_host', $ips ? implode(', ', $ips) : 'NE SE RÉSOUT PAS');
    if (!$ips) {
        $warnings[] = 'smtp_host introuvable dans le DNS';
    }
}
if ($fromDomain) {
    $mx = @dns_get_record($fromDomain, DNS_MX) ?: [];
    line("MX {$fromDomain}", $mx ? implode(', ', array_map(fn($r) => "{$r['target']} ({$r['pri']})", $mx)) : '(aucun)');
    $spf = array_filter(array_column(@dns_get_record($fromDomain, DNS_TXT) ?: [], 'txt'), fn($t) => str_starts_with($t, 'v=spf1'));
    line('SPF', $spf ? implode(' | ', $spf) : '(aucun) — risque de spam / rejet');
    $dmarc = array_column(@dns_get_record("_dmarc.{$fromDomain}", DNS_TXT) ?: [], 'txt');
    line('DMARC', $dmarc ? implode(' | ', $dmarc) : '(aucun)');
    $dkim = array_column(@dns_get_record("default._domainkey.{$fromDomain}", DNS_TXT) ?: [], 'txt');
    line('DKIM (default)', $dkim ? substr(implode('', $dkim), 0, 60) . '…' : '(aucun sélecteur « default »)');
}

// ---------------------------------------------------------------- Envoi

section('Envoi à ' . $to . ($loginMail ? ' (lien de connexion)' : ' (message de test)'));
$GLOBALS['smtp_trace'] = function (string $line): void {
    echo '  ' . date('H:i:s') . ' ' . $line . "\n";
};
error_clear_last();
$start = microtime(true);

if ($loginMail) {
    $ok = send_login_email($member, create_login_link((int) $member['id'], config('email_link_ttl_hours') * 3600));
} else {
    $ok = send_mail($to, 'Test d\'envoi — ' . config('app_name'),
        "Ceci est un message de test envoyé le " . date('d/m/Y à H:i:s') . " par scripts/test-mail.php.\n\n"
        . "Serveur : " . (config('smtp_host') ?: 'mail()') . "\nExpéditeur : {$from}\n");
}

line('Durée', round(microtime(true) - $start, 2) . ' s');
line('Résultat', $ok ? 'ACCEPTÉ par le serveur' : 'ÉCHEC');
if (!$ok && ($err = error_get_last())) {
    line('Dernière erreur PHP', $err['message']);
}

// ---------------------------------------------------------------- Bilan

section('Bilan');
foreach ($warnings as $w) {
    echo "  ! {$w}\n";
}
if ($ok) {
    echo "  Le serveur a accepté le message. S'il n'arrive pas : vérifier les spams, puis le suivi de\n"
       . "  livraison dans cPanel (« Suivi de livraison » / Track Delivery) pour voir s'il a été rejeté ensuite.\n";
} else {
    echo "  L'envoi a échoué : voir l'échange ci-dessus et " . ini_get('error_log') . "\n";
}
exit($ok ? 0 : 1);
