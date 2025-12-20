<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | This array contains all supported languages for the application.
    | Each language should have a locale code, display name, flag emoji,
    | and flag icon for consistent usage across the application.
    |
    */

    'supported' => [
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag_emoji' => '🇺🇸',
            'flag_icon' => 'flag-icon-us',
            'rtl' => false,
        ],
        'de' => [
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag_emoji' => '🇩🇪',
            'flag_icon' => 'flag-icon-de',
            'rtl' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | This is the default language that will be used when no other
    | language preference is found.
    |
    */

    'default' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Fallback Language
    |--------------------------------------------------------------------------
    |
    | This language will be used as fallback when translations
    | are missing for the current language.
    |
    */

    'fallback' => 'en',
];
