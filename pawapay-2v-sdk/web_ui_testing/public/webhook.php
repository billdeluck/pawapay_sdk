<?php
/**
 * ============================================================================
 * PAWAPAY WEBHOOK HANDLER FOR TESTING
 * ============================================================================
 * 
 * This webhook handler receives and processes PawaPay payment notifications
 * for the testing environment.
 * 
 * @version 2.0.0
 * @since 2024-10-17
 * ============================================================================
 */

// Load dependencies
require_once __DIR__ . '/../../vendor/autoload.php';

use PawaPay\Controller\WebhookController;
use PawaPay\Config\PawaPayConfig;

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Initialize webhook handler
$config = new PawaPayConfig();
$webhookController = new WebhookController($config);

// Handle webhook request
try {
    // Get the raw POST data
    $payload = file_get_contents('php://input');
    $headers = getallheaders();
    
    // Log webhook receipt for testing
    $logData = [
        'timestamp' => date('c'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'payload' => $payload,
        'headers' => $headers,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log to file for testing purposes
    $logFile = __DIR__ . '/../logs/webhooks.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
    // Process the webhook
    $result = $webhookController->handleWebhook($payload, $headers);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['error']]);
    }
    
} catch (\Exception $e) {
    // Log error
    error_log('Webhook Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
?>