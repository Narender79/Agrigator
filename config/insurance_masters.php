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
    ],
    'business_types' => [
        ['code' => '01', 'name' => 'New Business'],
        ['code' => '03', 'name' => 'Roll Over'],
        ['code' => '04', 'name' => 'Used Vehicle']
    ],
    'prev_policy_types' => [
        ['code' => 'Package', 'name' => 'Package'],
        ['code' => 'Liability', 'name' => 'Liability']
    ],
    'prev_policy_ncbs' => ['0', '20', '25', '35', '45', '50', '55'],
    'voluntary_amounts' => ['2500', '5000', '7500', '15000'],
    // 'nominee_relationships' => [
    //     'Aunt', 'Brother', 'Brother In Law', 'Chauffeur', 'Daughter', 'Daughter In Law', 
    //     'Employee', 'Employer', 'Father', 'Father In Law', 'Fiancé', 'Friend', 
    //     'Granddaughter', 'Grandfather', 'Grandmother', 'Grandson', 'Husband', 'Insured', 
    //     'Mother', 'Mother In Law', 'Nephew', 'Niece', 'RELATIVE', 'Relative', 'Self', 
    //     'Sister', 'Sister In Law', 'Son', 'Son In Law', 'Spouse', 'Uncle', 'Wife', 'OTHERS'
    // ],
    // 'appointee_relationships' => ['Daughter', 'Father', 'Mother', 'Son', 'Spouse'],
    'financier_types' => [
        ['code' => 'Hire Purchase', 'name' => 'Hire Purchase'],
        ['code' => 'Hypothecation', 'name' => 'Hypothecation'],
        ['code' => 'Lease agreement', 'name' => 'Lease agreement']
    ],
    'tyre_secure_options' => [
        ['code' => 'DEPRECIATION BASIS', 'name' => 'DEPRECIATION BASIS'],
        ['code' => 'REPLACEMENT BASIS', 'name' => 'REPLACEMENT BASIS']
    ],
    'engine_secure_options' => [
        ['code' => 'WITH DEDUCTIBLE', 'name' => 'WITH DEDUCTIBLE'],
        ['code' => 'WITHOUT DEDUCTIBLE', 'name' => 'WITHOUT DEDUCTIBLE']
    ],
    'owner_driver_declarations' => [
        ['code' => 'None', 'name' => 'None'],
        ['code' => 'No valid driving license', 'name' => 'No valid driving license'],
        ['code' => 'Other motor policy with CPA', 'name' => 'Other motor policy with CPA'],
        ['code' => 'Have standalone CPA >= 15 L', 'name' => 'Have standalone CPA >= 15 L']
    ],
    'occupations' => [
        ['code' => '1', 'name' => 'ACCOUNTANTS'],
        ['code' => '182', 'name' => 'ARCHITECT'],
        ['code' => '183', 'name' => 'AUDITOR'],
        ['code' => '2', 'name' => 'BANKERS'],
        ['code' => '3', 'name' => 'BLUE COLLAR(LABORER)'],
        ['code' => '4', 'name' => 'BUILDERS'],
        ['code' => '5', 'name' => 'CIVIL SERVANTS'],
        ['code' => '6', 'name' => 'CONTRACTORS'],
        ['code' => '181', 'name' => 'DOCTORS'],
        ['code' => '9', 'name' => 'ENGINEER'],
        ['code' => '18', 'name' => 'EXPORTERS'],
        ['code' => '10', 'name' => 'HIGH TENSION ELECTRICAL INSTALLATION'],
        ['code' => '21', 'name' => 'INDUSTRIALIST'],
        ['code' => '22', 'name' => 'JOCKEYS'],
        ['code' => '184', 'name' => 'LAWYER'],
        ['code' => '15', 'name' => 'MACHINE OPERATORS'],
        ['code' => '11', 'name' => 'MERCHANT EXPORTER'],
        ['code' => '23', 'name' => 'OTHER'],
        ['code' => '14', 'name' => 'PAMPHLET WRITERS AND DISTRIBUTORS'],
        ['code' => '185', 'name' => 'PROFESSIONAL'],
        ['code' => '16', 'name' => 'PUBLIC SERVANTS'],
        ['code' => '20', 'name' => 'RACING'],
        ['code' => '13', 'name' => 'SELF EMPLOYED'],
        ['code' => '12', 'name' => 'SERVICE'],
        ['code' => '134', 'name' => 'SOCIAL SERVICE'],
        ['code' => '126', 'name' => 'SYSTEMS ANALYST'],
        ['code' => '19', 'name' => 'TEACHER'],
        ['code' => '17', 'name' => 'UNDERGROUND MINES WORKER'],
        ['code' => '24', 'name' => 'UNEMPLOYED'],
        ['code' => '7', 'name' => 'VETERINARY DOCTORS'],
        ['code' => '8', 'name' => 'WOOD WORKING MACHINISTS']
    ]
];
