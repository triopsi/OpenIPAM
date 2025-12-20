<?php

return [
    'navigation_label' => 'Benutzer',
    'model_label' => 'Benutzer',
    'plural_model_label' => 'Benutzer',

    'sections' => [
        'user_information' => 'Benutzerinformationen',
        'password' => 'Passwort',
        'additional_information' => 'Zusätzliche Informationen',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'gravatar_type' => 'Gravatar-Typ',
        'password' => 'Passwort',
        'two_factor_auth' => 'Zwei-Faktor-Authentifizierung',
    ],

    'gravatar_types' => [
        'mystery_person' => 'Mystery Person',
        'identicon' => 'Identicon',
        'monsterid' => 'MonsterID',
        'wavatar' => 'Wavatar',
        'retro' => 'Retro',
        'robohash' => 'RoboHash',
        'blank' => 'Leer',
    ],

    'helpers' => [
        'password_keep_current' => 'Leer lassen, um das aktuelle Passwort beizubehalten.',
        'password_minimum' => 'Mindestens 8 Zeichen empfohlen.',
        'two_factor_help' => 'E-Mail basierte 2FA aktivieren/deaktivieren',
    ],

    'table' => [
        'avatar' => 'Avatar',
        'name' => 'Name',
        'email' => 'E-Mail',
        'email_copied' => 'E-Mail kopiert',
        'two_factor' => '2FA',
        'two_factor_enabled' => '2FA aktiviert',
        'two_factor_disabled' => '2FA nicht aktiviert',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],

    'filters' => [
        'two_factor_only' => 'Nur mit 2FA',
    ],
];
