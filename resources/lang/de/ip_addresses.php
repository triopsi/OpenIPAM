<?php

return [
    'navigation_label' => 'IP-Adressen',
    'model_label' => 'IP-Adresse',
    'plural_model_label' => 'IP-Adressen',

    'sections' => [
        'ip_information' => 'IP-Adressinformationen',
        'network_config' => 'Netzwerkkonfiguration',
        'description' => 'Beschreibung',
    ],

    'fields' => [
        'group' => 'IP-Gruppe',
        'generate_mode' => 'Erstellungsmodus',
        'ip_address' => 'IP-Adresse',
        'subnet_input' => 'Subnetz (CIDR)',
        'subnet_start' => 'Startindex (optional)',
        'subnet_count' => 'Anzahl (optional)',
        'version' => 'IP-Version',
        'status' => 'Status',
        'subnet' => 'Subnetz',
        'gateway' => 'Gateway',
        'description' => 'Beschreibung',
    ],

    'generate_modes' => [
        'single' => 'Einzelne IP-Adresse',
        'subnet' => 'Ganzes Subnetz generieren',
    ],

    'statuses' => [
        'available' => 'Verfügbar',
        'assigned' => 'Zugewiesen',
        'reserved' => 'Reserviert',
    ],

    'placeholders' => [
        'subnet_start' => 'z.B. 1',
        'subnet_count' => 'z.B. 10',
    ],

    'helpers' => [
        'subnet_start' => 'Erste IP im Bereich (Standard: 1)',
        'subnet_count' => 'Wie viele IPs ab Start generieren? (Standard: alle)',
        'status' => 'Verfügbar: freie IP, kann zugewiesen werden. Zugewiesen: aktuell einem Gerät zugewiesen und in Nutzung. Reserviert: für speziellen Zweck blockiert; nicht zur automatischen Vergabe verwenden.',
    ],

    'table' => [
        'ip_address' => 'IP-Adresse',
        'ip_copied' => 'IP-Adresse kopiert',
        'version' => 'Version',
        'group' => 'IP-Gruppe',
        'no_group' => 'Keine Gruppe',
        'subnet' => 'Subnetz',
        'gateway' => 'Gateway',
        'status' => 'Status',
        'assigned_devices' => 'Zugewiesene Geräte',
        'description' => 'Beschreibung',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],

    'filters' => [
        'group' => 'IP-Gruppe',
        'version' => 'IP-Version',
        'status' => 'Status',
    ],

    'bulk_actions' => [
        'edit_gateway' => 'Gateway ändern',
        'new_gateway' => 'Neues Gateway',
        'edit_group' => 'IP-Gruppe ändern',
        'new_group' => 'Neue IP-Gruppe',
        'select_group' => 'Gruppe auswählen (oder leer lassen)',
        'edit_subnet' => 'Subnetz ändern',
        'new_subnet' => 'Neues Subnetz',
        'edit_status' => 'Status ändern',
        'new_status' => 'Neuer Status',
        'select_status' => 'Status auswählen',
        'edit_description' => 'Beschreibung ändern',
        'action' => 'Aktion',
        'replace' => 'Ersetzen',
        'append' => 'Anhängen',
        'prepend' => 'Voranstellen',
        'clear' => 'Leeren',
        'description_placeholder' => 'Neue Beschreibung eingeben...',
        'advanced_edit' => 'Erweiterte Bearbeitung',
        'network_settings' => 'Netzwerk-Einstellungen',
        'gateway_optional' => 'Gateway (optional)',
        'subnet_optional' => 'Subnetz (optional)',
        'assignment' => 'Zuordnung',
        'group_optional' => 'IP-Gruppe (optional)',
        'status_optional' => 'Status (optional)',
        'leave_unchanged' => 'Unverändert lassen',
        'description_optional' => 'Beschreibung (optional)',
        'description_no_change' => 'Neue Beschreibung (leer lassen für keine Änderung)',
    ],

    'relation_managers' => [
        'devices' => [
            'title' => 'Zugewiesene Geräte',
            'fields' => [
                'device' => 'Gerät',
                'is_primary' => 'Primäre IP für dieses Gerät',
            ],
            'table' => [
                'name' => 'Gerätename',
                'hostname' => 'Hostname',
                'type' => 'Typ',
                'primary_ip' => 'Primäre IP',
                'status' => 'Status',
            ],
            'filters' => [
                'type' => 'Gerätetyp',
            ],
            'actions' => [
                'select_device' => 'Gerät auswählen',
            ],
        ],
    ],
];
