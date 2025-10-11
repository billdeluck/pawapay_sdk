# 🚀 Modesy Marketplace - PawaPay Integration Guide

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/pawapay/pawapay-2v-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **Complete integration guide for implementing PawaPay mobile money payments in Modesy marketplace platform with comprehensive multi-vendor support, automated reconciliation, and advanced error handling.**

## 📋 Table of Contents

- [🎯 Overview](#-overview)
- [✨ Features](#-features) 
- [📦 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [🏗️ Implementation](#️-implementation)
- [🧪 Testing](#-testing)
- [🔧 Troubleshooting](#-troubleshooting)
- [📚 API Reference](#-api-reference)
- [🤝 Support](#-support)

## 🎯 Overview

This integration provides a complete PawaPay payment solution for Modesy marketplace platforms, supporting:

- **Multi-vendor transactions** with automatic commission calculations
- **All payment scenarios** (e-commerce, subscriptions, digital downloads, donations)
- **PawaPay redirect functionality** with all 4 official use cases
- **Network error handling** and automated reconciliation
- **HMAC-SHA256 webhook verification** for security
- **Mobile money integration** for MTN, Airtel, M-Pesa, and more

## ✨ Features

### 🏪 Marketplace Support
- ✅ **Multi-vendor order processing** with individual vendor transactions
- ✅ **Hierarchical commission calculations** with configurable rates  
- ✅ **Vendor-specific payment routing** and reconciliation
- ✅ **Order splitting** across multiple vendors automatically

### 💳 Payment Processing  
- ✅ **PawaPay redirect integration** (all 4 official use cases)
- ✅ **Mobile money payments** (MTN, Airtel, M-Pesa, etc.)
- ✅ **Real-time payment status** tracking and updates
- ✅ **Automatic payment confirmation** via webhooks

### 🔄 Advanced Features
- ✅ **Network error recovery** with intelligent retry mechanisms
- ✅ **Automated reconciliation** for payments older than 15 minutes  
- ✅ **Webhook signature verification** (HMAC-SHA256)
- ✅ **Transaction logging** and audit trails
- ✅ **CSRF bypass configuration** for webhook endpoints

### 🛡️ Security & Reliability
- ✅ **Secure webhook processing** with signature validation
- ✅ **Database transaction integrity** with rollback support
- ✅ **Input validation** and sanitization
- ✅ **Error handling** with comprehensive logging

## 📦 Installation

### Prerequisites

- **PHP 7.4+** with required extensions
- **Modesy marketplace platform** (any version)
- **MySQL/MariaDB database** 
- **PawaPay account** with API credentials
- **SSL certificate** for webhook endpoints

### Step 1: Download Integration Files

```bash
# Clone or download the PawaPay 2V SDK
git clone https://github.com/pawapay/pawapay-2v-sdk.git
cd pawapay-2v-sdk/modesy_integration
```

### Step 2: Install Database Schema

```bash
# Run the database migration
mysql -u your_username -p your_database < database_migration.sql

# Verify installation
mysql -u your_username -p your_database -e "SHOW TABLES LIKE '%payment%'"
```

### Step 3: Copy Integration Files

```php
// Copy files to your Modesy installation
cp modesy_integration/ModesyIntegrationService.php /path/to/modesy/app/Services/
cp modesy_integration/_pawapay.php /path/to/modesy/resources/views/payment/methods/
cp modesy_integration/CheckoutControllerMethods.php /path/to/modesy/app/Http/Controllers/
cp modesy_integration/DatabaseConfig.php /path/to/modesy/app/Services/
```

## ⚙️ Configuration

### Database Configuration

1. **Update Payment Gateway Settings**

```sql
-- Configure PawaPay in payment_gateways table
UPDATE payment_gateways SET
    public_key = 'your_pawapay_api_token',
    secret_key = 'your_webhook_secret', 
    environment = 'sandbox', -- or 'production'
    webhook_url = 'https://yourdomain.com/pawapay/webhook',
    redirect_urls = '{"success_url":"https://yourdomain.com/pawapay/callback/{transactionId}","cancel_url":"https://yourdomain.com/checkout?cancelled=1","failure_url":"https://yourdomain.com/checkout?error=payment_failed"}'
WHERE payment_option = 'pawapay';
```

### CSRF Bypass Setup

2. **Configure CSRF Exclusions**

```php
// For Laravel-based Modesy (App/Http/Middleware/VerifyCsrfToken.php)
protected $except = [
    'pawapay/webhook',
    'pawapay/webhook/*',
    'payment/pawapay/webhook',
    'pawapay/callback/*'
];

// For CodeIgniter-based Modesy (Config/Security.php)  
public $csrfExcludeURIs = [
    'pawapay/webhook',
    'payment/pawapay/webhook'
];
```

### Route Configuration

3. **Set up Routes**

```php
// Laravel routes (web.php)
Route::post('checkout/pawapay/initiate', 'CheckoutController@initiatePawaPayPayment')
    ->name('pawapay.initiate')->middleware('auth');
    
Route::get('pawapay/callback/{transactionId}', 'CheckoutController@completePawaPayPayment')
    ->name('pawapay.callback');

// API routes (api.php)  
Route::post('pawapay/webhook', 'CheckoutController@handlePawaPayWebhook')
    ->name('pawapay.webhook');
```

## 🏗️ Implementation

### Step 1: Initialize Services

```php
<?php
use PawaPay\Service\ModesyIntegrationService;
use PawaPay\Service\DatabaseConfig;

// Initialize database configuration
$dbConfig = new DatabaseConfig([
    'host' => env('DB_HOST'),
    'database' => env('DB_DATABASE'), 
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD')
]);

// Initialize Modesy integration service
$pawaPayService = new ModesyIntegrationService($dbConfig);
```

### Step 2: Add Controller Methods

```php
<?php
// In your CheckoutController class

/**
 * Initiate PawaPay payment for Modesy order
 */
public function initiatePawaPayPayment(Request $request)
{
    try {
        // Get order data from checkout session
        $orderData = $this->prepareOrderData($request);
        
        // Create Modesy transaction
        $transaction = $this->pawaPayService->createModesyTransaction($orderData);
        
        // Initialize PawaPay redirect
        $redirectData = $this->paymentFacade->createPaymentPageRedirect([
            'deposit_id' => $transaction['transaction_id'],
            'amount' => $orderData['totals']['total'],
            'currency' => $orderData['currency'],
            'description' => 'Order #' . $orderData['order_id'],
            'payer_msisdn' => $orderData['customer']['phone'],
            'payer_email' => $orderData['customer']['email'],
            'success_url' => route('pawapay.callback', ['transactionId' => $transaction['transaction_id']]),
            'cancel_url' => route('checkout') . '?cancelled=1',
            'failure_url' => route('checkout') . '?error=payment_failed'
        ]);
        
        return redirect($redirectData['redirect_url']);
        
    } catch (Exception $e) {
        return redirect()->route('checkout')->with('error', 'Payment initiation failed: ' . $e->getMessage());
    }
}

/**
 * Handle PawaPay payment completion callback
 */
public function completePawaPayPayment($transactionId, Request $request)
{
    try {
        // Process the payment callback
        $result = $this->pawaPayService->handlePaymentCallback([
            'deposit_id' => $transactionId,
            'status' => $request->get('status'),
            'payer_msisdn' => $request->get('payer_msisdn'),
            'amount' => $request->get('amount'),
            'currency' => $request->get('currency')
        ]);
        
        if ($result['success']) {
            // Update order status using Modesy's handlePayment method
            $paymentResult = $this->handlePayment($result['order_id'], $transactionId, 'completed');
            
            if ($paymentResult) {
                return redirect()->route('order.success', ['orderId' => $result['order_id']])
                    ->with('success', 'Payment completed successfully!');
            }
        }
        
        return redirect()->route('checkout')->with('error', 'Payment verification failed');
        
    } catch (Exception $e) {
        return redirect()->route('checkout')->with('error', 'Payment processing failed: ' . $e->getMessage());
    }
}

/**
 * Handle PawaPay webhooks for payment notifications
 */
public function handlePawaPayWebhook(Request $request)
{
    try {
        // Verify webhook signature
        $signature = $request->header('X-PawaPay-Signature') ?? $request->header('pawapay-signature');
        
        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        // Process webhook
        $result = $this->pawaPayService->processWebhook($request->all(), $signature);
        
        if ($result['processed']) {
            return response()->json(['status' => 'success'], 200);
        }
        
        return response()->json(['error' => 'Processing failed'], 400);
        
    } catch (Exception $e) {
        return response()->json(['error' => 'Webhook processing failed'], 500);
    }
}
```

### Step 3: Add Payment Method View

```php
<!-- In your checkout payment methods view -->
<div class="payment-method" id="pawapay-method">
    <input type="radio" name="payment_method" value="pawapay" id="pawapay">
    <label for="pawapay">
        <img src="/images/pawapay-logo.png" alt="PawaPay Mobile Money">
        Mobile Money (MTN, Airtel, M-Pesa)
    </label>
    
    <div class="payment-form" id="pawapay-form" style="display: none;">
        <div class="form-group">
            <label for="phone_number">Mobile Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" 
                   placeholder="+256700000000" required>
            <small>Enter your mobile money registered phone number</small>
        </div>
        
        <div class="mobile-networks">
            <h4>Supported Networks:</h4>
            <div class="networks-list">
                <span class="network">MTN Mobile Money</span>
                <span class="network">Airtel Money</span>
                <span class="network">M-Pesa</span>
                <span class="network">Orange Money</span>
            </div>
        </div>
    </div>
</div>
```

## 🧪 Testing

### Automated Testing Suite

The integration includes a comprehensive test suite covering all payment scenarios:

```bash
# Run all integration tests
php modesy_integration/test_scenarios.php

# Run specific test scenarios
php -r "
require 'test_scenarios.php';
\$tests = new ModesyPaymentTestScenarios();
\$result = \$tests->testSingleVendorOrder();
print_r(\$result);
"
```

### Test Coverage

✅ **Single vendor orders** - Basic e-commerce payments  
✅ **Multi-vendor orders** - Marketplace with commission calculations  
✅ **Subscription payments** - Recurring billing scenarios  
✅ **Digital downloads** - Instant delivery products  
✅ **Donations** - Contribution/charity payments  
✅ **Error handling** - Network failures and validation errors  
✅ **Webhook processing** - Signature verification and duplicate handling  
✅ **Reconciliation** - Automated payment status updates  

### Manual Testing Steps

1. **Test Payment Flow**

```bash
# 1. Create test order in Modesy
# 2. Select PawaPay as payment method
# 3. Enter valid mobile number (+256700000001 for sandbox)
# 4. Complete payment on PawaPay redirect page
# 5. Verify successful return to success page
# 6. Check order status updated in Modesy admin
```

2. **Test Webhook Processing**

```bash
# Use tools like ngrok for local webhook testing
ngrok http 80
# Update webhook URL in PawaPay dashboard to ngrok URL
# Process payments and monitor webhook logs
```

3. **Test Error Scenarios**

```bash
# Test with invalid phone numbers
# Test with network timeouts  
# Test with insufficient balance
# Verify error messages display correctly
```

## 🔧 Troubleshooting

### Common Issues & Solutions

#### 🚫 CSRF Token Mismatch on Webhooks

**Problem:** Webhooks fail with 419 CSRF token mismatch error

**Solution:**
```php
// Add webhook URLs to CSRF exclusion list
protected $except = [
    'pawapay/webhook',
    'payment/pawapay/webhook'
];
```

#### 📡 Webhook Not Receiving

**Problem:** PawaPay webhooks not reaching your server  

**Solution:**
1. Verify webhook URL is publicly accessible
2. Check SSL certificate is valid
3. Ensure firewall allows incoming connections
4. Test webhook URL with curl:

```bash
curl -X POST https://yourdomain.com/pawapay/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

#### 💰 Commission Calculations Incorrect

**Problem:** Vendor commissions not calculating properly

**Solution:**
```php
// Verify commission rates in database
SELECT vendor_id, commission_rate FROM vendors;

// Check commission calculation logic
$commissions = $pawaPayService->calculateCommissions($orderData);
var_dump($commissions);
```

#### 🔄 Reconciliation Not Working

**Problem:** Payments stuck in pending status

**Solution:**
```php
// Manually trigger reconciliation
$reconciliationResult = $pawaPayService->runReconciliation();

// Check reconciliation logs
SELECT * FROM transactions WHERE reconciliation_status = 'failed';
```

#### 📱 Mobile Number Format Issues

**Problem:** Phone number validation failing

**Solution:**
```php
// Ensure phone numbers are in international format
// Valid: +256700000001
// Invalid: 0700000001

// Use phone number formatting
$phone = $pawaPayService->formatPhoneNumber($inputPhone, $countryCode);
```

### Debug Mode

Enable debug mode for detailed logging:

```php
// Add to your .env file
PAWAPAY_DEBUG=true
PAWAPAY_LOG_LEVEL=debug

// Check logs
tail -f storage/logs/pawapay.log
```

## 📚 API Reference

### ModesyIntegrationService Methods

#### `createModesyTransaction(array $orderData): array`

Creates a new transaction in the Modesy database with PawaPay integration.

**Parameters:**
- `$orderData` - Order data array with customer, products, and totals

**Returns:** Array with transaction_id and database record

**Example:**
```php
$transaction = $service->createModesyTransaction([
    'order_id' => 12345,
    'customer' => [
        'id' => 1001,
        'email' => 'customer@example.com',
        'phone' => '+256700000001'
    ],
    'products' => [/* product array */],
    'totals' => ['total' => 100.00],
    'currency' => 'USD'
]);
```

#### `processMultiVendorPayment(array $orderData): array`

Processes payments for orders with multiple vendors, handling commission splits.

**Parameters:**
- `$orderData` - Order data with multiple vendor products

**Returns:** Array with main transaction and vendor-specific transactions

#### `handlePaymentCallback(array $callbackData): array`

Processes PawaPay redirect callback and updates order status.

**Parameters:**
- `$callbackData` - Callback data from PawaPay redirect

**Returns:** Processing result with success status

#### `calculateCommissions(array $orderData): array`

Calculates vendor commissions based on products and rates.

**Parameters:**
- `$orderData` - Order data with vendor products

**Returns:** Commission breakdown by vendor

### PaymentPageFacade Methods

#### `createPaymentPageRedirect(array $params): array`

Creates PawaPay payment page redirect URL with all required parameters.

**Parameters:**
- `$params` - Payment parameters (amount, currency, customer info, etc.)

**Returns:** Array with redirect_url and deposit_id

### DatabaseConfig Methods

#### `getPaymentGatewayConfig(string $gateway): array`

Retrieves payment gateway configuration from Modesy database.

#### `createTransaction(array $data): string`

Creates transaction record in transactions table.

#### `logWebhook(array $webhookData): int`

Logs webhook data for audit and processing.

## 🤝 Support

### Getting Help

- 📧 **Email Support**: support@pawapay.co.uk
- 📖 **Documentation**: [PawaPay Developer Docs](https://docs.pawapay.co.uk)
- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/pawapay/pawapay-2v-sdk/issues)
- 💬 **Community**: [PawaPay Developer Community](https://community.pawapay.co.uk)

### Professional Services

Need custom integration or premium support?

- 🏗️ **Custom Integration** - Tailored implementation for your platform
- 🔧 **Technical Consulting** - Architecture and optimization advice  
- 🚀 **Priority Support** - Dedicated support with SLA guarantees
- 📚 **Training Services** - Team training on PawaPay integration

Contact: enterprise@pawapay.co.uk

---

## 📄 License

This integration is open-source software licensed under the [MIT license](LICENSE).

---

## 🔄 Changelog

### Version 2.0.0 (2024-10-11)
- ✅ Complete Modesy marketplace integration
- ✅ Multi-vendor support with commission calculations
- ✅ All PawaPay redirect use cases implemented  
- ✅ Network error handling and reconciliation
- ✅ Comprehensive test suite
- ✅ CSRF bypass configuration
- ✅ Database migration scripts
- ✅ Full documentation and setup guide

### Version 1.0.0 (2024-10-09)  
- ✅ Basic PawaPay redirect functionality
- ✅ Core payment processing
- ✅ Webhook signature verification
- ✅ Error handling framework

---

**Ready to accept mobile money payments in your Modesy marketplace? Follow this guide and start processing payments today! 🚀**