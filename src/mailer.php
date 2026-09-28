<?php

function send_login_email(array $member, string $link): bool
{
    $subject = 'Votre lien de connexion — ' . config('app_name');
    $body = "Bonjour {$member['name']},\n\n"
        . "Voici votre lien personnel pour accéder à l'outil de notation des architectes :\n\n"
        . $link . "\n\n"
        . "Ce lien est valable " . config('email_link_ttl_hours') . " heures. Une fois connecté, "
        . "vous resterez connecté sur cet appareil.\n\n"
        . "Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.\n";

    return send_mail($member['email'], $subject, $body);
}

// Envoie un e-mail texte et consigne l'échange complet dans logs/mail.log.
function send_mail(string $to, string $subject, string $body): bool
{
    $lines = [];
    $previous = $GLOBALS['smtp_trace'] ?? null;
    $GLOBALS['smtp_trace'] = function (string $line) use (&$lines, $previous): void {
        $lines[] = date('H:i:s') . ' ' . $line;
        if ($previous) {
            $previous($line);
        }
    };
    try {
        $ok = send_mail_now($to, $subject, $body);
    } finally {
        $GLOBALS['smtp_trace'] = $previous;
    }
    $origin = PHP_SAPI === 'cli' ? 'cli' : PHP_SAPI . ' ' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' ' . ($_SERVER['REQUEST_URI'] ?? '');
    @file_put_contents(
        APP_ROOT . '/logs/mail.log',
        '[' . date('Y-m-d H:i:s') . "] {$to} ({$origin}) : " . ($ok ? 'ACCEPTÉ' : 'ÉCHEC') . "\n  "
            . implode("\n  ", $lines) . "\n\n",
        FILE_APPEND | LOCK_EX
    );
    return $ok;
}

// Par SMTP si smtp_host est configuré, sinon via mail().
function send_mail_now(string $to, string $subject, string $body): bool
{
    $from = config('mail_from');
    $headers = [
        'From'                      => mb_encode_mimeheader(config('app_name'), 'UTF-8') . " <{$from}>",
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => 'quoted-printable',
    ];
    $subject = mb_encode_mimeheader($subject, 'UTF-8');
    $body = quoted_printable_encode(str_replace(["\r\n", "\r", "\n"], "\r\n", $body));

    if (!config('smtp_host')) {
        smtp_trace('* envoi via mail() : ' . ini_get('sendmail_path'));
        $ok = mail($to, $subject, $body, $headers, '-f' . $from);
        if (!$ok) {
            error_log('Envoi via mail() vers ' . $to . ' impossible : ' . (error_get_last()['message'] ?? 'raison inconnue'));
        }
        return $ok;
    }

    $headers = [
        'Date'       => date('r'),
        'To'         => "<{$to}>",
        'Subject'    => $subject,
        'Message-ID' => '<' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($from, '@'), 1) . '>',
    ] + $headers;
    $message = '';
    foreach ($headers as $name => $value) {
        $message .= "{$name}: {$value}\r\n";
    }
    $message .= "\r\n" . preg_replace('/^\./m', '..', $body);
    smtp_trace("* Message-ID {$headers['Message-ID']}, expéditeur {$from}");

    try {
        smtp_send($from, $to, $message);
        return true;
    } catch (RuntimeException $ex) {
        smtp_trace('! ' . $ex->getMessage());
        error_log('Envoi SMTP vers ' . $to . ' impossible : ' . $ex->getMessage());
        return false;
    }
}

function smtp_send(string $from, string $to, string $message): void
{
    $host   = config('smtp_host');
    $port   = (int) config('smtp_port');
    $secure = config('smtp_secure'); // ssl | tls (STARTTLS) | none

    $target = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . "{$host}:{$port}";
    smtp_trace("* connexion à {$target}");
    // Les erreurs TLS (certificat…) ne sont données que sous forme d'avertissements : on les collecte
    $warnings = [];
    set_error_handler(function (int $no, string $msg) use (&$warnings): bool {
        $warnings[] = $msg;
        return true;
    });
    try {
        $fp = stream_socket_client($target, $errno, $errstr, 15);
    } finally {
        restore_error_handler();
    }
    if (!$fp) {
        throw new RuntimeException("connexion à {$host}:{$port} impossible ({$errno} {$errstr}) " . implode(' / ', $warnings));
    }
    stream_set_timeout($fp, 15);
    smtp_trace_crypto($fp);

    try {
        $helo = parse_url(config('app_url'), PHP_URL_HOST) ?: 'localhost';
        smtp_expect($fp, 220);
        smtp_command($fp, "EHLO {$helo}", 250);
        if ($secure === 'tls') {
            smtp_command($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('échec de la négociation STARTTLS (' . (error_get_last()['message'] ?? '') . ')');
            }
            smtp_trace_crypto($fp);
            smtp_command($fp, "EHLO {$helo}", 250);
        }
        if (config('smtp_user')) {
            smtp_command($fp, 'AUTH LOGIN', 334);
            smtp_command($fp, base64_encode(config('smtp_user')), 334, '<utilisateur : ' . config('smtp_user') . '>');
            smtp_command($fp, base64_encode(config('smtp_pass')), 235, '<mot de passe masqué>');
        }
        smtp_command($fp, "MAIL FROM:<{$from}>", 250);
        smtp_command($fp, "RCPT TO:<{$to}>", [250, 251]);
        smtp_command($fp, 'DATA', 354);
        smtp_command($fp, $message . "\r\n.", 250, '<message de ' . strlen($message) . ' octets>');
        try {
            smtp_command($fp, 'QUIT', 221);
        } catch (RuntimeException) {
            // Le message est déjà accepté : une fin de session incorrecte n'est pas un échec
        }
    } finally {
        fclose($fp);
    }
}

// Journal détaillé de l'échange SMTP, activé en définissant $GLOBALS['smtp_trace'] (callable).
function smtp_trace(string $line): void
{
    if (isset($GLOBALS['smtp_trace'])) {
        ($GLOBALS['smtp_trace'])($line);
    }
}

function smtp_trace_crypto($fp): void
{
    $crypto = stream_get_meta_data($fp)['crypto'] ?? null;
    if ($crypto) {
        smtp_trace("* chiffrement : {$crypto['protocol']} / {$crypto['cipher_name']}");
    }
}

function smtp_command($fp, string $command, int|array $expected, ?string $shown = null): string
{
    smtp_trace('C: ' . ($shown ?? $command));
    fwrite($fp, $command . "\r\n");
    return smtp_expect($fp, $expected);
}

// Lit une réponse SMTP (éventuellement multiligne) et vérifie son code.
function smtp_expect($fp, int|array $expected): string
{
    $response = '';
    while (($line = fgets($fp, 515)) !== false) {
        $response .= $line;
        smtp_trace('S: ' . rtrim($line));
        if (!isset($line[3]) || $line[3] === ' ') {
            break;
        }
    }
    if ($response === '') {
        $timedOut = stream_get_meta_data($fp)['timed_out'] ?? false;
        throw new RuntimeException($timedOut ? 'délai dépassé en attente du serveur' : 'connexion fermée par le serveur');
    }
    if (!in_array((int) substr($response, 0, 3), (array) $expected, true)) {
        throw new RuntimeException('réponse inattendue du serveur : ' . trim($response));
    }
    return $response;
}
