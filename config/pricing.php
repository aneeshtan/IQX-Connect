<?php

return [
    'default_plan' => 'free',

    'plans' => [
        'free' => [
            'name' => 'Freemium',
            'price_label' => 'Free',
            'workspace_price_monthly' => 0,
            'included_users' => 3,
            'included_operational_records' => 100,
            'feature_flags' => [
                'advanced_integrations' => false,
                'premium_support' => false,
                'custom_branding' => false,
                'enterprise_security' => false,
            ],
            'highlights' => [
                '1 workspace',
                '3 users included',
                'First 100 operational records included',
                'Core CRM, collaboration, and reports',
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'price_label' => '$149 / workspace / month',
            'workspace_price_monthly' => 149,
            'included_users' => 5,
            'included_operational_records' => 500,
            'feature_flags' => [
                'advanced_integrations' => true,
                'premium_support' => false,
                'custom_branding' => false,
                'enterprise_security' => false,
            ],
            'highlights' => [
                '5 users included',
                '500 operational records per month',
                'Google Sheets, CSV, and standard APIs',
                'Workspace controls and exports',
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'price_label' => '$399 / workspace / month',
            'workspace_price_monthly' => 399,
            'included_users' => 10,
            'included_operational_records' => 1500,
            'feature_flags' => [
                'advanced_integrations' => true,
                'premium_support' => true,
                'custom_branding' => true,
                'enterprise_security' => false,
            ],
            'highlights' => [
                '10 users included',
                '1,500 operational records per month',
                'Advanced workflow, finance, and segmentation',
                'Priority support and migration help',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_label' => 'Custom',
            'workspace_price_monthly' => null,
            'included_users' => null,
            'included_operational_records' => null,
            'feature_flags' => [
                'advanced_integrations' => true,
                'premium_support' => true,
                'custom_branding' => true,
                'enterprise_security' => true,
            ],
            'highlights' => [
                'Custom seats and volume',
                'SSO, SLA, and enterprise controls',
                'Custom integrations and onboarding',
                'Multi-workspace rollout support',
            ],
        ],
    ],

    'usage_metrics' => [
        'freight_forwarding' => [
            'key' => 'shipment_jobs',
            'label' => 'Shipment jobs',
            'description' => 'Executed shipment jobs tracked in the workspace.',
        ],
        'container_conversion' => [
            'key' => 'projects',
            'label' => 'Projects',
            'description' => 'Awarded and active customer projects.',
        ],
        'ship_chandling' => [
            'key' => 'bookings',
            'label' => 'Operational orders',
            'description' => 'Operational supply and fulfillment records.',
        ],
        'shipping_liner' => [
            'key' => 'bookings',
            'label' => 'Bookings',
            'description' => 'Customer bookings managed in the workspace.',
        ],
        'ship_management' => [
            'key' => 'won_opportunities',
            'label' => 'Managed contracts',
            'description' => 'Won management contracts and onboarded commercial records.',
        ],
        'container_trading_leasing' => [
            'key' => 'quotes',
            'label' => 'Commercial deals',
            'description' => 'Quoted and managed trading or leasing deals.',
        ],
        'general_maritime' => [
            'key' => 'won_opportunities',
            'label' => 'Operational records',
            'description' => 'Won commercial records used as the operational benchmark.',
        ],
    ],
];
