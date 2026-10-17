<?php

// O Symfony Mailer só aceita os schemes "smtp" (STARTTLS) e "smtps" (TLS implícito).
// Com MAIL_SCHEME=tls todo envio falhava com UnsupportedSchemeException — e o
// .env.example é o que cada ambiente novo copia.
it('.env.example usa um MAIL_SCHEME aceito pelo Symfony Mailer', function () {
    $env = file_get_contents(base_path('.env.example'));
    preg_match('/^MAIL_SCHEME=(.*)$/m', $env, $matches);

    expect($matches)->not->toBeEmpty()
        ->and(trim($matches[1], " \"'"))->toBeIn(['smtp', 'smtps', '']);
});
