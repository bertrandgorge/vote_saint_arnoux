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

    $from = config('mail_from');
    $headers = [
        'From'                      => mb_encode_mimeheader(config('app_name'), 'UTF-8') . " <{$from}>",
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];

    return mail($member['email'], mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers, '-f' . $from);
}
