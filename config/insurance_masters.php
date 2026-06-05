<?php

return [
    'policy_plans' => [
        '4w' => [
            [
                'name' => 'Package Policy (1 Year OD + 1 Year TP)',
                'pol_plan_id' => '02',
                'pol_plan_variant' => 'PackagePolicy',
                'product_code' => '3184',
                'business_type' => 'Roll Over'
            ],
            [
                'name' => 'Package Policy (1 Year OD + 3 Years TP)',
                'pol_plan_id' => '04',
                'pol_plan_variant' => 'PackagePolicy',
                'product_code' => '3184',
                'business_type' => 'New Business'
            ],
            [
                'name' => 'Standalone OD (1 Year OD)',
                'pol_plan_id' => '05',
                'pol_plan_variant' => 'Standalone OD',
                'product_code' => '3184',
                'business_type' => 'Roll Over'
            ]
        ],
        '2w' => [
            [
                'name' => 'Package Policy (1 Year OD + 1 Year TP)',
                'pol_tenure' => '2',
                'plan_type' => 'Package (1 year OD + 1 year TP)',
                'product_code' => '3187',
                'business_type' => 'Roll Over'
            ],
            [
                'name' => 'Package Policy (2 Years OD + 2 Years TP)',
                'pol_tenure' => '4',
                'plan_type' => 'Package (2 years OD + 2 years TP)',
                'product_code' => '3187',
                'business_type' => 'Roll Over'
            ],
            [
                'name' => 'Package Policy (3 Years OD + 3 Years TP)',
                'pol_tenure' => '6',
                'plan_type' => 'Package (3 years OD + 3 years TP)',
                'product_code' => '3187',
                'business_type' => 'Roll Over'
            ],
            [
                'name' => 'Package Policy (1 Year OD + 5 Years TP)',
                'pol_tenure' => '7',
                'plan_type' => 'Package (1 year OD + 5 years TP)',
                'product_code' => '3187',
                'business_type' => 'New Business'
            ],
            [
                'name' => 'Standalone OD (1 Year)',
                'pol_tenure' => '1',
                'plan_type' => 'Standalone OD (1 year)',
                'product_code' => '3187',
                'business_type' => 'Roll Over'
            ]
        ]
    ],
    'motor_plans' => [
        '4w' => [
            ['code' => 'P1', 'name' => 'Silver (Basic OD + Glass Repair)'],
            ['code' => 'P2', 'name' => 'Gold (Silver + Loss of Belongings, Hotel, Keys, RSA)'],
            ['code' => 'P3', 'name' => 'Pearl (Gold + Zero Depreciation)'],
            ['code' => 'P4', 'name' => 'Pearl+ (Pearl + Engine Secure + Consumables)'],
            ['code' => 'P5', 'name' => 'Sapphire (Pearl + Consumables + Tyre Secure)'],
            ['code' => 'P6', 'name' => 'Sapphire Plus (Pearl + Engine Secure + Consumables + Tyre Secure)'],
            ['code' => 'P7', 'name' => 'Sapphire++ (Sapphire Plus + Return to Invoice)'],
            ['code' => 'P10', 'name' => 'Platinum (Pearl + Return to Invoice + Engine Secure)'],
            ['code' => 'P11', 'name' => 'Coral (Pearl + Consumables)'],
            ['code' => 'P12', 'name' => 'Pearl++ (Pearl + Return to Invoice + Engine Secure + Consumables)']
        ],
        '2w' => [] // Two-Wheeler does not use pre-packaged Motor Plans
    ],
    'add_ons' => [
        '4w' => [], // Private Car add-ons are bundled into the Motor Plans above
        '2w' => [
            ['code' => 'dep_reimburse', 'name' => 'Zero Depreciation (Depreciation Reimbursement)'],
            ['code' => 'return_invoice', 'name' => 'Return to Invoice (RTI)'],
            ['code' => 'consumbale_expense', 'name' => 'Consumables expenses'],
            ['code' => 'rsa', 'name' => 'Roadside Assistance (RSA)'],
            ['code' => 'add_towing', 'name' => 'Emergency Towing Cover']
        ]
    ]
];
