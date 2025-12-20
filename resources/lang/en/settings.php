<?php

return [
    'navigation_label' => 'Settings',
    'model_label' => 'Setting',
    'plural_model_label' => 'Settings',

    'fields' => [
        'key' => 'Key',
        'label' => 'Label',
        'description' => 'Description',
        'group' => 'Group',
        'type' => 'Type',
        'value' => 'Value',
        'value_json' => 'Value (JSON)',
    ],

    'groups' => [
        'general' => 'General',
        'ipam' => 'IPAM',
        'devices' => 'Devices',
        'notifications' => 'Notifications',
    ],

    'types' => [
        'string' => 'Text',
        'boolean' => 'Boolean',
        'integer' => 'Number',
        'json' => 'JSON',
    ],

    'table' => [
        'label' => 'Label',
        'key' => 'Key',
        'group' => 'Group',
        'type' => 'Type',
        'value' => 'Value',
        'updated_at' => 'Updated',
    ],

    'filters' => [
        'group' => 'Group',
    ],

    'ipam' => [
        'title' => 'IPAM Settings',
        'navigation_label' => 'IPAM Settings',
        'section_title' => 'IP Address Management',
        'section_description' => 'Configure default settings for IP address management',
        'no_specific_group' => 'No specific group',

        'fields' => [
            'default_ip_group' => 'Default IP Address Group',
            'default_ip_group_description' => 'This group will be used as default for IP assignment when creating new devices',
            'auto_assign_primary_ip' => 'Automatic primary IP assignment',
            'auto_assign_primary_ip_description' => 'When creating new devices, automatically assign the next free IP address as primary',
            'reserve_network_broadcast' => 'Reserve network and broadcast addresses',
            'reserve_network_broadcast_description' => 'The first and last IP address of a subnet are automatically marked as reserved',
        ],

        'actions' => [
            'save' => 'Save Settings',
        ],

        'notifications' => [
            'saved' => 'Settings saved',
        ],
    ],
];
