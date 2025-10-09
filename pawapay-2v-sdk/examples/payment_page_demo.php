<?php
/**
 * PawaPay Payment Page Demo
 * 
 * Complete demonstration of payment page redirect functionality
 * Shows how to integrate PawaPay payment pages into your application
 *
 * @package     Myzuwa\PawaPay\Examples
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Myzuwa\PawaPay\PaymentPageFacade;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

// Configuration - replace with your actual credentials
$config = [
    'api' => [
        'token' => 'your_pawapay_api_token_here',
        'base_url' => 'https://api.sandbox.pawapay.io' // Use https://api.pawapay.io for production
    ],
    'webhook_secret' => 'your_webhook_secret_here',
    'environment' => 'sandbox' // or 'production'
];

// Initialize the facade
$paymentPage = new PaymentPageFacade($config);

echo "🚀 PawaPay Payment Page Demo\n";
echo "============================\n\n";

// Example 1: Create a fixed amount payment page
try {
    echo "📄 Creating fixed amount payment page...\n";
    
    $paymentUrl = $paymentPage->createPaymentRedirect([
        'amount' => 100.00,
        'currency' => 'KES',
        'description' => 'Festival Ticket Purchase',
        'returnUrl' => 'https://yoursite.com/payment/return',
        'customerPhone' => '254712345678',
        'country' => 'KEN',
        'orderId' => 'ORD-' . time(),
        'customerEmail' => 'customer@example.com'
    ]);
    
    echo "✅ Payment page created successfully!\n";
    echo "🔗 Redirect URL: {$paymentUrl}\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error creating payment page: {$e->getMessage()}\n\n";
}

// Example 2: Create a flexible payment page (customer enters amount)
try {
    echo "💰 Creating flexible payment page...\n";
    
    $flexiblePayment = $paymentPage->createFlexiblePayment(
        'Wallet Top-up',
        'https://yoursite.com/wallet/return',
        [
            'customerPhone' => '254712345678',
            'country' => 'KEN'
        ]
    );
    
    echo "✅ Flexible payment page created!\n";
    echo "🔗 Redirect URL: {$flexiblePayment['redirectUrl']}\n";
    echo "📊 Type: Flexible (customer enters amount)\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error creating flexible payment: {$e->getMessage()}\n\n";
}

// Example 3: Simulate handling a return from payment page
echo "🔄 Simulating payment return handling...\n";

// In a real application, this would be called when customer returns from payment page
$sampleDepositId = 'f4401bd2-1568-4140-bf2d-eb77d2b2b639';
$sampleQueryParams = [
    'status' => 'completed',
    'token' => 'security_token_here'
];

try {
    // Note: This will fail in demo since we don't have a real payment session
    // In production, this would work after creating a real payment page
    $returnResult = $paymentPage->handlePaymentReturn($sampleDepositId, $sampleQueryParams);
    
    echo "✅ Payment return handled successfully!\n";
    echo "📊 Status: {$returnResult['status']}\n";
    echo "💬 Message: {$returnResult['message']}\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "⚠️ Return handling demo failed (expected in demo): {$e->getMessage()}\n\n";
}

// Example 4: Check payment status
echo "🔍 Demonstrating payment status check...\n";

try {
    $statusResult = $paymentPage->checkPaymentStatus($sampleDepositId);
    
    echo "✅ Status check completed!\n";
    echo "📊 Current status: {$statusResult['status']}\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "⚠️ Status check demo failed (expected in demo): {$e->getMessage()}\n\n";
}

// Example 5: Webhook processing
echo "🎣 Demonstrating webhook processing...\n";

$sampleWebhookData = [
    'type' => 'deposit.completed',
    'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
    'status' => 'COMPLETED',
    'amount' => '100.00',
    'currency' => 'KES',
    'payer' => [
        'type' => 'MMO',
        'accountDetails' => [
            'phoneNumber' => '254712345678',
            'provider' => 'MPESA_KE'
        ]
    ],
    'metadata' => [
        ['orderId' => 'ORD-123456'],
        ['customerEmail' => 'customer@example.com', 'isPII' => true]
    ]
];

// Generate valid signature for demo
$payload = json_encode($sampleWebhookData);
$signature = hash_hmac('sha256', $payload, $config['webhook_secret']);

try {
    $webhookResult = $paymentPage->processWebhook($sampleWebhookData, $signature);
    
    echo "✅ Webhook processed successfully!\n";
    echo "📊 Deposit ID: {$webhookResult['depositId']}\n";
    echo "📊 Status: {$webhookResult['status']}\n";
    echo "📊 Is Payment Page: " . ($webhookResult['isPaymentPage'] ? 'Yes' : 'No') . "\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Webhook processing failed: {$e->getMessage()}\n\n";
}

// Example 6: Statistics and cleanup
echo "📊 Payment page statistics...\n";

try {
    $stats = $paymentPage->getStatistics();
    
    echo "📈 Total sessions: {$stats['total_sessions']}\n";
    echo "🟢 Active sessions: {$stats['active_sessions']}\n";
    echo "🔴 Expired sessions: {$stats['expired_sessions']}\n";
    echo "📊 Success rate: {$stats['success_rate']}%\n\n";
    
    // Clean up expired sessions
    $cleaned = $paymentPage->cleanExpiredSessions();
    echo "🧹 Cleaned {$cleaned} expired sessions\n\n";
    
} catch (Exception $e) {
    echo "⚠️ Stats unavailable: {$e->getMessage()}\n\n";
}

// Integration Examples
echo "🔧 Integration Code Examples\n";
echo "============================\n\n";

echo "💻 Basic Integration (PHP):\n";
echo "```php\n";
echo "<?php\n";
echo "// 1. Create payment redirect\n";
echo "\$paymentPage = new PaymentPageFacade(\$config);\n";
echo "\$redirectUrl = \$paymentPage->createPaymentRedirect([\n";
echo "    'amount' => 100.00,\n";
echo "    'currency' => 'KES',\n";
echo "    'description' => 'Order Payment',\n";
echo "    'returnUrl' => 'https://yoursite.com/return',\n";
echo "    'orderId' => 'ORD-123'\n";
echo "]);\n\n";
echo "// 2. Redirect customer\n";
echo "header('Location: ' . \$redirectUrl);\n\n";
echo "// 3. Handle return (on your return URL)\n";
echo "\$depositId = \$_GET['depositId'] ?? null;\n";
echo "\$result = \$paymentPage->handlePaymentReturn(\$depositId, \$_GET);\n\n";
echo "if (\$result['status'] === 'completed') {\n";
echo "    // Payment successful - fulfill order\n";
echo "    echo 'Payment successful!';\n";
echo "} else {\n";
echo "    // Handle other statuses\n";
echo "    echo 'Payment status: ' . \$result['status'];\n";
echo "}\n";
echo "```\n\n";

echo "🌐 JavaScript Integration:\n";
echo "```javascript\n";
echo "// Redirect to payment page\n";
echo "function initiatePayment() {\n";
echo "    // Get redirect URL from your backend\n";
echo "    fetch('/api/create-payment', {\n";
echo "        method: 'POST',\n";
echo "        body: JSON.stringify({\n";
echo "            amount: 100.00,\n";
echo "            currency: 'KES',\n";
echo "            description: 'Order Payment'\n";
echo "        })\n";
echo "    })\n";
echo "    .then(response => response.json())\n";
echo "    .then(data => {\n";
echo "        window.location.href = data.redirectUrl;\n";
echo "    });\n";
echo "}\n\n";
echo "// Poll payment status (if needed)\n";
echo "function checkPaymentStatus(depositId) {\n";
echo "    fetch(`/api/payment-status/\${depositId}`)\n";
echo "    .then(response => response.json())\n";
echo "    .then(data => {\n";
echo "        if (data.status === 'completed') {\n";
echo "            showSuccessMessage();\n";
echo "        } else if (['pending', 'processing'].includes(data.status)) {\n";
echo "            setTimeout(() => checkPaymentStatus(depositId), 5000);\n";
echo "        }\n";
echo "    });\n";
echo "}\n";
echo "```\n\n";

echo "🎣 Webhook Handler:\n";
echo "```php\n";
echo "<?php\n";
echo "// webhook.php - Handle PawaPay webhooks\n";
echo "\$payload = file_get_contents('php://input');\n";
echo "\$signature = \$_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? '';\n";
echo "\$webhookData = json_decode(\$payload, true);\n\n";
echo "try {\n";
echo "    \$result = \$paymentPage->processWebhook(\$webhookData, \$signature);\n";
echo "    \n";
echo "    if (\$result['status'] === 'completed') {\n";
echo "        // Update order status in database\n";
echo "        updateOrderStatus(\$result['depositId'], 'paid');\n";
echo "    }\n";
echo "    \n";
echo "    http_response_code(200);\n";
echo "    echo 'OK';\n";
echo "} catch (PaymentGatewayException \$e) {\n";
echo "    http_response_code(400);\n";
echo "    echo 'Error: ' . \$e->getMessage();\n";
echo "}\n";
echo "```\n\n";

echo "✨ Demo completed! Check the examples above for integration guidance.\n";
echo "🔗 For production use, replace sandbox URLs and credentials with production values.\n";
echo "📚 See documentation for more advanced features and configuration options.\n";