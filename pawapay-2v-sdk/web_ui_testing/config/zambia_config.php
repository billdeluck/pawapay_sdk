<?php
/**
 * ============================================================================
 * ZAMBIA-SPECIFIC PAWAPAY CONFIGURATION
 * ============================================================================
 * 
 * This configuration implements the complete Zambia mobile money fee structure
 * based on real PawaPay fees for Zambia operators:
 * - Airtel Money (AIRTEL_OAPI_ZMB) 
 * - MTN Mobile Money (MTN_MOMO_ZMB)
 * - Zamtel Kwacha (ZAMTEL_MOMO_ZMB)
 * 
 * @version 2.0.0
 * @since 2024-10-17
 * ============================================================================
 */

return [
    // ========================================================================
    // FEE ACTIVATION SETTINGS
    // ========================================================================
    'fee_settings' => [
        'enable_fees' => true, // true/false - Master switch for all fee processing
        'apply_to_customer' => true, // true = customer pays fees, false = merchant absorbs
        'fee_display_method' => 'inclusive', // 'inclusive' or 'additional'
        'precision' => 2, // Decimal places for fee calculations
    ],

    // ========================================================================
    // ZAMBIA MOBILE MONEY OPERATORS & FEE PERCENTAGES
    // ========================================================================
    'zambia_operators' => [
        'AIRTEL_OAPI_ZMB' => [
            'name' => 'Airtel Money',
            'display_name' => 'Airtel Money Zambia',
            'currency' => 'ZMW',
            'country_code' => 'ZM',
            'fee_structure' => 'mixed', // fixed + percentage
            'base_fee' => 2.00, // ZMW 2.00 fixed fee
            'percentage_fee' => 0.005, // 0.5% of amount
            'minimum_fee' => 2.00,
            'maximum_fee' => 50.00,
            'test_phones' => [
                'success' => '260973456789',
                'fail_limit' => '260973456019',
                'fail_approval' => '260973456039',
                'fail_balance' => '260973456049',
            ]
        ],
        
        'MTN_MOMO_ZMB' => [
            'name' => 'MTN Mobile Money',
            'display_name' => 'MTN Mobile Money Zambia', 
            'currency' => 'ZMW',
            'country_code' => 'ZM',
            'fee_structure' => 'percentage', // percentage only
            'base_fee' => 0.00,
            'percentage_fee' => 0.01, // 1% of amount
            'minimum_fee' => 1.00,
            'maximum_fee' => 100.00,
            'test_phones' => [
                'success' => '260763456789',
                'fail_limit' => '260763456019',
                'fail_not_found' => '260763456029',
                'fail_approval' => '260763456039',
            ]
        ],
        
        'ZAMTEL_MOMO_ZMB' => [
            'name' => 'Zamtel Kwacha',
            'display_name' => 'Zamtel Kwacha Mobile Money',
            'currency' => 'ZMW',
            'country_code' => 'ZM',
            'fee_structure' => 'tiered', // tiered based on amount
            'tiers' => [
                ['min' => 0, 'max' => 50, 'fixed' => 1.00, 'percentage' => 0],
                ['min' => 50.01, 'max' => 200, 'fixed' => 2.50, 'percentage' => 0],
                ['min' => 200.01, 'max' => 1000, 'fixed' => 0, 'percentage' => 0.015], // 1.5%
                ['min' => 1000.01, 'max' => 999999, 'fixed' => 0, 'percentage' => 0.01], // 1%
            ],
            'test_phones' => [
                'success' => '260953456700',
                'fail_balance' => '260953456704',
                'fail_unspecified' => '260953456712',
            ]
        ]
    ],

    // ========================================================================
    // PAWAPAY GLOBAL SETTINGS
    // ========================================================================
    'pawapay_global' => [
        'global_fee_percentage' => 0.01, // 1% PawaPay platform fee
        'apply_global_fee' => true,
        'currency_code' => 'ZMW',
        'country_code' => 'ZM',
        'default_operator' => 'MTN_MOMO_ZMB',
    ],

    // ========================================================================
    // MEMBERSHIP PLANS FOR TESTING (Modesy-style)
    // ========================================================================
    'membership_plans' => [
        'basic_vendor' => [
            'id' => 'plan_basic_vendor',
            'name' => 'Basic Vendor Plan',
            'description' => 'Start selling with basic features',
            'price' => 25.00, // ZMW 25
            'currency' => 'ZMW',
            'duration' => '1 month',
            'features' => [
                'Up to 10 products',
                'Basic analytics', 
                'Email support',
                'Mobile money payments'
            ]
        ],
        
        'premium_vendor' => [
            'id' => 'plan_premium_vendor', 
            'name' => 'Premium Vendor Plan',
            'description' => 'Advanced selling features and priority support',
            'price' => 75.00, // ZMW 75
            'currency' => 'ZMW',
            'duration' => '1 month',
            'features' => [
                'Unlimited products',
                'Advanced analytics',
                'Priority support',
                'Featured listings',
                'Social media integration'
            ]
        ],
        
        'enterprise_vendor' => [
            'id' => 'plan_enterprise_vendor',
            'name' => 'Enterprise Vendor Plan', 
            'description' => 'Full marketplace access with custom features',
            'price' => 150.00, // ZMW 150
            'currency' => 'ZMW',
            'duration' => '1 month',
            'features' => [
                'Everything in Premium',
                'Custom branding',
                'API access',
                'Bulk operations',
                'Dedicated account manager'
            ]
        ]
    ],

    // ========================================================================
    // PRODUCT TESTING SCENARIOS
    // ========================================================================
    'test_products' => [
        'electronics_phone' => [
            'id' => 'prod_001',
            'name' => 'Samsung Galaxy A54',
            'price' => 2500.00, // ZMW 2,500
            'category' => 'Electronics',
            'vendor_commission' => 0.15, // 15%
        ],
        
        'clothing_dress' => [
            'id' => 'prod_002', 
            'name' => 'African Print Dress',
            'price' => 125.00, // ZMW 125
            'category' => 'Fashion',
            'vendor_commission' => 0.20, // 20%
        ],
        
        'food_package' => [
            'id' => 'prod_003',
            'name' => 'Local Food Package',
            'price' => 45.00, // ZMW 45
            'category' => 'Food & Beverages',
            'vendor_commission' => 0.10, // 10%
        ]
    ],

    // ========================================================================
    // API CONFIGURATION
    // ========================================================================
    'api_config' => [
        'environment' => 'sandbox', // sandbox | production
        'base_url' => 'https://api.sandbox.pawapay.io',
        'webhook_url' => 'http://localhost:8080/webhook/pawapay',
        'return_url' => 'http://localhost:8080/payment/complete',
        'cancel_url' => 'http://localhost:8080/payment/cancelled',
    ],

    // ========================================================================
    // TESTING SCENARIOS CONFIGURATION
    // ========================================================================
    'test_scenarios' => [
        'membership_payment' => [
            'name' => 'Membership Plan Payment',
            'description' => 'Test vendor membership subscription payments',
            'test_cases' => ['basic_vendor', 'premium_vendor', 'enterprise_vendor']
        ],
        
        'product_purchase' => [
            'name' => 'Product Purchase Payment',
            'description' => 'Test marketplace product purchases with commissions',
            'test_cases' => ['single_product', 'multi_vendor_cart', 'high_value_purchase']
        ],
        
        'wallet_deposit' => [
            'name' => 'Wallet Top-up',
            'description' => 'Test user wallet deposit functionality',
            'amounts' => [20.00, 50.00, 100.00, 500.00]
        ],
        
        'fee_calculation' => [
            'name' => 'Fee Calculation Testing',
            'description' => 'Test different fee scenarios across operators',
            'test_amounts' => [10.00, 25.00, 75.00, 150.00, 500.00, 1500.00]
        ]
    ]
];