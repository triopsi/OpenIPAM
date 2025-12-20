<?php

return [
    'two_factor' => [
        'title' => 'Zwei-Faktor-Authentifizierung',
        'code_sent_title' => 'Code gesendet',
        'code_sent_body' => 'Wir haben einen 6-stelligen Code an Ihre E-Mail gesendet.',
        'code_label' => 'Verifizierungscode',
        'code_helper' => 'Geben Sie den 6-stelligen Code ein, den wir an Ihre E-Mail gesendet haben.',
        'invalid_code_title' => 'Ungültiger Code',
        'invalid_code_body' => 'Der eingegebene Code ist ungültig oder abgelaufen.',
        'code_resent_title' => 'Code erneut gesendet',
        'code_resent_body' => 'Ein neuer Code wurde an Ihre E-Mail gesendet.',
        'verify_button' => 'Verifizieren',
        'resend_code' => 'Code erneut senden',
        'email' => [
            'code_subject' => 'Ihr Zwei-Faktor-Authentifizierungscode',
            'disable_subject' => 'Code zur Deaktivierung der Zwei-Faktor-Authentifizierung',
            'greeting' => 'Hallo :name!',
            'login_line1' => 'Sie haben versucht, sich in Ihr Konto einzuloggen.',
            'login_line2' => 'Um die Anmeldung abzuschließen, verwenden Sie bitte den folgenden Code:',
            'disable_line1' => 'Sie haben die Deaktivierung der Zwei-Faktor-Authentifizierung angefordert.',
            'disable_line2' => 'Um die Zwei-Faktor-Authentifizierung zu deaktivieren, verwenden Sie bitte den folgenden Code:',
            'validity' => 'Dieser Code ist 10 Minuten gültig.',
            'ignore_warning' => 'Wenn Sie diese Aktion nicht angefordert haben, ignorieren Sie bitte diese E-Mail.',
        ],
    ],
];
