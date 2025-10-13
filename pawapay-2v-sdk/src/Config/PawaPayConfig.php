<?php

/**
 * ============================================================================
 * PAWAPAY SDK CONFIGURATION MANAGER
 * ============================================================================
 * 
 * This class handles loading and managing all PawaPay SDK configuration
 * from .env files, with support for environment overrides and validation.
 * 
 * Features:
 * - Automatic .env file loading
 * - Environment variable validation
 * - Type conversion and defaults
 * - Configuration caching
 * - Development vs Production settings
 * 
 * @author PawaPay SDK Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

namespace PawaPay\Config;

use Dotenv\Dotenv;
use InvalidArgumentException;

class PawaPayConfig
{
    private static $instance = null;
    private $config = [];
    private $loaded = false;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->loadConfiguration();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load configuration from .env file and environment variables
     */
    private function loadConfiguration(): void
    {
        if ($this->loaded) {
            return;
        }
        
        // Try to load .env file
        $this->loadEnvFile();
        
        // Load all configuration values with defaults
        $this->config = [
            // API Configuration
            'api_token' => $this->getEnv('PAWAPAY_API_TOKEN', ''),
            'webhook_secret' => $this->getEnv('PAWAPAY_WEBHOOK_SECRET', ''),
            'environment' => $this->getEnv('PAWAPAY_ENVIRONMENT', 'sandbox'),
            'sandbox_url' => $this->getEnv('PAWAPAY_SANDBOX_URL', 'https://api.sandbox.pawapay.io'),
            'production_url' => $this->getEnv('PAWAPAY_PRODUCTION_URL', 'https://api.pawapay.io'),
            
            // Fee Configuration
            'fee_override_percentage' => $this->parseFloat($this->getEnv('PAWAPAY_FEE_OVERRIDE_PERCENTAGE')),
            'apply_fees_to_amount' => $this->parseBool($this->getEnv('PAWAPAY_APPLY_FEES_TO_AMOUNT', 'true')),
            'fee_precision' => $this->parseInt($this->getEnv('PAWAPAY_FEE_PRECISION', '2')),
            'minimum_fee' => $this->parseFloat($this->getEnv('PAWAPAY_MINIMUM_FEE')),
            'maximum_fee' => $this->parseFloat($this->getEnv('PAWAPAY_MAXIMUM_FEE')),
            'default_country' => $this->getEnv('PAWAPAY_DEFAULT_COUNTRY', 'KE'),
            'default_operator' => $this->getEnv('PAWAPAY_DEFAULT_OPERATOR', 'MPESA'),
            
            // Webhook Configuration
            'webhook_url' => $this->getEnv('PAWAPAY_WEBHOOK_URL', ''),
            'webhook_retry_attempts' => $this->parseInt($this->getEnv('PAWAPAY_WEBHOOK_RETRY_ATTEMPTS', '3')),
            'webhook_timeout' => $this->parseInt($this->getEnv('PAWAPAY_WEBHOOK_TIMEOUT', '30')),
            
            // Redirect Configuration
            'success_url' => $this->getEnv('PAWAPAY_SUCCESS_URL', ''),
            'failure_url' => $this->getEnv('PAWAPAY_FAILURE_URL', ''),
            'cancel_url' => $this->getEnv('PAWAPAY_CANCEL_URL', ''),
            
            // Reconciliation Settings
            'reconciliation_enabled' => $this->parseBool($this->getEnv('PAWAPAY_RECONCILIATION_ENABLED', 'true')),
            'reconciliation_interval' => $this->parseInt($this->getEnv('PAWAPAY_RECONCILIATION_INTERVAL', '15')),
            'reconciliation_max_attempts' => $this->parseInt($this->getEnv('PAWAPAY_RECONCILIATION_MAX_ATTEMPTS', '5')),
            'reconciliation_batch_size' => $this->parseInt($this->getEnv('PAWAPAY_RECONCILIATION_BATCH_SIZE', '100')),
            
            // Logging Configuration
            'log_level' => $this->getEnv('PAWAPAY_LOG_LEVEL', 'info'),
            'log_file' => $this->getEnv('PAWAPAY_LOG_FILE', 'logs/pawapay.log'),
            'debug_mode' => $this->parseBool($this->getEnv('PAWAPAY_DEBUG_MODE', 'false')),
            'log_api_requests' => $this->parseBool($this->getEnv('PAWAPAY_LOG_API_REQUESTS', 'false')),
            
            // Timeout Settings
            'request_timeout' => $this->parseInt($this->getEnv('PAWAPAY_REQUEST_TIMEOUT', '30')),
            'connection_timeout' => $this->parseInt($this->getEnv('PAWAPAY_CONNECTION_TIMEOUT', '10')),
            
            // Caching Configuration
            'cache_fees' => $this->parseBool($this->getEnv('PAWAPAY_CACHE_FEES', 'true')),
            'cache_duration' => $this->parseInt($this->getEnv('PAWAPAY_CACHE_DURATION', '3600')),
            'cache_driver' => $this->getEnv('PAWAPAY_CACHE_DRIVER', 'file'),
            
            // Security Settings
            'rate_limiting' => $this->parseBool($this->getEnv('PAWAPAY_RATE_LIMITING', 'true')),
            'rate_limit_rpm' => $this->parseInt($this->getEnv('PAWAPAY_RATE_LIMIT_RPM', '60')),
            'webhook_ip_whitelist' => $this->parseArray($this->getEnv('PAWAPAY_WEBHOOK_IP_WHITELIST', '')),
            
            // Database Configuration
            'db_host' => $this->getEnv('DB_HOST', 'localhost'),
            'db_port' => $this->parseInt($this->getEnv('DB_PORT', '3306')),
            'db_database' => $this->getEnv('DB_DATABASE', ''),
            'db_username' => $this->getEnv('DB_USERNAME', ''),
            'db_password' => $this->getEnv('DB_PASSWORD', ''),
            'db_charset' => $this->getEnv('DB_CHARSET', 'utf8mb4'),
            'db_table_prefix' => $this->getEnv('DB_TABLE_PREFIX', ''),
            
            // Testing Configuration
            'test_mode' => $this->parseBool($this->getEnv('PAWAPAY_TEST_MODE', 'false')),
            'test_phones' => $this->parseArray($this->getEnv('PAWAPAY_TEST_PHONES', '+256700000001')),
            'test_amounts' => $this->parseNumberArray($this->getEnv('PAWAPAY_TEST_AMOUNTS', '100,500,1000')),
            'test_currencies' => $this->parseArray($this->getEnv('PAWAPAY_TEST_CURRENCIES', 'USD,KES,UGX')),
            'test_skip_ssl' => $this->parseBool($this->getEnv('PAWAPAY_TEST_SKIP_SSL', 'false')),
            
            // Modesy Integration
            'modesy_integration' => $this->parseBool($this->getEnv('PAWAPAY_MODESY_INTEGRATION', 'false')),
            'modesy_base_url' => $this->getEnv('MODESY_BASE_URL', ''),
            'default_commission_rate' => $this->parseFloat($this->getEnv('PAWAPAY_DEFAULT_COMMISSION_RATE', '0.05')),
            'multi_vendor_enabled' => $this->parseBool($this->getEnv('PAWAPAY_MULTI_VENDOR_ENABLED', 'true')),
            'commission_method' => $this->getEnv('PAWAPAY_COMMISSION_METHOD', 'per_vendor'),
            
            // Advanced Settings
            'user_agent' => $this->getEnv('PAWAPAY_USER_AGENT', 'PawaPay-PHP-SDK/2.0'),
            'enable_request_signing' => $this->parseBool($this->getEnv('PAWAPAY_ENABLE_REQUEST_SIGNING', 'true')),
            'timezone' => $this->getEnv('PAWAPAY_TIMEZONE', 'UTC'),
            'default_currency' => $this->getEnv('PAWAPAY_DEFAULT_CURRENCY', 'USD'),
            'currency_decimals' => $this->parseInt($this->getEnv('PAWAPAY_CURRENCY_DECIMALS', '2')),
            'auto_retry' => $this->parseBool($this->getEnv('PAWAPAY_AUTO_RETRY', 'true')),
            'retry_attempts' => $this->parseInt($this->getEnv('PAWAPAY_RETRY_ATTEMPTS', '3')),
            'retry_delay' => $this->parseInt($this->getEnv('PAWAPAY_RETRY_DELAY', '1')),
            
            // Notification Settings
            'email_notifications' => $this->parseBool($this->getEnv('PAWAPAY_EMAIL_NOTIFICATIONS', 'false')),
            'notification_email' => $this->getEnv('PAWAPAY_NOTIFICATION_EMAIL', ''),
            'slack_webhook' => $this->getEnv('PAWAPAY_SLACK_WEBHOOK', ''),
            'sms_notifications' => $this->parseBool($this->getEnv('PAWAPAY_SMS_NOTIFICATIONS', 'false'))
        ];
        
        // Validate critical configuration
        $this->validateConfiguration();
        
        $this->loaded = true;
    }
    
    /**
     * Load .env file if it exists
     */
    private function loadEnvFile(): void
    {
        $envPaths = [
            getcwd(), // Current working directory
            __DIR__ . '/../../', // SDK root directory
            dirname(dirname(__DIR__)) . '/../', // Project root
        ];
        
        foreach ($envPaths as $path) {
            if (file_exists($path . '/.env')) {
                try {
                    $dotenv = Dotenv::createImmutable($path);
                    $dotenv->safeLoad();
                    break;
                } catch (\Exception $e) {
                    // Continue to next path if loading fails
                    continue;
                }
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded) {
            $this->loadConfiguration();
        }
        
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get all configuration
     */
    public function all(): array
    {
        if (!$this->loaded) {
            $this->loadConfiguration();
        }
        
        return $this->config;
    }
    
    /**
     * Get API URL based on environment
     */
    public function getApiUrl(): string
    {
        $environment = $this->get('environment');
        
        if ($environment === 'production') {
            return $this->get('production_url');
        }
        
        return $this->get('sandbox_url');
    }
    
    /**
     * Check if we're in test mode
     */
    public function isTestMode(): bool
    {
        return $this->get('test_mode') || $this->get('environment') === 'sandbox';
    }
    
    /**
     * Get database configuration as array
     */
    public function getDatabaseConfig(): array
    {
        return [
            'host' => $this->get('db_host'),
            'port' => $this->get('db_port'),
            'database' => $this->get('db_database'),
            'username' => $this->get('db_username'),
            'password' => $this->get('db_password'),
            'charset' => $this->get('db_charset'),
            'table_prefix' => $this->get('db_table_prefix')
        ];
    }
    
    /**
     * Get fee calculator configuration
     */
    public function getFeeCalculatorConfig(): array
    {
        return [
            'fee_override_percentage' => $this->get('fee_override_percentage'),
            'apply_to_amount' => $this->get('apply_fees_to_amount'),
            'round_precision' => $this->get('fee_precision'),
            'minimum_fee' => $this->get('minimum_fee'),
            'maximum_fee' => $this->get('maximum_fee'),
            'debug_mode' => $this->get('debug_mode')
        ];
    }
    
    /**
     * Helper method to get environment variable
     */
    private function getEnv(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Parse boolean values
     */
    private function parseBool(string $value): bool
    {
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }
    
    /**
     * Parse integer values
     */
    private function parseInt(string $value): ?int
    {
        if (empty($value)) {
            return null;
        }
        
        return (int) $value;
    }
    
    /**
     * Parse float values
     */
    private function parseFloat(string $value): ?float
    {
        if (empty($value)) {
            return null;
        }
        
        return (float) $value;
    }
    
    /**
     * Parse comma-separated values into array
     */
    private function parseArray(string $value): array
    {
        if (empty($value)) {
            return [];
        }
        
        return array_map('trim', explode(',', $value));
    }
    
    /**
     * Parse comma-separated numbers into array
     */
    private function parseNumberArray(string $value): array
    {
        $array = $this->parseArray($value);
        return array_map('floatval', $array);
    }
    
    /**
     * Validate critical configuration values
     */
    private function validateConfiguration(): void
    {
        $errors = [];
        
        // Check API token in production
        if ($this->config['environment'] === 'production' && empty($this->config['api_token'])) {
            $errors[] = 'PAWAPAY_API_TOKEN is required in production environment';
        }
        
        // Check webhook secret
        if (empty($this->config['webhook_secret']) && $this->config['environment'] === 'production') {
            $errors[] = 'PAWAPAY_WEBHOOK_SECRET is required for webhook verification';
        }
        
        // Validate environment
        if (!in_array($this->config['environment'], ['sandbox', 'production'])) {
            $errors[] = 'PAWAPAY_ENVIRONMENT must be either "sandbox" or "production"';
        }
        
        // Validate URLs
        if (!empty($this->config['webhook_url']) && !filter_var($this->config['webhook_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'PAWAPAY_WEBHOOK_URL must be a valid URL';
        }
        
        // Validate fee override percentage
        if ($this->config['fee_override_percentage'] !== null) {
            if ($this->config['fee_override_percentage'] < 0 || $this->config['fee_override_percentage'] > 1) {
                $errors[] = 'PAWAPAY_FEE_OVERRIDE_PERCENTAGE must be between 0 and 1';
            }
        }
        
        if (!empty($errors)) {
            throw new InvalidArgumentException('Configuration validation failed: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Set configuration value (for testing)
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }
    
    /**
     * Reset configuration (for testing)
     */
    public function reset(): void
    {
        $this->config = [];
        $this->loaded = false;
        self::$instance = null;
    }
}