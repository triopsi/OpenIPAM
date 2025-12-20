<?php

return [
    'csv_import' => [
        'title' => 'CSV Import',
        'description' => 'Import devices with IP addresses from CSV files',
        'file_label' => 'CSV File',
        'delimiter_label' => 'Delimiter',
        'has_header_label' => 'CSV has header row',
        'preview_label' => 'Preview',
        'column_mapping_title' => 'Column Mapping',
        'column_mapping_description' => 'Map CSV columns to database fields',
        'duplicate_handling_label' => 'Duplicate Handling',
        'duplicate_handling_description' => 'What should happen when a device with the same name already exists?',

        'delimiters' => [
            'comma' => 'Comma (,)',
            'semicolon' => 'Semicolon (;)',
            'tab' => 'Tab',
            'pipe' => 'Pipe (|)',
        ],

        'duplicate_options' => [
            'skip' => 'Skip',
            'overwrite' => 'Overwrite',
            'merge' => 'Merge (only fill empty fields)',
        ],

        'fields' => [
            'name' => 'Name (Required)',
            'hostname' => 'Hostname',
            'mac_address' => 'MAC Address',
            'type' => 'Type',
            'location' => 'Location',
            'status' => 'Status',
            'url' => 'URL',
            'description' => 'Description',
            'primary_ip' => 'Primary IP Address',
            'secondary_ips' => 'Secondary IP Addresses',
        ],

        'helper_texts' => [
            'primary_ip' => 'IPv4 or IPv6 address',
            'secondary_ips' => 'Multiple IPs separated by semicolon (;)',
        ],

        'column_options' => [
            'ignore' => '-- Ignore --',
            'column_with_name' => 'Column: :name',
            'column_with_example' => 'Column :number (:example)',
        ],

        'preview_messages' => [
            'upload_file' => 'Upload a CSV file to see a preview.',
            'invalid_file' => 'No valid file selected.',
            'file_not_found' => 'File not found. Path: :path',
            'empty_file' => 'The CSV file is empty.',
            'read_error' => 'Error reading file: :error',
        ],

        'success_messages' => [
            'import_completed' => 'Import completed: :imported devices imported',
            'ip_assignments_created' => ':count IP assignments created',
            'items_skipped' => ':count skipped',
            'errors_occurred' => ':count errors',
        ],

        'error_messages' => [
            'import_failed' => 'Import failed: :error',
            'no_valid_file' => 'No valid CSV file selected.',
        ],

        'notifications' => [
            'success_title' => 'CSV Import Successful',
            'error_title' => 'Import Error',
        ],
    ],

    'csv_export' => [
        'title' => 'Export as CSV',
        'description' => 'Export selected devices to CSV file',
        'filename_label' => 'Filename',
        'default_filename' => 'devices_export',
        'success_message' => 'CSV export completed successfully',
    ],

    'device_fields' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC Address',
        'type' => 'Type',
        'location' => 'Location',
        'status' => 'Status',
        'url' => 'URL',
        'description' => 'Description',
        'primary_ip' => 'Primary IP',
        'secondary_ips' => 'Secondary IPs',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    'device_types' => [
        'server' => 'Server',
        'router' => 'Router',
        'switch' => 'Switch',
        'firewall' => 'Firewall',
        'access_point' => 'Access Point',
        'printer' => 'Printer',
        'workstation' => 'Workstation',
        'laptop' => 'Laptop',
        'mobile' => 'Mobile Device',
        'iot' => 'IoT Device',
        'camera' => 'Camera',
        'phone' => 'Phone',
        'other' => 'Other',
    ],

    'device_statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'maintenance' => 'Maintenance',
        'decommissioned' => 'Decommissioned',
        'planned' => 'Planned',
    ],

    // Device Resource
    'navigation_label' => 'Devices',
    'model_label' => 'Device',
    'plural_model_label' => 'Devices',

    'sections' => [
        'device_information' => 'Device Information',
        'details' => 'Details',
        'description' => 'Description',
        'ip_addresses' => 'IP Addresses',
    ],

    'fields' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC Address',
        'type' => 'Type',
        'location' => 'Location',
        'status' => 'Status',
        'description' => 'Description',
        'url' => 'URL (Login/Dashboard)',
        'primary_ip' => 'Primary IP Address',
        'additional_ips' => 'Additional IP Addresses',
    ],

    'types' => [
        'server' => 'Server',
        'workstation' => 'Workstation',
        'laptop' => 'Laptop',
        'printer' => 'Printer',
        'switch' => 'Switch',
        'router' => 'Router',
        'firewall' => 'Firewall',
        'access_point' => 'Access Point',
        'other' => 'Other',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'maintenance' => 'Maintenance',
    ],

    'placeholders' => [
        'primary_ip' => 'Auto-assign or manually select',
    ],

    'helpers' => [
        'url' => 'URL for login, dashboard, or management interface',
    ],

    'table' => [
        'name' => 'Name',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC Address',
        'type' => 'Type',
        'location' => 'Location',
        'status' => 'Status',
        'ip_addresses' => 'IP Addresses',
        'url' => 'URL',
        'no_url' => 'No URL',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],

    'filters' => [
        'type' => 'Type',
        'status' => 'Status',
    ],

    'bulk_actions' => [
        'csv_export' => 'Export as CSV',
        'filename_prefix' => 'devices_export_',
        'csv_modal_heading' => 'Export Devices as CSV',
        'csv_modal_description' => 'Do you want to export the selected devices as CSV file?',
        'csv_export_button' => 'Export',
    ],

    'csv' => [
        'headers' => [
            'name' => 'Name',
            'hostname' => 'Hostname',
            'mac_address' => 'MAC Address',
            'type' => 'Type',
            'location' => 'Location',
            'status' => 'Status',
            'url' => 'URL',
            'description' => 'Description',
            'ip_addresses' => 'IP Addresses',
            'primary_ip' => 'Primary IP',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ],
    ],

    'relation_managers' => [
        'ip_addresses' => [
            'title' => 'IP Addresses',
            'fields' => [
                'ip_address' => 'IP Address',
                'is_primary' => 'Primary IP',
                'is_primary_full' => 'Primary IP Address',
            ],
            'table' => [
                'ip_address' => 'IP Address',
                'version' => 'Version',
                'subnet' => 'Subnet',
                'primary' => 'Primary',
                'status' => 'Status',
            ],
            'filters' => [
                'version' => 'IP Version',
            ],
            'actions' => [
                'select_ip' => 'Select IP Address',
            ],
        ],
    ],
];
