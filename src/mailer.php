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

// Envoie un e-mail texte : par SMTP si smtp_host est configuré, sinon via mail().
function send_mail(string $to, string $subject, string $body): bool
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
        return mail($to, $subject, $body, $headers, '-f' . $from);
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

    try {
        smtp_send($from, $to, $message);
        return true;
    } catch (RuntimeException $ex) {
        error_log('Envoi SMTP vers ' . $to . ' impossible : ' . $ex->getMessage());
        return false;
    }
}

function smtp_send(string $from, string $to, string $message): void
{
    $host   = config('smtp_host');
    $port   = (int) config('smtp_port');
    $secure = config('smtp_secure'); // ssl | tls (STARTTLS) | none

    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . "{$host}:{$port}", $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("connexion à {$host}:{$port} impossible ({$errstr})");
    }
    stream_set_timeout($fp, 15);

    try {
        $helo = parse_url(config('app_url'), PHP_URL_HOST) ?: 'localhost';
        smtp_expect($fp, 220);
        smtp_command($fp, "EHLO {$helo}", 250);
        if ($secure === 'tls') {
            smtp_command($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('échec de la négociation STARTTLS');
            }
            smtp_command($fp, "EHLO {$helo}", 250);
        }
        if (config('smtp_user')) {
            smtp_command($fp, 'AUTH LOGIN', 334);
            smtp_command($fp, base64_encode(config('smtp_user')), 334);
            smtp_command($fp, base64_encode(config('smtp_pass')), 235);
        }
        smtp_command($fp, "MAIL FROM:<{$from}>", 250);
        smtp_command($fp, "RCPT TO:<{$to}>", [250, 251]);
        smtp_command($fp, 'DATA', 354);
        smtp_command($fp, $message . "\r\n.", 250);
        fwrite($fp, "QUIT\r\n");
    } finally {
        fclose($fp);
    }
}

function smtp_command($fp, string $command, int|array $expected): string
{
    fwrite($fp, $command . "\r\n");
    return smtp_expect($fp, $expected);
}

// Lit une réponse SMTP (éventuellement multiligne) et vérifie son code.
function smtp_expect($fp, int|array $expected): string
{
    $response = '';
    while (($line = fgets($fp, 515)) !== false) {
        $response .= $line;
        if (!isset($line[3]) || $line[3] === ' ') {
            break;
        }
    }
    if (!in_array((int) substr($response, 0, 3), (array) $expected, true)) {
        throw new RuntimeException('réponse inattendue du serveur : ' . trim($response));
    }
    return $response;
}
