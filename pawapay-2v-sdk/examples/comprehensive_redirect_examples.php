<?php
/**
 * PawaPay Enhanced Redirect Examples - Complete Implementation Guide
 * 
 * This file demonstrates all redirect scenarios, error handling, and reconciliation
 * features according to PawaPay documentation best practices.
 *
 * @package     Myzuwa\PawaPay\Examples
 * @version     2.0.0
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

// Initialize the enhanced facade
$paymentPage = new PaymentPageFacade($config);

echo "🚀 PawaPay Enhanced Redirect Integration Demo\n";
echo "=============================================\n\n";

// =============================================================================
// PAWAPAY DOCUMENTED USE CASES
// =============================================================================

echo "📋 PawaPay Official Use Cases\n";
echo "=============================\n\n";

// Use Case 1: Payment page for all countries
try {
    echo "🌍 Use Case 1: Universal Payment Page (All Countries)\n";
    
    $universalPayment = $paymentPage->createUniversalPaymentPage(
        'https://yoursite.com/payment/return',
        'Demo payment for any country'
    );
    
    echo "✅ Universal payment page created!\n";
    echo "🔗 Redirect URL: {$universalPayment['redirectUrl']}\n";
    echo "📊 Deposit ID: {$universalPayment['depositId']}\n";
    echo "📝 Customer can choose: Country, Phone Number, Amount\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

// Use Case 2: Fixed phone number
try {
    echo "📱 Use Case 2: Fixed Phone Number (Registered Users)\n";
    
    $fixedPhonePayment = $paymentPage->createFixedPhonePaymentPage(
        '254712345678', // Registered user's phone
        'https://yoursite.com/payment/return',
        'User subscription payment'
    );
    
    echo "✅ Fixed phone payment page created!\n";
    echo "🔗 Redirect URL: {$fixedPhonePayment['redirectUrl']}\n";
    echo "📱 Phone: 254712345678 (fixed)\n";
    echo "📝 Customer can choose: Amount only\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

// Use Case 3: Fixed amount
try {
    echo "💰 Use Case 3: Fixed Amount Payment\n";
    
    $fixedAmountPayment = $paymentPage->createFixedAmountPaymentPage(
        '100', // Fixed amount
        'KEN', // Required country for fixed amount
        'https://yoursite.com/payment/return',
        'Product purchase - KES 100'
    );
    
    echo "✅ Fixed amount payment page created!\n";
    echo "🔗 Redirect URL: {$fixedAmountPayment['redirectUrl']}\n";
    echo "💰 Amount: KES 100 (fixed)\n";
    echo "🌍 Country: Kenya (fixed)\n";
    echo "📝 Customer can choose: Phone Number only\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

// Use Case 4: Fixed amount and phone number
try {
    echo "🔒 Use Case 4: Fixed Amount & Phone (Subscription Billing)\n";
    
    $fullFixedPayment = $paymentPage->createFixedAmountAndPhonePaymentPage(
        '254712345678', // Fixed phone
        '100', // Fixed amount
        'https://yoursite.com/payment/return',
        'Monthly subscription - KES 100'
    );
    
    echo "✅ Fully fixed payment page created!\n";
    echo "🔗 Redirect URL: {$fullFixedPayment['redirectUrl']}\n";
    echo "📱 Phone: 254712345678 (fixed)\n";
    echo "💰 Amount: KES 100 (fixed)\n";
    echo "📝 Customer cannot change: Phone or Amount\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

// =============================================================================
// MODULAR SCENARIO-BASED EXAMPLES
// =============================================================================

echo "🎯 Modular Scenario-Based Payment Pages\n";
echo "======================================\n\n";

// E-commerce Checkout
try {
    echo "🛒 E-commerce Checkout Scenario\n";
    
    $ecommerce = $paymentPage->createEcommerceCheckout([
        'amount' => '250.00',
        'currency' => 'KES',
        'country' => 'KEN',
        'returnUrl' => 'https://yourstore.com/checkout/return',
        'orderId' => 'ORD-' . time(),
        'customerEmail' => 'customer@example.com'
    ]);
    
    echo "✅ E-commerce checkout created!\n";
    echo "🔗 Redirect: {$ecommerce['redirectUrl']}\n";
    echo "🛍️ Order: {$ecommerce['depositId']}\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ E-commerce Error: {$e->getMessage()}\n\n";
}

// Subscription Billing
try {
    echo "📅 Subscription Billing Scenario\n";
    
    $subscription = $paymentPage->createSubscriptionBilling([
        'amount' => '50.00',
        'msisdn' => '254712345678',
        'returnUrl' => 'https://yourapp.com/subscription/return',
        'subscriptionId' => 'SUB-' . time(),
        'billingCycle' => 'monthly'
    ]);
    
    echo "✅ Subscription billing created!\n";
    echo "🔗 Redirect: {$subscription['redirectUrl']}\n";
    echo "📅 Subscription: Monthly KES 50\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Subscription Error: {$e->getMessage()}\n\n";
}

// Wallet Top-up (Flexible Amount)
try {
    echo "💳 Wallet Top-up Scenario (Flexible Amount)\n";
    
    $walletTopup = $paymentPage->createWalletTopup([
        'returnUrl' => 'https://yourapp.com/wallet/return',
        'userId' => 'USER123',
        'country' => 'KEN' // Optional preference
    ]);
    
    echo "✅ Wallet top-up created!\n";
    echo "🔗 Redirect: {$walletTopup['redirectUrl']}\n";
    echo "💰 Amount: Customer enters amount\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Wallet Error: {$e->getMessage()}\n\n";
}

// Event Ticket Purchase
try {
    echo "🎫 Event Ticket Scenario\n";
    
    $eventTicket = $paymentPage->createEventTicket([
        'amount' => '75.00',
        'currency' => 'KES',
        'country' => 'KEN',
        'returnUrl' => 'https://events.com/ticket/return',
        'eventId' => 'EVENT-CONCERT-2024',
        'ticketType' => 'VIP',
        'attendeeName' => 'John Doe',
        'attendeeEmail' => 'john@example.com'
    ]);
    
    echo "✅ Event ticket payment created!\n";
    echo "🔗 Redirect: {$eventTicket['redirectUrl']}\n";
    echo "🎫 Ticket: VIP - KES 75\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Event Error: {$e->getMessage()}\n\n";
}

// Donation (Flexible or Fixed)
try {
    echo "❤️ Donation Scenario\n";
    
    $donation = $paymentPage->createDonation([
        'returnUrl' => 'https://charity.org/donation/return',
        'cause' => 'Children Education Fund',
        'donorName' => 'Anonymous Donor',
        'country' => 'KEN' // Optional, allows customer to set amount
    ]);
    
    echo "✅ Donation page created!\n";
    echo "🔗 Redirect: {$donation['redirectUrl']}\n";
    echo "❤️ Cause: Children Education Fund\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "❌ Donation Error: {$e->getMessage()}\n\n";
}

// =============================================================================
// ERROR HANDLING & RECOVERY DEMONSTRATIONS
// =============================================================================

echo "⚠️ Error Handling & Recovery Features\n";
echo "===================================\n\n";

// Simulate handling a failed payment
echo "🔍 Processing Failure Handling Demo\n";
$sampleFailedDepositId = 'f4401bd2-1568-4140-bf2d-failed-demo';

try {
    $failureResult = $paymentPage->handleProcessingFailure($sampleFailedDepositId);
    
    echo "✅ Failure analysis complete!\n";
    echo "📊 Status: {$failureResult['status']}\n";
    echo "❌ Failure Code: {$failureResult['failureCode']}\n";
    echo "💬 Message: {$failureResult['failureMessage']}\n";
    echo "🔄 Retry Allowed: " . ($failureResult['retryAllowed'] ? 'Yes' : 'No') . "\n";
    echo "💡 Recommendations: " . implode(', ', $failureResult['recommendedActions']) . "\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "⚠️ Demo failure (expected): {$e->getMessage()}\n\n";
}

// Demonstrate reconciliation status check
echo "🔄 Reconciliation Status Demo\n";

try {
    $reconciliationResult = $paymentPage->checkReconciliationStatus($sampleFailedDepositId);
    
    echo "✅ Reconciliation status checked!\n";
    echo "📊 Status: {$reconciliationResult['status']}\n";
    echo "💬 Message: {$reconciliationResult['message']}\n\n";
    
} catch (PaymentGatewayException $e) {
    echo "⚠️ Reconciliation demo failed (expected): {$e->getMessage()}\n\n";
}

// =============================================================================
// AUTOMATED RECONCILIATION CYCLE
// =============================================================================

echo "🤖 Automated Reconciliation Cycle\n";
echo "=================================\n\n";

try {
    echo "⏰ Running reconciliation for payments older than 15 minutes...\n";
    
    $reconciliationResults = $paymentPage->runReconciliationCycle(15);
    
    echo "✅ Reconciliation cycle completed!\n";
    echo "📊 Total Checked: {$reconciliationResults['total_checked']}\n";
    echo "✅ Found: {$reconciliationResults['found']}\n";
    echo "❌ Not Found: {$reconciliationResults['not_found']}\n";
    echo "⚠️ Errors: {$reconciliationResults['errors']}\n\n";
    
    if (!empty($reconciliationResults['details'])) {
        echo "📋 Details:\n";
        foreach ($reconciliationResults['details'] as $depositId => $detail) {
            echo "  - {$depositId}: " . json_encode($detail) . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "⚠️ Reconciliation cycle demo: {$e->getMessage()}\n\n";
}

// =============================================================================
// AVAILABLE SCENARIOS OVERVIEW
// =============================================================================

echo "📋 Available Payment Scenarios\n";
echo "=============================\n\n";

try {
    $scenarios = $paymentPage->getAvailableScenarios();
    
    foreach ($scenarios as $scenarioName => $config) {
        echo "🎯 {$config['name']} ({$scenarioName})\n";
        echo "   📝 {$config['description']}\n";
        echo "   💰 Fixed Amount: " . ($config['fixed_amount'] ? 'Yes' : 'No') . "\n";
        echo "   📱 Fixed Phone: " . ($config['fixed_phone'] ? 'Yes' : 'No') . "\n";
        echo "   📋 Required: " . implode(', ', $config['required_fields']) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "⚠️ Could not load scenarios: {$e->getMessage()}\n\n";
}

// =============================================================================
// STATISTICS & MONITORING
// =============================================================================

echo "📊 Payment Page Statistics\n";
echo "=========================\n\n";

try {
    $stats = $paymentPage->getStatistics();
    
    echo "📈 Session Statistics:\n";
    echo "  📊 Total Sessions: {$stats['total_sessions']}\n";
    echo "  🟢 Active Sessions: {$stats['active_sessions']}\n";
    echo "  🔴 Expired Sessions: {$stats['expired_sessions']}\n";
    echo "  📊 Success Rate: {$stats['success_rate']}%\n\n";
    
    // Clean up expired sessions
    $cleaned = $paymentPage->cleanExpiredSessions();
    echo "🧹 Maintenance: Cleaned {$cleaned} expired sessions\n\n";
    
} catch (Exception $e) {
    echo "⚠️ Stats unavailable: {$e->getMessage()}\n\n";
}

// =============================================================================
// INTEGRATION CODE EXAMPLES
// =============================================================================

echo "💻 Integration Code Examples\n";
echo "===========================\n\n";

echo "🔧 Basic Universal Payment Integration:\n";
echo "```php\n";
echo "<?php\n";
echo "// 1. Create universal payment page\n";
echo "\$paymentPage = new PaymentPageFacade(\$config);\n";
echo "\$result = \$paymentPage->createUniversalPaymentPage(\n";
echo "    'https://yoursite.com/return',\n";
echo "    'Order payment'\n";
echo ");\n\n";
echo "// 2. Redirect customer\n";
echo "header('Location: ' . \$result['redirectUrl']);\n";
echo "exit;\n";
echo "```\n\n";

echo "🛒 E-commerce Integration:\n";
echo "```php\n";
echo "<?php\n";
echo "// E-commerce checkout with order details\n";
echo "\$checkout = \$paymentPage->createEcommerceCheckout([\n";
echo "    'amount' => \$order->total,\n";
echo "    'currency' => \$order->currency,\n";
echo "    'country' => \$customer->country,\n";
echo "    'returnUrl' => route('checkout.return'),\n";
echo "    'orderId' => \$order->id,\n";
echo "    'customerEmail' => \$customer->email\n";
echo "]);\n\n";
echo "return redirect(\$checkout['redirectUrl']);\n";
echo "```\n\n";

echo "🔄 Return URL Handling:\n";
echo "```php\n";
echo "<?php\n";
echo "// Handle customer return from payment page\n";
echo "\$depositId = \$_GET['depositId'] ?? null;\n";
echo "\$result = \$paymentPage->handlePaymentReturn(\$depositId, \$_GET);\n\n";
echo "switch (\$result['status']) {\n";
echo "    case 'completed':\n";
echo "        // Payment successful - fulfill order\n";
echo "        \$this->fulfillOrder(\$orderId);\n";
echo "        return view('payment.success');\n";
echo "        \n";
echo "    case 'pending':\n";
echo "    case 'processing':\n";
echo "        // Show waiting page with AJAX polling\n";
echo "        return view('payment.waiting', compact('depositId'));\n";
echo "        \n";
echo "    case 'failed':\n";
echo "        // Show retry options\n";
echo "        \$failureInfo = \$paymentPage->handleProcessingFailure(\$depositId);\n";
echo "        return view('payment.failed', compact('failureInfo'));\n";
echo "        \n";
echo "    default:\n";
echo "        return view('payment.unknown');\n";
echo "}\n";
echo "```\n\n";

echo "🎣 Enhanced Webhook Handler:\n";
echo "```php\n";
echo "<?php\n";
echo "// webhook.php - Production-ready webhook handling\n";
echo "\$payload = file_get_contents('php://input');\n";
echo "\$signature = \$_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? '';\n";
echo "\$webhookData = json_decode(\$payload, true);\n\n";
echo "try {\n";
echo "    \$result = \$paymentPage->processWebhook(\$webhookData, \$signature);\n";
echo "    \n";
echo "    if (\$result['status'] === 'completed') {\n";
echo "        // Update order status\n";
echo "        Order::where('deposit_id', \$result['depositId'])\n";
echo "              ->update(['status' => 'paid', 'paid_at' => now()]);\n";
echo "        \n";
echo "        // Send confirmation email\n";
echo "        Mail::to(\$order->customer_email)->send(new PaymentConfirmation(\$order));\n";
echo "    } elseif (\$result['status'] === 'failed') {\n";
echo "        // Handle failed payment\n";
echo "        \$this->handleFailedPayment(\$result['depositId']);\n";
echo "    }\n";
echo "    \n";
echo "    http_response_code(200);\n";
echo "    echo 'OK';\n";
echo "    \n";
echo "} catch (PaymentGatewayException \$e) {\n";
echo "    error_log('Webhook error: ' . \$e->getMessage());\n";
echo "    http_response_code(400);\n";
echo "    echo 'Error: ' . \$e->getMessage();\n";
echo "}\n";
echo "```\n\n";

echo "🤖 Automated Reconciliation Cron Job:\n";
echo "```php\n";
echo "<?php\n";
echo "// reconcile-payments.php - Run every 15 minutes\n";
echo "require_once 'vendor/autoload.php';\n\n";
echo "\$paymentPage = new PaymentPageFacade(\$config);\n\n";
echo "// Reconcile payments older than 15 minutes\n";
echo "\$results = \$paymentPage->runReconciliationCycle(15);\n\n";
echo "// Log results\n";
echo "error_log('Reconciliation: ' . json_encode(\$results));\n\n";
echo "// Alert on errors\n";
echo "if (\$results['errors'] > 0) {\n";
echo "    mail('admin@yoursite.com', 'Reconciliation Errors', json_encode(\$results));\n";
echo "}\n\n";
echo "echo 'Reconciled: ' . \$results['total_checked'] . ' payments\\n';\n";
echo "```\n\n";

echo "✨ Demo completed successfully!\n";
echo "🔗 For production use:\n";
echo "   1. Replace sandbox URLs with production URLs\n";
echo "   2. Use production API credentials\n";
echo "   3. Set up proper webhook endpoints\n";
echo "   4. Implement reconciliation cron jobs\n";
echo "   5. Add proper error logging and monitoring\n\n";
echo "📚 See enhanced documentation for advanced configuration options.\n";