<?php
/**
 * PawaPay SDK Bootstrap Configuration
 *
 * This file is loaded during SDK initialization to set up
 * basic configuration and environment settings.
 */

// Define SDK root directory
if (!defined('PAWAPAY_SDK_ROOT')) {
    define('PAWAPAY_SDK_ROOT', dirname(__DIR__));
}

// Define SDK source directory
if (!defined('PAWAPAY_SDK_SRC')) {
    define('PAWAPAY_SDK_SRC', PAWAPAY_SDK_ROOT . '/src');
}

// Define configuration directory
if (!defined('PAWAPAY_SDK_CONFIG')) {
    define('PAWAPAY_SDK_CONFIG', PAWAPAY_SDK_ROOT . '/config');
}

// Set default timezone if not already set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Africa/Lusaka');
}

// Error reporting for development
if (getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

// Set up include path for easier file loading
set_include_path(get_include_path() . PATH_SEPARATOR . PAWAPAY_SDK_SRC);

// Basic environment detection
if (!defined('PAWAPAY_ENV')) {
    $env = getenv('PAWAPAY_ENVIRONMENT') ?: 'sandbox';
    define('PAWAPAY_ENV', $env);
}

// API endpoints based on environment
if (!defined('PAWAPAY_API_BASE_URL')) {
    $baseUrl = getenv('PAWAPAY_API_URL') ?: 'https://api.sandbox.pawapay.io';
    define('PAWAPAY_API_BASE_URL', $baseUrl);
}

// Webhook configuration
if (!defined('PAWAPAY_WEBHOOK_URL')) {
    $webhookUrl = getenv('PAWAPAY_WEBHOOK_URL') ?: 'http://localhost/pawapay-v2-integration/webhook/test';
    define('PAWAPAY_WEBHOOK_URL', $webhookUrl);
}

// Logging configuration
if (!defined('PAWAPAY_LOG_REQUESTS')) {
    $logRequests = getenv('PAWAPAY_LOG_REQUEST') === 'true';
    define('PAWAPAY_LOG_REQUESTS', $logRequests);
}

if (!defined('PAWAPAY_LOG_RESPONSES')) {
    $logResponses = getenv('PAWAPAY_LOG_RESPONSE') === 'true';
    define('PAWAPAY_LOG_RESPONSES', $logResponses);
}

// SSL verification setting
if (!defined('PAWAPAY_SSL_VERIFY')) {
    $sslVerify = getenv('SSL_VERIFY') !== 'false';
    define('PAWAPAY_SSL_VERIFY', $sslVerify);
}

// Application settings
if (!defined('APP_URL')) {
    $appUrl = getenv('APP_URL') ?: 'http://localhost/pawapay-v2-integration';
    define('APP_URL', $appUrl);
}

if (!defined('APP_ENV')) {
    $appEnv = getenv('APP_ENV') ?: 'development';
    define('APP_ENV', $appEnv);
}

if (!defined('APP_DEBUG')) {
    $appDebug = getenv('APP_DEBUG') === 'true';
    define('APP_DEBUG', $appDebug);
}

// Database configuration (if needed for testing)
if (!defined('DB_HOST')) {
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    define('DB_HOST', $dbHost);
}

if (!defined('DB_PORT')) {
    $dbPort = getenv('DB_PORT') ?: '3306';
    define('DB_PORT', $dbPort);
}

if (!defined('DB_DATABASE')) {
    $dbDatabase = getenv('DB_DATABASE') ?: 'pawapay_test';
    define('DB_DATABASE', $dbDatabase);
}

if (!defined('DB_USERNAME')) {
    $dbUsername = getenv('DB_USERNAME') ?: 'root';
    define('DB_USERNAME', $dbUsername);
}

if (!defined('DB_PASSWORD')) {
    $dbPassword = getenv('DB_PASSWORD') ?: '';
    define('DB_PASSWORD', $dbPassword);
}

// Log file path
if (!defined('LOG_PATH')) {
    $logPath = getenv('LOG_PATH') ?: 'storage/logs/pawapay.log';
    define('LOG_PATH', $logPath);
}

// Ensure log directory exists
$logDir = dirname(LOG_PATH);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Test phone numbers for different scenarios
if (!defined('AIRTEL_SUCCESS_PHONE')) {
    define('AIRTEL_SUCCESS_PHONE', getenv('AIRTEL_SUCCESS_PHONE') ?: '260973456789');
}

if (!defined('MTN_SUCCESS_PHONE')) {
    define('MTN_SUCCESS_PHONE', getenv('MTN_SUCCESS_PHONE') ?: '260763456789');
}

if (!defined('ZAMTEL_SUCCESS_PHONE')) {
    define('ZAMTEL_SUCCESS_PHONE', getenv('ZAMTEL_SUCCESS_PHONE') ?: '260953456700');
}

// Initialize basic logging if debug is enabled
if (APP_DEBUG && PAWAPAY_LOG_REQUESTS) {
    // Simple file logger for debugging
    function log_message($level, $message) {
        $logEntry = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        file_put_contents(LOG_PATH, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Set up basic exception handler for testing
if (APP_DEBUG) {
    set_exception_handler(function($exception) {
        echo "<h1>SDK Error</h1>";
        echo "<p><strong>" . get_class($exception) . "</strong>: " . $exception->getMessage() . "</p>";
        echo "<p>File: " . $exception->getFile() . " (Line " . $exception->getLine() . ")</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        exit(1);
    });
}
