<?php

/**
 * ============================================================================
 * PAWAPAY SDK - COMPREHENSIVE FEE CALCULATION SERVICE
 * ============================================================================
 * 
 * This service implements the complete PawaPay fee structure based on real
 * fee data from https://www.pawapay.io/fees with support for:
 * 
 * - Country-specific fee calculations
 * - Operator-specific fee tiers  
 * - Fixed fees + percentage combinations
 * - Collection vs Disbursement fees
 * - Real-time fee calculations
 * - Environment-based fee overrides
 * 
 * Fee Structure:
 * - Global PawaPay fee: 1% on all transactions
 * - MMO (Mobile Money Operator) fees: Vary by country/operator/amount
 * - Total fee = MMO_fee + PawaPay_fee (1%)
 * 
 * @author PawaPay SDK Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

namespace PawaPay\Service;

use InvalidArgumentException;
use PawaPay\Exception\FeeCalculationException;

class FeeCalculator
{
    // Global PawaPay platform fee (1%)
    private const PAWAPAY_PLATFORM_FEE = 0.01;
    
    // Cache for fee structures to avoid repeated calculations
    private static $feeCache = [];
    
    // Environment configuration
    private $config;
    
    /**
     * Fee structure database with real PawaPay fees
     * Format: [country][operator][type] = fee_structure
     */
    private const FEE_STRUCTURE = [
        'KE' => [ // Kenya
            'MPESA' => [
                'collections' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 100, 'fixed_fee' => 0, 'currency' => 'KSH'],
                        ['max' => 500, 'fixed_fee' => 5, 'currency' => 'KSH'],
                        ['max' => 1000, 'fixed_fee' => 10, 'currency' => 'KSH'],
                        ['max' => 1500, 'fixed_fee' => 15, 'currency' => 'KSH'],
                        ['max' => 2500, 'fixed_fee' => 20, 'currency' => 'KSH'],
                        ['max' => 3500, 'fixed_fee' => 25, 'currency' => 'KSH'],
                        ['max' => 5000, 'fixed_fee' => 34, 'currency' => 'KSH'],
                        ['max' => 7500, 'fixed_fee' => 42, 'currency' => 'KSH'],
                        ['max' => 10000, 'fixed_fee' => 48, 'currency' => 'KSH'],
                        ['max' => 15000, 'fixed_fee' => 57, 'currency' => 'KSH'],
                        ['max' => 20000, 'fixed_fee' => 62, 'currency' => 'KSH'],
                        ['max' => 25000, 'fixed_fee' => 67, 'currency' => 'KSH'],
                        ['max' => 30000, 'fixed_fee' => 72, 'currency' => 'KSH'],
                        ['max' => 35000, 'fixed_fee' => 83, 'currency' => 'KSH'],
                        ['max' => 40000, 'fixed_fee' => 99, 'currency' => 'KSH'],
                        ['max' => 45000, 'fixed_fee' => 103, 'currency' => 'KSH'],
                        ['max' => 150000, 'fixed_fee' => 108, 'currency' => 'KSH']
                    ]
                ],
                'disbursements' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 100, 'fixed_fee' => 0, 'currency' => 'KSH'],
                        ['max' => 1500, 'fixed_fee' => 5, 'currency' => 'KSH'],
                        ['max' => 5000, 'fixed_fee' => 9, 'currency' => 'KSH'],
                        ['max' => 20000, 'fixed_fee' => 11, 'currency' => 'KSH'],
                        ['max' => 150000, 'fixed_fee' => 13, 'currency' => 'KSH']
                    ]
                ]
            ]
        ],
        'UG' => [ // Uganda
            'MTN' => [
                'disbursements' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 500, 'fixed_fee' => 0, 'currency' => 'UGX'],
                        ['max' => 60000, 'fixed_fee' => 300, 'currency' => 'UGX'],
                        ['max' => 500000, 'fixed_fee' => 600, 'currency' => 'UGX'],
                        ['max' => 1000000, 'fixed_fee' => 1000, 'currency' => 'UGX'],
                        ['max' => PHP_INT_MAX, 'fixed_fee' => 1200, 'currency' => 'UGX']
                    ]
                ]
            ],
            'AIRTEL' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.015, // 1.5%
                    'total_percentage' => 0.025 // 2.5% (1.5% MMO + 1% PawaPay)
                ],
                'disbursements' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 499, 'fixed_fee' => 0, 'currency' => 'UGX'],
                        ['max' => 60000, 'fixed_fee' => 300, 'currency' => 'UGX'],
                        ['max' => 500000, 'fixed_fee' => 600, 'currency' => 'UGX'],
                        ['max' => PHP_INT_MAX, 'fixed_fee' => 1000, 'currency' => 'UGX']
                    ]
                ]
            ]
        ],
        'ZM' => [ // Zambia
            'AIRTEL' => [
                'collections' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 150.01, 'fixed_fee' => 0.50, 'currency' => 'ZMW'],
                        ['max' => 300.01, 'fixed_fee' => 1.00, 'currency' => 'ZMW'],
                        ['max' => 500.01, 'fixed_fee' => 1.00, 'currency' => 'ZMW'],
                        ['max' => 1000.01, 'fixed_fee' => 1.50, 'currency' => 'ZMW'],
                        ['max' => 3000.01, 'fixed_fee' => 2.80, 'currency' => 'ZMW'],
                        ['max' => 5000.01, 'fixed_fee' => 4.00, 'currency' => 'ZMW'],
                        ['max' => 10000.01, 'fixed_fee' => 5.50, 'currency' => 'ZMW']
                    ]
                ]
            ],
            'MTN' => [
                'collections' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 150.01, 'fixed_fee' => 0.42, 'currency' => 'ZMW'],
                        ['max' => 300.01, 'fixed_fee' => 0.90, 'currency' => 'ZMW'],
                        ['max' => 500.01, 'fixed_fee' => 0.80, 'currency' => 'ZMW'],
                        ['max' => 1000.01, 'fixed_fee' => 1.00, 'currency' => 'ZMW'],
                        ['max' => 3000.01, 'fixed_fee' => 2.20, 'currency' => 'ZMW'],
                        ['max' => 5000.01, 'fixed_fee' => 3.00, 'currency' => 'ZMW'],
                        ['max' => 10000.01, 'fixed_fee' => 4.00, 'currency' => 'ZMW'],
                        ['max' => PHP_INT_MAX, 'fixed_fee' => 5.20, 'currency' => 'ZMW']
                    ]
                ],
                'disbursements' => [
                    'type' => 'tiered_mixed',
                    'tiers' => [
                        ['max' => 150.01, 'percentage' => 0.02, 'fixed_fee' => 0.16, 'currency' => 'ZMW'],
                        ['max' => 300.01, 'percentage' => 0.02, 'fixed_fee' => 0.20, 'currency' => 'ZMW'],
                        ['max' => 500.01, 'percentage' => 0.02, 'fixed_fee' => 0.40, 'currency' => 'ZMW'],
                        ['max' => 1000.01, 'percentage' => 0.02, 'fixed_fee' => 1.00, 'currency' => 'ZMW'],
                        ['max' => 3000.01, 'percentage' => 0.02, 'fixed_fee' => 1.60, 'currency' => 'ZMW'],
                        ['max' => 5000.01, 'percentage' => 0.02, 'fixed_fee' => 2.00, 'currency' => 'ZMW'],
                        ['max' => 10000.01, 'percentage' => 0.02, 'fixed_fee' => 3.00, 'currency' => 'ZMW'],
                        ['max' => PHP_INT_MAX, 'percentage' => 0.02, 'fixed_fee' => 3.60, 'currency' => 'ZMW']
                    ]
                ]
            ],
            'ZAMTEL' => [
                'collections' => [
                    'type' => 'tiered_fixed',
                    'tiers' => [
                        ['max' => 150.01, 'fixed_fee' => 0.42, 'currency' => 'ZMW'],
                        ['max' => 300.01, 'fixed_fee' => 0.80, 'currency' => 'ZMW'],
                        ['max' => 500.01, 'fixed_fee' => 0.90, 'currency' => 'ZMW'],
                        ['max' => 1000.01, 'fixed_fee' => 1.00, 'currency' => 'ZMW'],
                        ['max' => 3000.01, 'fixed_fee' => 2.00, 'currency' => 'ZMW'],
                        ['max' => 5000.01, 'fixed_fee' => 3.00, 'currency' => 'ZMW'],
                        ['max' => 10000.01, 'fixed_fee' => 4.00, 'currency' => 'ZMW'],
                        ['max' => PHP_INT_MAX, 'fixed_fee' => 5.00, 'currency' => 'ZMW']
                    ]
                ]
            ]
        ],
        'TZ' => [ // Tanzania
            'AIRTEL' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.0118, // 1.18%
                    'total_percentage' => 0.0218 // 2.18% (1.18% MMO + 1% PawaPay)
                ]
            ],
            'disbursements' => [
                'type' => 'tiered_fixed',
                'tiers' => [
                    ['max' => 2000, 'fixed_fee' => 40, 'currency' => 'TZS'],
                    ['max' => 4000, 'fixed_fee' => 80, 'currency' => 'TZS'],
                    ['max' => 5000, 'fixed_fee' => 120, 'currency' => 'TZS'],
                    ['max' => 10000, 'fixed_fee' => 140, 'currency' => 'TZS'],
                    ['max' => 20000, 'fixed_fee' => 160, 'currency' => 'TZS'],
                    ['max' => PHP_INT_MAX, 'fixed_fee' => 200, 'currency' => 'TZS']
                ]
            ]
        ],
        'RW' => [ // Rwanda
            'MTN' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.021, // 2.1%
                    'total_percentage' => 0.031 // 3.1% (2.1% MMO + 1% PawaPay)
                ]
            ],
            'AIRTEL' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.015, // 1.5%
                    'total_percentage' => 0.025 // 2.5% (1.5% MMO + 1% PawaPay)
                ]
            ]
        ],
        'MZ' => [ // Mozambique
            'disbursements' => [
                'type' => 'tiered_mixed',
                'tiers' => [
                    ['max' => 500.01, 'fixed_fee' => 5, 'currency' => 'MZN'],
                    ['max' => 12000.01, 'percentage' => 0.01, 'currency' => 'MZN'], // 1% MMO + 1% PawaPay = 2%
                    ['max' => PHP_INT_MAX, 'fixed_fee' => 120, 'currency' => 'MZN']
                ]
            ]
        ],
        'MW' => [ // Malawi
            'AIRTEL' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.0233, // 2.33%
                    'total_percentage' => 0.0333 // 3.33% (2.33% MMO + 1% PawaPay)
                ]
            ]
        ],
        'CI' => [ // Ivory Coast
            'MTN' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.008, // 0.8%
                    'total_percentage' => 0.018 // 1.8% (0.8% MMO + 1% PawaPay)
                ]
            ],
            'ORANGE' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.015, // 1.5%
                    'total_percentage' => 0.025 // 2.5% (1.5% MMO + 1% PawaPay)
                ]
            ]
        ],
        'ET' => [ // Ethiopia
            'SAFARICOM_MPESA' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.005, // 0.5%
                    'total_percentage' => 0.015 // 1.5% (0.5% MMO + 1% PawaPay)
                ]
            ]
        ],
        'CM' => [ // Cameroon
            'MTN' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.0075, // 0.75%
                    'total_percentage' => 0.0175 // 1.75% (0.75% MMO + 1% PawaPay)
                ]
            ],
            'ORANGE' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.0077, // 0.77%
                    'total_percentage' => 0.0177 // 1.77% (0.77% MMO + 1% PawaPay)
                ]
            ]
        ],
        'CD' => [ // DRC
            'VODACOM' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.015, // 1.5%
                    'total_percentage' => 0.025 // 2.5% (1.5% MMO + 1% PawaPay)
                ]
            ]
        ],
        'BJ' => [ // Benin
            'MTN' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.012, // 1.2%
                    'total_percentage' => 0.022 // 2.2% (1.2% MMO + 1% PawaPay)
                ]
            ],
            'MOOV' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.012, // 1.2%
                    'total_percentage' => 0.022 // 2.2% (1.2% MMO + 1% PawaPay)
                ]
            ]
        ],
        'SL' => [ // Sierra Leone
            'ORANGE' => [
                'collections' => [
                    'type' => 'percentage',
                    'mmo_percentage' => 0.023, // 2.3%
                    'total_percentage' => 0.033 // 3.3% (2.3% MMO + 1% PawaPay)
                ]
            ]
        ]
        // More countries can be added as needed
    ];
    
    /**
     * Initialize fee calculator with configuration
     * 
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'fee_override_percentage' => null, // Override fee percentage (0 = no fee)
            'apply_to_amount' => true, // Apply fee to transaction amount
            'round_precision' => 2, // Decimal places for fee amounts
            'minimum_fee' => null, // Minimum fee amount
            'maximum_fee' => null, // Maximum fee amount
            'debug_mode' => false // Enable debug logging
        ], $config);
    }
    
    /**
     * Calculate total fee for a transaction
     * 
     * @param float $amount Transaction amount
     * @param string $currency Currency code (USD, KES, UGX, etc.)
     * @param string $country Country code (KE, UG, ZM, etc.)
     * @param string $operator Mobile money operator (MTN, AIRTEL, MPESA, etc.)
     * @param string $type Transaction type (collections|disbursements)
     * @return array Fee calculation result
     * @throws FeeCalculationException
     */
    public function calculateFee(
        float $amount, 
        string $currency, 
        string $country = 'KE', 
        string $operator = 'MPESA', 
        string $type = 'collections'
    ): array {
        
        // Validate inputs
        $this->validateInputs($amount, $currency, $country, $operator, $type);
        
        // Check for fee override
        if ($this->config['fee_override_percentage'] !== null) {
            return $this->calculateOverrideFee($amount, $currency);
        }
        
        // Get fee structure for country/operator/type
        $feeStructure = $this->getFeeStructure($country, $operator, $type);
        
        // Calculate fee based on structure type
        $feeResult = $this->calculateByStructure($amount, $currency, $feeStructure);
        
        // Apply constraints (min/max fee)
        $feeResult = $this->applyFeeConstraints($feeResult);
        
        // Add metadata
        $feeResult['calculation_metadata'] = [
            'country' => $country,
            'operator' => $operator,
            'type' => $type,
            'currency' => $currency,
            'original_amount' => $amount,
            'fee_structure_type' => $feeStructure['type'] ?? 'default',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $feeResult;
    }
    
    /**
     * Calculate total amount including fees (for display to customer)
     * 
     * @param float $amount Original transaction amount
     * @param string $currency Currency code
     * @param string $country Country code
     * @param string $operator Mobile money operator
     * @param string $type Transaction type
     * @return array Calculation result with total amount
     */
    public function calculateTotalWithFees(
        float $amount, 
        string $currency, 
        string $country = 'KE', 
        string $operator = 'MPESA', 
        string $type = 'collections'
    ): array {
        
        $feeCalculation = $this->calculateFee($amount, $currency, $country, $operator, $type);
        
        return [
            'original_amount' => $amount,
            'fee_amount' => $feeCalculation['total_fee_amount'],
            'total_amount' => $amount + $feeCalculation['total_fee_amount'],
            'currency' => $currency,
            'fee_breakdown' => $feeCalculation,
            'display_info' => [
                'subtotal' => number_format($amount, 2),
                'fees' => number_format($feeCalculation['total_fee_amount'], 2),
                'total' => number_format($amount + $feeCalculation['total_fee_amount'], 2)
            ]
        ];
    }
    
    /**
     * Get available countries and operators
     * 
     * @return array List of supported countries and operators
     */
    public function getSupportedCountriesAndOperators(): array
    {
        $supported = [];
        
        foreach (self::FEE_STRUCTURE as $country => $operators) {
            $supported[$country] = [];
            
            foreach ($operators as $operator => $types) {
                if (is_array($types) && !isset($types['type'])) {
                    $supported[$country][] = $operator;
                }
            }
            
            // Handle country-level fee structures (like Tanzania disbursements)
            if (isset($operators['type']) || isset($operators['disbursements']) || isset($operators['collections'])) {
                $supported[$country][] = 'DEFAULT';
            }
        }
        
        return $supported;
    }
    
    /**
     * Get fee preview for display purposes
     * 
     * @param float $amount Transaction amount
     * @param string $currency Currency code
     * @param string $country Country code
     * @param string $operator Mobile money operator
     * @return array Fee preview information
     */
    public function getFeePreview(
        float $amount, 
        string $currency, 
        string $country = 'KE', 
        string $operator = 'MPESA'
    ): array {
        
        try {
            $collectionFee = $this->calculateFee($amount, $currency, $country, $operator, 'collections');
            $disbursementFee = null;
            
            // Try to get disbursement fee if available
            try {
                $disbursementFee = $this->calculateFee($amount, $currency, $country, $operator, 'disbursements');
            } catch (FeeCalculationException $e) {
                // Disbursements not available for this operator
            }
            
            return [
                'amount' => $amount,
                'currency' => $currency,
                'country' => $country,
                'operator' => $operator,
                'collections' => [
                    'available' => true,
                    'fee' => $collectionFee['total_fee_amount'],
                    'total' => $amount + $collectionFee['total_fee_amount']
                ],
                'disbursements' => [
                    'available' => $disbursementFee !== null,
                    'fee' => $disbursementFee['total_fee_amount'] ?? 0,
                    'total' => $disbursementFee ? $amount + $disbursementFee['total_fee_amount'] : $amount
                ]
            ];
            
        } catch (FeeCalculationException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'fallback_fee' => $amount * self::PAWAPAY_PLATFORM_FEE // Just PawaPay fee
            ];
        }
    }
    
    /**
     * Validate calculation inputs
     */
    private function validateInputs(float $amount, string $currency, string $country, string $operator, string $type): void
    {
        if ($amount <= 0) {
            throw new FeeCalculationException("Amount must be greater than 0, got: {$amount}");
        }
        
        if (empty($currency) || strlen($currency) !== 3) {
            throw new FeeCalculationException("Currency must be a valid 3-letter code, got: {$currency}");
        }
        
        if (empty($country) || strlen($country) !== 2) {
            throw new FeeCalculationException("Country must be a valid 2-letter code, got: {$country}");
        }
        
        if (!in_array($type, ['collections', 'disbursements'])) {
            throw new FeeCalculationException("Type must be 'collections' or 'disbursements', got: {$type}");
        }
    }
    
    /**
     * Get fee structure for country/operator/type combination
     */
    private function getFeeStructure(string $country, string $operator, string $type): array
    {
        // Check specific operator structure
        if (isset(self::FEE_STRUCTURE[$country][$operator][$type])) {
            return self::FEE_STRUCTURE[$country][$operator][$type];
        }
        
        // Check country-level structure (e.g., Tanzania disbursements)
        if (isset(self::FEE_STRUCTURE[$country][$type])) {
            return self::FEE_STRUCTURE[$country][$type];
        }
        
        // Fallback to default percentage structure
        return [
            'type' => 'percentage',
            'mmo_percentage' => 0.01, // 1% MMO default
            'total_percentage' => 0.02 // 2% total (1% MMO + 1% PawaPay)
        ];
    }
    
    /**
     * Calculate fee based on fee structure type
     */
    private function calculateByStructure(float $amount, string $currency, array $feeStructure): array
    {
        switch ($feeStructure['type']) {
            case 'tiered_fixed':
                return $this->calculateTieredFixedFee($amount, $currency, $feeStructure);
                
            case 'tiered_mixed':
                return $this->calculateTieredMixedFee($amount, $currency, $feeStructure);
                
            case 'percentage':
                return $this->calculatePercentageFee($amount, $currency, $feeStructure);
                
            default:
                return $this->calculateDefaultFee($amount, $currency);
        }
    }
    
    /**
     * Calculate tiered fixed fee (e.g., Kenya M-PESA)
     */
    private function calculateTieredFixedFee(float $amount, string $currency, array $structure): array
    {
        $fixedFee = 0;
        $mmoFee = 0;
        
        foreach ($structure['tiers'] as $tier) {
            if ($amount <= $tier['max']) {
                $fixedFee = $tier['fixed_fee'];
                $mmoFee = $fixedFee;
                break;
            }
        }
        
        $pawaPayFee = $amount * self::PAWAPAY_PLATFORM_FEE;
        $totalFee = $fixedFee + $pawaPayFee;
        
        return [
            'mmo_fee_amount' => round($mmoFee, $this->config['round_precision']),
            'pawapay_fee_amount' => round($pawaPayFee, $this->config['round_precision']),
            'total_fee_amount' => round($totalFee, $this->config['round_precision']),
            'fee_currency' => $currency,
            'fee_type' => 'tiered_fixed'
        ];
    }
    
    /**
     * Calculate tiered mixed fee (percentage + fixed, e.g., Zambia MTN disbursements)
     */
    private function calculateTieredMixedFee(float $amount, string $currency, array $structure): array
    {
        $fixedFee = 0;
        $percentageFee = 0;
        
        foreach ($structure['tiers'] as $tier) {
            if ($amount <= $tier['max']) {
                $fixedFee = $tier['fixed_fee'] ?? 0;
                $percentageFee = ($tier['percentage'] ?? 0) * $amount;
                break;
            }
        }
        
        $mmoFee = $fixedFee + $percentageFee;
        $pawaPayFee = $amount * self::PAWAPAY_PLATFORM_FEE;
        $totalFee = $mmoFee + $pawaPayFee;
        
        return [
            'mmo_fee_amount' => round($mmoFee, $this->config['round_precision']),
            'pawapay_fee_amount' => round($pawaPayFee, $this->config['round_precision']),
            'total_fee_amount' => round($totalFee, $this->config['round_precision']),
            'fee_currency' => $currency,
            'fee_type' => 'tiered_mixed'
        ];
    }
    
    /**
     * Calculate percentage-based fee
     */
    private function calculatePercentageFee(float $amount, string $currency, array $structure): array
    {
        $totalPercentage = $structure['total_percentage'] ?? 0.02;
        $mmoPercentage = $structure['mmo_percentage'] ?? ($totalPercentage - self::PAWAPAY_PLATFORM_FEE);
        
        $mmoFee = $amount * $mmoPercentage;
        $pawaPayFee = $amount * self::PAWAPAY_PLATFORM_FEE;
        $totalFee = $amount * $totalPercentage;
        
        return [
            'mmo_fee_amount' => round($mmoFee, $this->config['round_precision']),
            'pawapay_fee_amount' => round($pawaPayFee, $this->config['round_precision']),
            'total_fee_amount' => round($totalFee, $this->config['round_precision']),
            'fee_currency' => $currency,
            'fee_type' => 'percentage'
        ];
    }
    
    /**
     * Calculate default fee (PawaPay only)
     */
    private function calculateDefaultFee(float $amount, string $currency): array
    {
        $pawaPayFee = $amount * self::PAWAPAY_PLATFORM_FEE;
        
        return [
            'mmo_fee_amount' => 0,
            'pawapay_fee_amount' => round($pawaPayFee, $this->config['round_precision']),
            'total_fee_amount' => round($pawaPayFee, $this->config['round_precision']),
            'fee_currency' => $currency,
            'fee_type' => 'default'
        ];
    }
    
    /**
     * Calculate override fee when percentage is set in config
     */
    private function calculateOverrideFee(float $amount, string $currency): array
    {
        $overridePercentage = (float)$this->config['fee_override_percentage'];
        
        if ($overridePercentage === 0.0) {
            return [
                'mmo_fee_amount' => 0,
                'pawapay_fee_amount' => 0,
                'total_fee_amount' => 0,
                'fee_currency' => $currency,
                'fee_type' => 'override_zero'
            ];
        }
        
        $totalFee = $amount * $overridePercentage;
        
        return [
            'mmo_fee_amount' => 0,
            'pawapay_fee_amount' => round($totalFee, $this->config['round_precision']),
            'total_fee_amount' => round($totalFee, $this->config['round_precision']),
            'fee_currency' => $currency,
            'fee_type' => 'override'
        ];
    }
    
    /**
     * Apply minimum and maximum fee constraints
     */
    private function applyFeeConstraints(array $feeResult): array
    {
        if ($this->config['minimum_fee'] !== null) {
            $feeResult['total_fee_amount'] = max($feeResult['total_fee_amount'], $this->config['minimum_fee']);
        }
        
        if ($this->config['maximum_fee'] !== null) {
            $feeResult['total_fee_amount'] = min($feeResult['total_fee_amount'], $this->config['maximum_fee']);
        }
        
        return $feeResult;
    }
}