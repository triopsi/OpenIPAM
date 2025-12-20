<?php

return [
    'csv_import' => [
        'title' => 'CSV Import',
        'description' => 'Geräte mit IP-Adressen aus CSV-Dateien importieren',
        'file_label' => 'CSV-Datei',
        'delimiter_label' => 'Trennzeichen',
        'has_header_label' => 'CSV hat Kopfzeile',
        'preview_label' => 'Vorschau',
        'column_mapping_title' => 'Spalten-Zuordnung',
        'column_mapping_description' => 'Ordnen Sie die CSV-Spalten den Datenbankfeldern zu',
        'duplicate_handling_label' => 'Duplikat-Behandlung',
        'duplicate_handling_description' => 'Was soll passieren, wenn ein Gerät mit dem gleichen Namen bereits existiert?',

        'delimiters' => [
            'comma' => 'Komma (,)',
            'semicolon' => 'Semikolon (;)',
            'tab' => 'Tab',
            'pipe' => 'Pipe (|)',
        ],

        'duplicate_options' => [
            'skip' => 'Überspringen',
            'overwrite' => 'Überschreiben',
            'merge' => 'Zusammenführen (nur leere Felder füllen)',
        ],

        'fields' => [
            'name' => 'Name (Pflichtfeld)',
            'hostname' => 'Hostname',
            'mac_address' => 'MAC-Adresse',
            'type' => 'Typ',
            'location' => 'Standort',
            'status' => 'Status',
            'url' => 'URL',
            'description' => 'Beschreibung',
            'primary_ip' => 'Primäre IP-Adresse',
            'secondary_ips' => 'Sekundäre IP-Adressen',
        ],

        'helper_texts' => [
            'primary_ip' => 'IPv4 oder IPv6 Adresse',
            'secondary_ips' => 'Mehrere IPs getrennt durch Semikolon (;)',
        ],

        'column_options' => [
            'ignore' => '-- Ignorieren --',
            'column_with_name' => 'Spalte: :name',
            'column_with_example' => 'Spalte :number (:example)',
        ],

        'preview_messages' => [
            'upload_file' => 'Laden Sie eine CSV-Datei hoch, um eine Vorschau zu sehen.',
            'invalid_file' => 'Keine gültige Datei ausgewählt.',
            'file_not_found' => 'Datei nicht gefunden. Pfad: :path',
            'empty_file' => 'Die CSV-Datei ist leer.',
            'read_error' => 'Fehler beim Lesen der Datei: :error',
        ],

        'success_messages' => [
            'import_completed' => 'Import abgeschlossen: :imported Geräte importiert',
            'ip_assignments_created' => ':count IP-Zuordnungen erstellt',
            'items_skipped' => ':count übersprungen',
            'errors_occurred' => ':count Fehler',
        ],

        'error_messages' => [
            'import_failed' => 'Fehler beim Importieren: :error',
            'no_valid_file' => 'Keine gültige CSV-Datei ausgewählt.',
        ],

        'notifications' => [
            'success_title' => 'CSV-Import erfolgreich',
            'error_title' => 'Import-Fehler',
        ],
    ],

    'csv_export' => [
        'title' => 'Als CSV exportieren',
        'description' => 'Ausgewählte Geräte in CSV-Datei exportieren',
        'filename_label' => 'Dateiname',
        'default_filename' => 'geraete_export',
        'success_message' => 'CSV-Export erfolgreich abgeschlossen',
    ],

    'device_fields' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC-Adresse',
        'type' => 'Typ',
        'location' => 'Standort',
        'status' => 'Status',
        'url' => 'URL',
        'description' => 'Beschreibung',
        'primary_ip' => 'Primäre IP',
        'secondary_ips' => 'Sekundäre IPs',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],

    'device_types' => [
        'server' => 'Server',
        'router' => 'Router',
        'switch' => 'Switch',
        'firewall' => 'Firewall',
        'access_point' => 'Access Point',
        'printer' => 'Drucker',
        'workstation' => 'Arbeitsplatz',
        'laptop' => 'Laptop',
        'mobile' => 'Mobilgerät',
        'iot' => 'IoT-Gerät',
        'camera' => 'Kamera',
        'phone' => 'Telefon',
        'other' => 'Sonstiges',
    ],

    'device_statuses' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'maintenance' => 'Wartung',
        'decommissioned' => 'Ausgemustert',
        'planned' => 'Geplant',
    ],

    // Device Resource
    'navigation_label' => 'Geräte',
    'model_label' => 'Gerät',
    'plural_model_label' => 'Geräte',

    'sections' => [
        'device_information' => 'Geräteinformationen',
        'details' => 'Details',
        'description' => 'Beschreibung',
        'ip_addresses' => 'IP-Adressen',
    ],

    'fields' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC-Adresse',
        'type' => 'Typ',
        'location' => 'Standort',
        'status' => 'Status',
        'description' => 'Beschreibung',
        'url' => 'URL (Login/Dashboard)',
        'primary_ip' => 'Primäre IP-Adresse',
        'additional_ips' => 'Zusätzliche IP-Adressen',
    ],

    'types' => [
        'server' => 'Server',
        'workstation' => 'Workstation',
        'laptop' => 'Laptop',
        'printer' => 'Drucker',
        'switch' => 'Switch',
        'router' => 'Router',
        'firewall' => 'Firewall',
        'access_point' => 'Access Point',
        'other' => 'Sonstiges',
    ],

    'statuses' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'maintenance' => 'Wartung',
    ],

    'placeholders' => [
        'primary_ip' => 'Automatisch zuweisen oder manuell auswählen',
    ],

    'helpers' => [
        'url' => 'URL zum Login, Dashboard oder Verwaltungsinterface',
    ],

    'table' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC-Adresse',
        'type' => 'Typ',
        'location' => 'Standort',
        'status' => 'Status',
        'ip_addresses' => 'IP-Adressen',
        'url' => 'URL',
        'no_url' => 'Keine URL',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],

    'filters' => [
        'type' => 'Typ',
        'status' => 'Status',
    ],

    'bulk_actions' => [
        'csv_export' => 'Als CSV exportieren',
        'filename_prefix' => 'geraete_export_',
        'csv_modal_heading' => 'Geräte als CSV exportieren',
        'csv_modal_description' => 'Möchten Sie die ausgewählten Geräte als CSV-Datei exportieren?',
        'csv_export_button' => 'Exportieren',
    ],

    'csv' => [
        'headers' => [
            'name' => 'Name',
            'hostname' => 'Hostname',
            'mac_address' => 'MAC-Adresse',
            'type' => 'Typ',
            'location' => 'Standort',
            'status' => 'Status',
            'url' => 'URL',
            'description' => 'Beschreibung',
            'ip_addresses' => 'IP-Adressen',
            'primary_ip' => 'Primäre IP',
            'created_at' => 'Erstellt am',
            'updated_at' => 'Aktualisiert am',
        ],
    ],

    'relation_managers' => [
        'ip_addresses' => [
            'title' => 'IP-Adressen',
            'fields' => [
                'ip_address' => 'IP-Adresse',
                'is_primary' => 'Primäre IP',
                'is_primary_full' => 'Primäre IP-Adresse',
            ],
            'table' => [
                'ip_address' => 'IP-Adresse',
                'version' => 'Version',
                'subnet' => 'Subnetz',
                'primary' => 'Primär',
                'status' => 'Status',
            ],
            'filters' => [
                'version' => 'IP-Version',
            ],
            'actions' => [
                'select_ip' => 'IP-Adresse auswählen',
            ],
        ],
    ],
];
