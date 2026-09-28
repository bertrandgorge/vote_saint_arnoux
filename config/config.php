<?php
// Configuration par défaut, lue depuis les variables d'environnement (stack Docker).
// En production (O2Switch), copier config.local.php.example en config.local.php et le compléter.

$config = [
    'app_url'   => getenv('APP_URL') ?: 'http://localhost:8080',
    'app_env'   => getenv('APP_ENV') ?: 'prod',
    'app_name'  => 'Sélection des architectes — Saint-Arnoux',
    'db_host'   => getenv('DB_HOST') ?: 'localhost',
    'db_name'   => getenv('DB_NAME') ?: 'vote',
    'db_user'   => getenv('DB_USER') ?: 'vote',
    'db_pass'   => getenv('DB_PASS') ?: '',
    'mail_from' => getenv('MAIL_FROM') ?: 'jury@example.org',
    // SMTP : laisser smtp_host vide pour utiliser la fonction mail() de PHP
    'smtp_host'   => getenv('SMTP_HOST') ?: '',
    'smtp_port'   => (int) (getenv('SMTP_PORT') ?: 465),
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'ssl', // ssl (port 465) | tls = STARTTLS (port 587) | none
    'smtp_user'   => getenv('SMTP_USER') ?: '',
    'smtp_pass'   => getenv('SMTP_PASS') ?: '',
    // Durée de validité des liens de connexion
    'email_link_ttl_hours' => 48,
    'admin_link_ttl_days'  => 30,
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
