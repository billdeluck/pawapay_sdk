<?php

/**
 * ============================================================================
 * PAWAPAY SDK HELPER FUNCTIONS
 * ============================================================================
 * 
 * Global helper functions for the PawaPay SDK to make common operations
 * more convenient and accessible throughout the application.
 * 
 * @author PawaPay SDK Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

use PawaPay\Config\PawaPayConfig;
use PawaPay\Service\FeeCalculator;

if (!function_exists('pawapay_config')) {
    /**
     * Get PawaPay configuration value
     *
     * @param string|null $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or entire config array
     */
    function pawapay_config(?string $key = null, $default = null)
    {
        $config = PawaPayConfig::getInstance();
        
        if ($key === null) {
            return $config->all();
        }
        
        return $config->get($key, $default);
    }
}

if (!function_exists('pawapay_fee_calculator')) {
    /**
     * Get a configured instance of the PawaPay fee calculator
     *
     * @return FeeCalculator
     */
    function pawapay_fee_calculator(): FeeCalculator
    {
        $config = PawaPayConfig::getInstance();
        return new FeeCalculator($config->getFeeCalculatorConfig());
    }
}

if (!function_exists('pawapay_calculate_fee')) {
    /**
     * Calculate PawaPay fee for a transaction
     *
     * @param float $amount Transaction amount
     * @param string $currency Currency code
     * @param string $country Country code
     * @param string $operator Mobile money operator
     * @param string $type Transaction type (collections|disbursements)
     * @return array Fee calculation result
     */
    function pawapay_calculate_fee(
        float $amount,
        string $currency,
        string $country = null,
        string $operator = null,
        string $type = 'collections'
    ): array {
        $config = PawaPayConfig::getInstance();
        $calculator = new FeeCalculator($config->getFeeCalculatorConfig());
        
        $country = $country ?? $config->get('default_country');
        $operator = $operator ?? $config->get('default_operator');
        
        return $calculator->calculateFee($amount, $currency, $country, $operator, $type);
    }
}

if (!function_exists('pawapay_total_with_fees')) {
    /**
     * Calculate total amount including PawaPay fees
     *
     * @param float $amount Original transaction amount
     * @param string $currency Currency code
     * @param string $country Country code
     * @param string $operator Mobile money operator
     * @param string $type Transaction type
     * @return array Total calculation result
     */
    function pawapay_total_with_fees(
        float $amount,
        string $currency,
        string $country = null,
        string $operator = null,
        string $type = 'collections'
    ): array {
        $config = PawaPayConfig::getInstance();
        $calculator = new FeeCalculator($config->getFeeCalculatorConfig());
        
        $country = $country ?? $config->get('default_country');
        $operator = $operator ?? $config->get('default_operator');
        
        return $calculator->calculateTotalWithFees($amount, $currency, $country, $operator, $type);
    }
}

if (!function_exists('pawapay_format_currency')) {
    /**
     * Format currency amount for display
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @param int|null $decimals Number of decimal places
     * @return string Formatted currency string
     */
    function pawapay_format_currency(float $amount, string $currency, ?int $decimals = null): string
    {
        $decimals = $decimals ?? pawapay_config('currency_decimals', 2);
        
        $formatted = number_format($amount, $decimals);
        
        // Add currency symbol or code
        $symbols = [
            'USD' => '$',
            'KES' => 'KSh ',
            'UGX' => 'USh ',
            'TZS' => 'TSh ',
            'ZMW' => 'ZK ',
            'MZN' => 'MT ',
            'RWF' => 'RWF ',
            'GHS' => 'GH₵ ',
            'XAF' => 'FCFA ',
            'XOF' => 'CFA '
        ];
        
        $symbol = $symbols[$currency] ?? ($currency . ' ');
        
        return $symbol . $formatted;
    }
}

if (!function_exists('pawapay_is_test_mode')) {
    /**
     * Check if PawaPay is in test mode
     *
     * @return bool
     */
    function pawapay_is_test_mode(): bool
    {
        return PawaPayConfig::getInstance()->isTestMode();
    }
}

if (!function_exists('pawapay_generate_deposit_id')) {
    /**
     * Generate a unique deposit ID for PawaPay transactions
     *
     * @param string $prefix Optional prefix for the ID
     * @return string Unique deposit ID
     */
    function pawapay_generate_deposit_id(string $prefix = 'PWP'): string
    {
        return $prefix . '_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
    }
}

if (!function_exists('pawapay_validate_phone_number')) {
    /**
     * Validate and format phone number for mobile money
     *
     * @param string $phone Phone number to validate
     * @param string $country Country code for validation rules
     * @return array Validation result with formatted number
     */
    function pawapay_validate_phone_number(string $phone, string $country = 'KE'): array
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Country-specific validation rules
        $rules = [
            'KE' => ['prefix' => '+254', 'length' => 13, 'pattern' => '/^\+254[17]\d{8}$/'],
            'UG' => ['prefix' => '+256', 'length' => 13, 'pattern' => '/^\+256[37]\d{8}$/'],
            'TZ' => ['prefix' => '+255', 'length' => 13, 'pattern' => '/^\+255[67]\d{8}$/'],
            'RW' => ['prefix' => '+250', 'length' => 12, 'pattern' => '/^\+250[78]\d{7}$/'],
            'ZM' => ['prefix' => '+260', 'length' => 12, 'pattern' => '/^\+260[79]\d{7}$/'],
        ];
        
        $rule = $rules[$country] ?? $rules['KE'];
        
        // Auto-format if starts with country prefix without +
        if (strlen($phone) > 3 && substr($phone, 0, 3) === substr($rule['prefix'], 1)) {
            $phone = '+' . $phone;
        }
        
        // Auto-format if starts with 0
        if (substr($phone, 0, 1) === '0') {
            $phone = $rule['prefix'] . substr($phone, 1);
        }
        
        // Validate format
        $isValid = preg_match($rule['pattern'], $phone) === 1;
        
        return [
            'valid' => $isValid,
            'formatted' => $phone,
            'country' => $country,
            'original' => func_get_arg(0)
        ];
    }
}

if (!function_exists('pawapay_supported_countries')) {
    /**
     * Get list of countries supported by PawaPay
     *
     * @return array Array of supported countries with details
     */
    function pawapay_supported_countries(): array
    {
        return [
            'KE' => ['name' => 'Kenya', 'currency' => 'KES', 'operators' => ['MPESA']],
            'UG' => ['name' => 'Uganda', 'currency' => 'UGX', 'operators' => ['MTN', 'AIRTEL']],
            'TZ' => ['name' => 'Tanzania', 'currency' => 'TZS', 'operators' => ['VODACOM', 'AIRTEL', 'HALOTEL']],
            'RW' => ['name' => 'Rwanda', 'currency' => 'RWF', 'operators' => ['MTN', 'AIRTEL']],
            'ZM' => ['name' => 'Zambia', 'currency' => 'ZMW', 'operators' => ['MTN', 'AIRTEL', 'ZAMTEL']],
            'MW' => ['name' => 'Malawi', 'currency' => 'MWK', 'operators' => ['AIRTEL', 'TNM']],
            'MZ' => ['name' => 'Mozambique', 'currency' => 'MZN', 'operators' => ['MPESA', 'MOVITEL']],
            'GH' => ['name' => 'Ghana', 'currency' => 'GHS', 'operators' => ['MTN', 'VODAFONE', 'AIRTELTIGO']],
            'CI' => ['name' => 'Ivory Coast', 'currency' => 'XOF', 'operators' => ['MTN', 'ORANGE', 'MOOV']],
            'SN' => ['name' => 'Senegal', 'currency' => 'XOF', 'operators' => ['ORANGE', 'FREE', 'EXPRESSO']],
            'CM' => ['name' => 'Cameroon', 'currency' => 'XAF', 'operators' => ['MTN', 'ORANGE']],
            'BJ' => ['name' => 'Benin', 'currency' => 'XOF', 'operators' => ['MTN', 'MOOV']],
            'BF' => ['name' => 'Burkina Faso', 'currency' => 'XOF', 'operators' => ['ORANGE', 'MOOV']],
            'CD' => ['name' => 'DRC', 'currency' => 'CDF', 'operators' => ['VODACOM', 'AIRTEL', 'ORANGE']],
            'CG' => ['name' => 'Congo', 'currency' => 'XAF', 'operators' => ['AIRTEL', 'MTN']],
            'GA' => ['name' => 'Gabon', 'currency' => 'XAF', 'operators' => ['AIRTEL']],
            'SL' => ['name' => 'Sierra Leone', 'currency' => 'SLL', 'operators' => ['ORANGE']],
            'ET' => ['name' => 'Ethiopia', 'currency' => 'ETB', 'operators' => ['SAFARICOM']],
            'LS' => ['name' => 'Lesotho', 'currency' => 'LSL', 'operators' => ['MPESA']],
            'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'operators' => ['MTN', 'AIRTEL']]
        ];
    }
}

if (!function_exists('pawapay_log')) {
    /**
     * Log message to PawaPay log file
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    function pawapay_log(string $level, string $message, array $context = []): void
    {
        $config = PawaPayConfig::getInstance();
        $logLevel = $config->get('log_level', 'info');
        $logFile = $config->get('log_file', 'logs/pawapay.log');
        
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        if ($levels[$level] < $levels[$logLevel]) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('pawapay_debug')) {
    /**
     * Debug log helper
     */
    function pawapay_debug(string $message, array $context = []): void
    {
        pawapay_log('debug', $message, $context);
    }
}

if (!function_exists('pawapay_info')) {
    /**
     * Info log helper
     */
    function pawapay_info(string $message, array $context = []): void
    {
        pawapay_log('info', $message, $context);
    }
}

if (!function_exists('pawapay_warning')) {
    /**
     * Warning log helper
     */
    function pawapay_warning(string $message, array $context = []): void
    {
        pawapay_log('warning', $message, $context);
    }
}

if (!function_exists('pawapay_error')) {
    /**
     * Error log helper
     */
    function pawapay_error(string $message, array $context = []): void
    {
        pawapay_log('error', $message, $context);
    }
}