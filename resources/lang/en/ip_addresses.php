<?php

return [
    'navigation_label' => 'IP Addresses',
    'model_label' => 'IP Address',
    'plural_model_label' => 'IP Addresses',

    'sections' => [
        'ip_information' => 'IP Address Information',
        'network_config' => 'Network Configuration',
        'description' => 'Description',
    ],

    'fields' => [
        'group' => 'IP Group',
        'generate_mode' => 'Creation Mode',
        'ip_address' => 'IP Address',
        'subnet_input' => 'Subnet (CIDR)',
        'subnet_start' => 'Start Index (optional)',
        'subnet_count' => 'Count (optional)',
        'version' => 'IP Version',
        'status' => 'Status',
        'subnet' => 'Subnet',
        'gateway' => 'Gateway',
        'description' => 'Description',
    ],

    'generate_modes' => [
        'single' => 'Single IP Address',
        'subnet' => 'Generate Entire Subnet',
    ],

    'statuses' => [
        'available' => 'Available',
        'assigned' => 'Assigned',
        'reserved' => 'Reserved',
    ],

    'placeholders' => [
        'subnet_start' => 'e.g. 1',
        'subnet_count' => 'e.g. 10',
    ],

    'helpers' => [
        'subnet_start' => 'First IP in range (default: 1)',
        'subnet_count' => 'How many IPs to generate from start? (default: all)',
        'status' => 'Available: free IP, can be assigned. Assigned: currently assigned to a device and in use. Reserved: blocked for special purpose; do not use for automatic allocation.',
    ],

    'table' => [
        'ip_address' => 'IP Address',
        'ip_copied' => 'IP address copied',
        'version' => 'Version',
        'group' => 'IP Group',
        'no_group' => 'No Group',
        'subnet' => 'Subnet',
        'gateway' => 'Gateway',
        'status' => 'Status',
        'assigned_devices' => 'Assigned Devices',
        'description' => 'Description',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],

    'filters' => [
        'group' => 'IP Group',
        'version' => 'IP Version',
        'status' => 'Status',
    ],

    'bulk_actions' => [
        'edit_gateway' => 'Change Gateway',
        'new_gateway' => 'New Gateway',
        'edit_group' => 'Change IP Group',
        'new_group' => 'New IP Group',
        'select_group' => 'Select group (or leave empty)',
        'edit_subnet' => 'Change Subnet',
        'new_subnet' => 'New Subnet',
        'edit_status' => 'Change Status',
        'new_status' => 'New Status',
        'select_status' => 'Select status',
        'edit_description' => 'Change Description',
        'action' => 'Action',
        'replace' => 'Replace',
        'append' => 'Append',
        'prepend' => 'Prepend',
        'clear' => 'Clear',
        'description_placeholder' => 'Enter new description...',
        'advanced_edit' => 'Advanced Edit',
        'network_settings' => 'Network Settings',
        'gateway_optional' => 'Gateway (optional)',
        'subnet_optional' => 'Subnet (optional)',
        'assignment' => 'Assignment',
        'group_optional' => 'IP Group (optional)',
        'status_optional' => 'Status (optional)',
        'leave_unchanged' => 'Leave unchanged',
        'description_optional' => 'Description (optional)',
        'description_no_change' => 'New description (leave empty for no change)',
    ],

    'relation_managers' => [
        'devices' => [
            'title' => 'Assigned Devices',
            'fields' => [
                'device' => 'Device',
                'is_primary' => 'Primary IP for this device',
            ],
            'table' => [
                'name' => 'Device Name',
                'hostname' => 'Hostname',
                'type' => 'Type',
                'primary_ip' => 'Primary IP',
                'status' => 'Status',
            ],
            'filters' => [
                'type' => 'Device Type',
            ],
            'actions' => [
                'select_device' => 'Select Device',
            ],
        ],
    ],
];
