<?php

/**
 * ============================================================================
 * PAWAPAY SDK - TEST BOOTSTRAP
 * ============================================================================
 * 
 * Bootstrap file for PHPUnit tests. Sets up the testing environment,
 * loads configuration, and prepares for real API testing.
 * 
 * @author PawaPay SDK Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

// Ensure we're running from the correct directory
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "❌ Composer dependencies not installed. Run: composer install\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment variables
$envFiles = [
    __DIR__ . '/../.env.test',
    __DIR__ . '/../.env',
    getcwd() . '/.env.test',
    getcwd() . '/.env'
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname($envFile), basename($envFile));
        $dotenv->safeLoad();
        break;
    }
}

// Ensure test environment
$_ENV['PAWAPAY_ENVIRONMENT'] = 'sandbox';
$_ENV['PAWAPAY_TEST_MODE'] = 'true';
$_ENV['PAWAPAY_DEBUG_MODE'] = 'true';

// Create test logs directory
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Create test reports directory
$reportsDir = __DIR__ . '/reports';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}

// Display test environment information
echo "🧪 PawaPay SDK Test Environment\n";
echo "==============================\n";
echo "Environment: " . ($_ENV['PAWAPAY_ENVIRONMENT'] ?? 'sandbox') . "\n";
echo "Test Mode: " . ($_ENV['PAWAPAY_TEST_MODE'] ?? 'true') . "\n";
echo "Debug Mode: " . ($_ENV['PAWAPAY_DEBUG_MODE'] ?? 'false') . "\n";
echo "API Token: " . (empty($_ENV['PAWAPAY_API_TOKEN']) ? '❌ NOT SET' : '✅ SET') . "\n";
echo "Webhook Secret: " . (empty($_ENV['PAWAPAY_WEBHOOK_SECRET']) ? '❌ NOT SET' : '✅ SET') . "\n";
echo "\n";

if (empty($_ENV['PAWAPAY_API_TOKEN'])) {
    echo "⚠️  WARNING: PAWAPAY_API_TOKEN not set. Real API tests will be skipped.\n";
    echo "   Set your sandbox API token in .env file to run complete integration tests.\n\n";
}

if (empty($_ENV['PAWAPAY_WEBHOOK_SECRET'])) {
    echo "⚠️  WARNING: PAWAPAY_WEBHOOK_SECRET not set. Webhook tests may be limited.\n\n";
}

// Helper function for test data
function getTestDepositId(string $prefix = 'TEST'): string
{
    return $prefix . '_' . strtoupper(bin2hex(random_bytes(4))) . '_' . time();
}

// Helper function for test amounts
function getTestAmounts(): array
{
    $amounts = $_ENV['PAWAPAY_TEST_AMOUNTS'] ?? '10,25,50,100,500';
    return array_map('floatval', explode(',', $amounts));
}

// Helper function for test currencies
function getTestCurrencies(): array
{
    $currencies = $_ENV['PAWAPAY_TEST_CURRENCIES'] ?? 'USD,KES,UGX';
    return explode(',', $currencies);
}

// Helper function for test phone numbers
function getTestPhones(): array
{
    $phones = $_ENV['PAWAPAY_TEST_PHONES'] ?? '+256700000001,+256700000002';
    return explode(',', $phones);
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set($_ENV['PAWAPAY_TIMEZONE'] ?? 'UTC');

echo "🚀 Ready to run PawaPay SDK tests!\n\n";