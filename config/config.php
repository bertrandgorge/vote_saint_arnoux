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
    // Durée de validité des liens de connexion
    'email_link_ttl_hours' => 48,
    'admin_link_ttl_days'  => 30,
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
