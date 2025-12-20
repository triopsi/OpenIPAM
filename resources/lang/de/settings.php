<?php

return [
    'navigation_label' => 'Einstellungen',
    'model_label' => 'Einstellung',
    'plural_model_label' => 'Einstellungen',

    'fields' => [
        'key' => 'Schlüssel',
        'label' => 'Beschriftung',
        'description' => 'Beschreibung',
        'group' => 'Gruppe',
        'type' => 'Typ',
        'value' => 'Wert',
        'value_json' => 'Wert (JSON)',
    ],

    'groups' => [
        'general' => 'Allgemein',
        'ipam' => 'IPAM',
        'devices' => 'Geräte',
        'notifications' => 'Benachrichtigungen',
    ],

    'types' => [
        'string' => 'Text',
        'boolean' => 'Boolean',
        'integer' => 'Zahl',
        'json' => 'JSON',
    ],

    'table' => [
        'label' => 'Beschriftung',
        'key' => 'Schlüssel',
        'group' => 'Gruppe',
        'type' => 'Typ',
        'value' => 'Wert',
        'updated_at' => 'Aktualisiert',
    ],

    'filters' => [
        'group' => 'Gruppe',
    ],

    'ipam' => [
        'title' => 'IPAM Einstellungen',
        'navigation_label' => 'IPAM Einstellungen',
        'section_title' => 'IP-Adress Verwaltung',
        'section_description' => 'Konfigurieren Sie die Standardeinstellungen für die IP-Adress Verwaltung',
        'no_specific_group' => 'Keine spezielle Gruppe',

        'fields' => [
            'default_ip_group' => 'Standard IP-Adress Gruppe',
            'default_ip_group_description' => 'Diese Gruppe wird beim Erstellen neuer Geräte als Standard für die IP-Zuordnung verwendet',
            'auto_assign_primary_ip' => 'Automatische primäre IP-Zuweisung',
            'auto_assign_primary_ip_description' => 'Beim Erstellen neuer Geräte wird automatisch die nächste freie IP-Adresse als primär zugewiesen',
            'reserve_network_broadcast' => 'Netzwerk- und Broadcast-Adressen reservieren',
            'reserve_network_broadcast_description' => 'Die erste und letzte IP-Adresse eines Subnetzes werden automatisch als reserviert markiert',
        ],

        'actions' => [
            'save' => 'Einstellungen speichern',
        ],

        'notifications' => [
            'saved' => 'Einstellungen gespeichert',
        ],
    ],
];
