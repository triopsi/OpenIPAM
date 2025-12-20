<?php

return [
    'navigation_label' => 'Users',
    'model_label' => 'User',
    'plural_model_label' => 'Users',

    'sections' => [
        'user_information' => 'User Information',
        'password' => 'Password',
        'additional_information' => 'Additional Information',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'gravatar_type' => 'Gravatar Type',
        'password' => 'Password',
        'two_factor_auth' => 'Two-Factor Authentication',
    ],

    'gravatar_types' => [
        'mystery_person' => 'Mystery Person',
        'identicon' => 'Identicon',
        'monsterid' => 'MonsterID',
        'wavatar' => 'Wavatar',
        'retro' => 'Retro',
        'robohash' => 'RoboHash',
        'blank' => 'Blank',
    ],

    'helpers' => [
        'password_keep_current' => 'Leave blank to keep the current password.',
        'password_minimum' => 'Minimum 8 characters recommended.',
        'two_factor_help' => 'Enable/disable email-based 2FA',
    ],

    'table' => [
        'avatar' => 'Avatar',
        'name' => 'Name',
        'email' => 'Email',
        'email_copied' => 'Email copied',
        'two_factor' => '2FA',
        'two_factor_enabled' => '2FA enabled',
        'two_factor_disabled' => '2FA not enabled',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],

    'filters' => [
        'two_factor_only' => 'Only with 2FA',
    ],
];
