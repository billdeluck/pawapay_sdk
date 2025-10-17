<?php
/**
 * ============================================================================
 * ZAMBIA-SPECIFIC FEE CALCULATOR FOR PAWAPAY TESTING
 * ============================================================================
 * 
 * Enhanced fee calculator specifically for Zambia mobile money operators
 * with real-time fee calculations based on operator-specific structures.
 * 
 * Supports:
 * - Airtel Money Zambia (fixed + percentage)
 * - MTN Mobile Money Zambia (percentage only) 
 * - Zamtel Kwacha (tiered structure)
 * - Environment-based fee activation
 * 
 * @version 2.0.0
 * @since 2024-10-17
 * ============================================================================
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PawaPay\Service\FeeCalculator;
use PawaPay\Config\PawaPayConfig;

class ZambiaFeeCalculator
{
    private $config;
    private $feeCalculator;
    private $zambiaConfig;
    
    public function __construct()
    {
        // Load configurations
        $this->zambiaConfig = include __DIR__ . '/../config/zambia_config.php';
        $this->config = new PawaPayConfig();
        $this->feeCalculator = new FeeCalculator($this->config);
    }
    
    /**
     * Calculate fees for Zambia operators with environment controls
     */
    public function calculateZambiaFees(float $amount, string $operator = 'MTN_MOMO_ZMB'): array
    {
        // Check if fees are enabled in environment
        if (!$this->areFeesEnabled()) {
            return [
                'operator_fee' => 0.00,
                'pawapay_fee' => 0.00,
                'total_fee' => 0.00,
                'amount_with_fees' => $amount,
                'fee_breakdown' => [
                    'fees_disabled' => true,
                    'reason' => 'Fees disabled in environment configuration'
                ]
            ];
        }
        
        $operatorConfig = $this->zambiaConfig['zambia_operators'][$operator] ?? null;
        if (!$operatorConfig) {
            throw new \Exception("Unsupported operator: {$operator}");
        }
        
        $operatorFee = $this->calculateOperatorFee($amount, $operatorConfig);
        $pawapayFee = $this->calculatePawapayFee($amount);
        $totalFee = $operatorFee + $pawapayFee;
        
        return [
            'operator' => $operator,
            'operator_name' => $operatorConfig['display_name'],
            'currency' => $operatorConfig['currency'],
            'amount' => $amount,
            'operator_fee' => round($operatorFee, 2),
            'pawapay_fee' => round($pawapayFee, 2),
            'total_fee' => round($totalFee, 2),
            'amount_with_fees' => round($amount + $totalFee, 2),
            'fee_breakdown' => [
                'operator_fee_structure' => $operatorConfig['fee_structure'],
                'customer_pays_fees' => $this->customerPaysFees(),
                'fee_calculation_method' => $this->getFeeCalculationMethod($operatorConfig),
                'applied_tiers' => $this->getAppliedTiers($amount, $operatorConfig)
            ]
        ];
    }
    
    /**
     * Calculate operator-specific fees
     */
    private function calculateOperatorFee(float $amount, array $config): float
    {
        switch ($config['fee_structure']) {
            case 'mixed':
                // Fixed fee + percentage (e.g., Airtel)
                $baseFee = $config['base_fee'];
                $percentageFee = $amount * $config['percentage_fee'];
                $totalFee = $baseFee + $percentageFee;
                break;
                
            case 'percentage':
                // Percentage only (e.g., MTN)
                $totalFee = $amount * $config['percentage_fee'];
                break;
                
            case 'tiered':
                // Tiered structure (e.g., Zamtel)
                $totalFee = $this->calculateTieredFee($amount, $config['tiers']);
                break;
                
            default:
                $totalFee = 0.00;
        }
        
        // Apply min/max limits if defined
        if (isset($config['minimum_fee'])) {
            $totalFee = max($totalFee, $config['minimum_fee']);
        }
        if (isset($config['maximum_fee'])) {
            $totalFee = min($totalFee, $config['maximum_fee']);
        }
        
        return $totalFee;
    }
    
    /**
     * Calculate tiered fees for operators like Zamtel
     */
    private function calculateTieredFee(float $amount, array $tiers): float
    {
        foreach ($tiers as $tier) {
            if ($amount >= $tier['min'] && $amount <= $tier['max']) {
                if ($tier['percentage'] > 0) {
                    return $amount * $tier['percentage'];
                } else {
                    return $tier['fixed'];
                }
            }
        }
        
        // If amount exceeds all tiers, use the last tier's percentage
        $lastTier = end($tiers);
        return $amount * $lastTier['percentage'];
    }
    
    /**
     * Calculate PawaPay platform fee (1%)
     */
    private function calculatePawapayFee(float $amount): float
    {
        if (!$this->zambiaConfig['pawapay_global']['apply_global_fee']) {
            return 0.00;
        }
        
        return $amount * $this->zambiaConfig['pawapay_global']['global_fee_percentage'];
    }
    
    /**
     * Check if fees are enabled in environment
     */
    private function areFeesEnabled(): bool
    {
        $envEnabled = $_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] ?? 'true';
        $configEnabled = $this->zambiaConfig['fee_settings']['enable_fees'] ?? true;
        
        return (strtolower($envEnabled) === 'true') && $configEnabled;
    }
    
    /**
     * Check if customer pays fees or merchant absorbs them
     */
    private function customerPaysFees(): bool
    {
        $envSetting = $_ENV['PAWAPAY_CUSTOMER_PAYS_FEES'] ?? 'true';
        $configSetting = $this->zambiaConfig['fee_settings']['apply_to_customer'] ?? true;
        
        return (strtolower($envSetting) === 'true') && $configSetting;
    }
    
    /**
     * Get fee calculation method description
     */
    private function getFeeCalculationMethod(array $config): string
    {
        switch ($config['fee_structure']) {
            case 'mixed':
                return "Fixed fee ({$config['base_fee']} {$config['currency']}) + Percentage ({$config['percentage_fee']}%)";
                
            case 'percentage':
                return "Percentage only ({$config['percentage_fee']}% of amount)";
                
            case 'tiered':
                return "Tiered structure based on amount ranges";
                
            default:
                return "No fees";
        }
    }
    
    /**
     * Get applied tier information for tiered fee structures
     */
    private function getAppliedTiers(float $amount, array $config): array
    {
        if ($config['fee_structure'] !== 'tiered') {
            return [];
        }
        
        foreach ($config['tiers'] as $index => $tier) {
            if ($amount >= $tier['min'] && $amount <= $tier['max']) {
                return [
                    'tier_index' => $index,
                    'tier_range' => "{$tier['min']} - {$tier['max']} {$config['currency']}",
                    'tier_fee' => $tier['percentage'] > 0 ? "{$tier['percentage']}%" : "{$tier['fixed']} {$config['currency']}"
                ];
            }
        }
        
        return [];
    }
    
    /**
     * Get all available Zambia operators
     */
    public function getAvailableOperators(): array
    {
        $operators = [];
        foreach ($this->zambiaConfig['zambia_operators'] as $key => $config) {
            $operators[$key] = [
                'code' => $key,
                'name' => $config['name'],
                'display_name' => $config['display_name'],
                'currency' => $config['currency'],
                'fee_structure' => $config['fee_structure'],
                'test_phones' => $config['test_phones']
            ];
        }
        return $operators;
    }
    
    /**
     * Get test phone number for specific operator and scenario
     */
    public function getTestPhone(string $operator, string $scenario = 'success'): ?string
    {
        $config = $this->zambiaConfig['zambia_operators'][$operator] ?? null;
        if (!$config) {
            return null;
        }
        
        return $config['test_phones'][$scenario] ?? null;
    }
    
    /**
     * Calculate membership plan pricing with fees
     */
    public function calculateMembershipPricing(string $planId, string $operator = 'MTN_MOMO_ZMB'): array
    {
        $plan = $this->zambiaConfig['membership_plans'][$planId] ?? null;
        if (!$plan) {
            throw new \Exception("Membership plan not found: {$planId}");
        }
        
        $feeCalculation = $this->calculateZambiaFees($plan['price'], $operator);
        
        return [
            'plan' => $plan,
            'pricing' => $feeCalculation,
            'payment_breakdown' => [
                'plan_price' => $plan['price'],
                'operator_fee' => $feeCalculation['operator_fee'],
                'pawapay_fee' => $feeCalculation['pawapay_fee'],
                'total_to_pay' => $feeCalculation['amount_with_fees']
            ]
        ];
    }
    
    /**
     * Get fee comparison across all operators
     */
    public function compareOperatorFees(float $amount): array
    {
        $comparison = [];
        
        foreach ($this->zambiaConfig['zambia_operators'] as $operatorCode => $config) {
            try {
                $feeCalc = $this->calculateZambiaFees($amount, $operatorCode);
                $comparison[$operatorCode] = [
                    'operator_name' => $config['display_name'],
                    'total_fee' => $feeCalc['total_fee'],
                    'total_to_pay' => $feeCalc['amount_with_fees'],
                    'fee_structure' => $config['fee_structure']
                ];
            } catch (\Exception $e) {
                $comparison[$operatorCode] = [
                    'operator_name' => $config['display_name'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Sort by total amount to pay
        uasort($comparison, function($a, $b) {
            if (isset($a['error']) || isset($b['error'])) return 0;
            return $a['total_to_pay'] <=> $b['total_to_pay'];
        });
        
        return $comparison;
    }
}