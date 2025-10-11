# PawaPay Enhanced Redirect Integration Guide

## 🚀 Overview

This enhanced SDK implements all PawaPay payment page redirect use cases with comprehensive error handling, automated reconciliation, and modular scenario support. The integration follows PawaPay's official documentation best practices.

## 📋 Table of Contents

- [Core Features](#core-features)
- [PawaPay Official Use Cases](#pawapay-official-use-cases)
- [Modular Scenarios](#modular-scenarios)
- [Network Error Handling](#network-error-handling)
- [Automated Reconciliation](#automated-reconciliation)
- [Error Processing](#error-processing)
- [Integration Examples](#integration-examples)
- [Production Deployment](#production-deployment)

## 🌟 Core Features

### ✅ Complete PawaPay Use Case Coverage
- **Universal Payment Pages** - Any country, amount, phone
- **Fixed Phone Number** - Registered user payments
- **Fixed Amount** - Predetermined payment amounts
- **Fixed Amount & Phone** - Subscription/recurring billing

### ✅ Network Error Handling & Recovery
- **Pre-storage of Deposit IDs** - Enables reconciliation
- **Connection failure recovery** - Automatic status checking
- **Request timeout handling** - Graceful degradation
- **Network reconciliation** - Verify payment status

### ✅ Automated Payment Reconciliation
- **Reconciliation cycles** - Check payments older than 15 minutes
- **Status verification** - Confirm payment reached PawaPay
- **Automatic cleanup** - Handle expired sessions
- **Comprehensive logging** - Track reconciliation activities

### ✅ Enhanced Error Processing
- **Failure code mapping** - PawaPay standardized errors
- **Retry recommendations** - User-friendly guidance
- **Detailed error context** - Support team information
- **Recovery workflows** - Automated retry logic

### ✅ Modular Scenario Support
- **E-commerce Checkout** - Product purchase flows
- **Subscription Billing** - Recurring payment handling
- **Wallet Top-up** - Flexible amount funding
- **Event Tickets** - Fixed price event payments
- **Donations** - Charitable contribution support
- **Bill Payments** - Utility and service bills
- **Marketplace Vendors** - Multi-vendor platforms
- **Premium Upgrades** - Service tier upgrades

## 🎯 PawaPay Official Use Cases

### 1. Universal Payment Page (All Countries)

Perfect for: New customers, international users, flexible payments

```php
$paymentPage = new PaymentPageFacade($config);

$result = $paymentPage->createUniversalPaymentPage(
    'https://yoursite.com/return',
    'Order payment'
);

// Customer can choose: Country, Phone Number, Amount
header('Location: ' . $result['redirectUrl']);
```

**Customer Experience:**
- Dropdown to select country
- Enter phone number
- Enter payment amount
- Complete payment with mobile money

### 2. Fixed Phone Number (Registered Users)

Perfect for: Existing customers, subscription services

```php
$result = $paymentPage->createFixedPhonePaymentPage(
    '254712345678', // User's registered phone
    'https://yoursite.com/return',
    'Subscription payment'
);

// Phone is pre-filled and locked
// Customer can only choose: Amount
```

**Use Cases:**
- Subscription renewals
- Registered user purchases  
- Account-based payments
- Loyalty program transactions

### 3. Fixed Amount Payment

Perfect for: Product sales, service fees, predetermined costs

```php
$result = $paymentPage->createFixedAmountPaymentPage(
    '100.00',        // Fixed amount
    'KEN',           // Required country
    'https://yoursite.com/return',
    'Product purchase'
);

// Amount and country are locked
// Customer can only choose: Phone Number
```

**Use Cases:**
- Product catalog purchases
- Service fees
- Event tickets
- Digital content purchases

### 4. Fixed Amount & Phone (Complete Lock-down)

Perfect for: Subscription billing, automated payments

```php
$result = $paymentPage->createFixedAmountAndPhonePaymentPage(
    '254712345678',  // Fixed phone
    '50.00',         // Fixed amount  
    'https://yoursite.com/return',
    'Monthly subscription'
);

// Both phone and amount are locked
// Customer only confirms payment
```

**Use Cases:**
- Monthly subscriptions
- Recurring service payments
- Automated billing cycles
- Premium membership fees

## 🎨 Modular Scenarios

### E-commerce Checkout

Optimized for online store purchases with order tracking:

```php
$checkout = $paymentPage->createEcommerceCheckout([
    'amount' => '250.00',
    'currency' => 'KES',
    'country' => 'KEN',
    'returnUrl' => 'https://store.com/checkout/return',
    'orderId' => 'ORD-123456',
    'customerEmail' => 'customer@example.com'
]);

// Includes order metadata for tracking
// Optimized for marketplace platforms
```

### Subscription Billing

Designed for recurring payment workflows:

```php
$subscription = $paymentPage->createSubscriptionBilling([
    'amount' => '29.99',
    'msisdn' => '254712345678',
    'returnUrl' => 'https://app.com/billing/return', 
    'subscriptionId' => 'SUB-PREMIUM-001',
    'billingCycle' => 'monthly'
]);

// Fixed phone and amount for reliability
// Includes subscription context
```

### Wallet Top-up

Flexible amount funding for user accounts:

```php
$topup = $paymentPage->createWalletTopup([
    'returnUrl' => 'https://app.com/wallet/return',
    'userId' => 'USER123',
    'country' => 'KEN' // Optional preference
]);

// Customer enters desired top-up amount
// Flexible and user-friendly
```

### Event Tickets

Event-specific payment handling:

```php
$ticket = $paymentPage->createEventTicket([
    'amount' => '75.00',
    'currency' => 'KES',
    'country' => 'KEN',
    'returnUrl' => 'https://events.com/ticket/return',
    'eventId' => 'CONCERT-2024',
    'ticketType' => 'VIP',
    'attendeeName' => 'John Doe'
]);

// Includes event and attendee metadata
// Perfect for ticketing platforms
```

## 🛡️ Network Error Handling

### Pre-storage Strategy

Following PawaPay recommendations, all deposit IDs are stored before API calls:

```php
// SDK automatically stores deposit ID before payment page creation
$depositId = $this->generateDepositId();
$this->storeDepositIdForReconciliation($depositId, $paymentData);

try {
    $response = $this->createPaymentPage($paymentData);
} catch (NetworkException $e) {
    // Check if payment reached PawaPay
    return $this->handleNetworkError($depositId, $e);
}
```

### Connection Failure Recovery

```php
try {
    $result = $paymentPage->createUniversalPaymentPage($returnUrl, $reason);
} catch (PaymentGatewayException $e) {
    if ($e->isNetworkError()) {
        // Payment status is unknown - will be reconciled automatically
        $status = $paymentPage->checkReconciliationStatus($depositId);
        
        if ($status['requiresReconciliation']) {
            // Show "processing" message to customer
            // Reconciliation cycle will handle it
        }
    }
}
```

### Recovery Workflow

1. **Store Deposit ID** - Before any API call
2. **Attempt Payment Creation** - Normal flow
3. **Handle Network Errors** - Catch connection issues  
4. **Check Payment Status** - Verify if reached PawaPay
5. **Reconciliation Decision** - Mark as pending or failed

## 🔄 Automated Reconciliation

### Reconciliation Cycle

Run automated reconciliation for payments older than 15 minutes:

```php
// Recommended: Run every 10-15 minutes via cron
$results = $paymentPage->runReconciliationCycle(15);

echo "Checked: {$results['total_checked']} payments\n";
echo "Found: {$results['found']} successful\n";
echo "Not found: {$results['not_found']} failed\n";
echo "Errors: {$results['errors']} need retry\n";
```

### Reconciliation States

```php
// Check individual payment reconciliation
$status = $paymentPage->checkReconciliationStatus($depositId);

switch ($status['status']) {
    case 'IN_RECONCILIATION':
        // PawaPay is reconciling - no action needed
        // Successful payments reconcile faster
        break;
        
    case 'completed':
        // Payment successful - fulfill order
        break;
        
    case 'failed':
        // Payment failed - handle accordingly
        break;
}
```

### Cron Job Setup

```bash
# Add to crontab - run every 15 minutes
*/15 * * * * /usr/bin/php /path/to/reconcile-payments.php
```

```php
// reconcile-payments.php
<?php
require_once 'vendor/autoload.php';

$paymentPage = new PaymentPageFacade($config);
$results = $paymentPage->runReconciliationCycle(15);

// Log results and alert on errors
error_log('Reconciliation: ' . json_encode($results));
```

## ⚠️ Enhanced Error Processing

### Failure Analysis

```php
$failureInfo = $paymentPage->handleProcessingFailure($depositId);

echo "Status: {$failureInfo['status']}\n";
echo "Code: {$failureInfo['failureCode']}\n";
echo "Message: {$failureInfo['failureMessage']}\n";
echo "Retry Allowed: " . ($failureInfo['retryAllowed'] ? 'Yes' : 'No') . "\n";
echo "Recommendations: " . implode(', ', $failureInfo['recommendedActions']) . "\n";
```

### Failure Code Handling

The SDK maps PawaPay failure codes to user-friendly actions:

| Failure Code | Retry Allowed | Recommendations |
|--------------|---------------|-----------------|
| `INSUFFICIENT_BALANCE` | ✅ Yes | Show retry with different account, suggest balance top-up |
| `INVALID_PHONE_NUMBER` | ✅ Yes | Validate format, offer correction interface |
| `TRANSACTION_LIMIT_EXCEEDED` | ✅ Yes | Show limits, suggest smaller amount |
| `NETWORK_ERROR` | ✅ Yes | Retry payment, suggest try again later |
| `UNSPECIFIED_FAILURE` | ✅ Yes | Show generic error, offer retry |
| `UNKNOWN_ERROR` | ❌ No | Contact support, provide reference |

### Retry Workflow

```php
if ($failureInfo['retryAllowed'] && $failureInfo['newDepositIdRequired']) {
    // Create new payment page for retry (new deposit ID required)
    $retryResult = $paymentPage->createUniversalPaymentPage(
        $originalReturnUrl,
        'Retry: ' . $originalReason
    );
    
    // Redirect to new payment page
    header('Location: ' . $retryResult['redirectUrl']);
}
```

## 💼 Integration Examples

### Complete E-commerce Integration

```php
<?php
class CheckoutController
{
    public function createPayment(Request $request)
    {
        $paymentPage = new PaymentPageFacade(config('pawapay'));
        
        try {
            // Create e-commerce checkout payment
            $result = $paymentPage->createEcommerceCheckout([
                'amount' => $request->total,
                'currency' => $request->currency,
                'country' => $request->country,
                'returnUrl' => route('checkout.return'),
                'orderId' => $request->order_id,
                'customerEmail' => auth()->user()->email
            ]);
            
            // Store payment reference
            Order::where('id', $request->order_id)->update([
                'deposit_id' => $result['depositId'],
                'payment_status' => 'pending'
            ]);
            
            return redirect($result['redirectUrl']);
            
        } catch (PaymentGatewayException $e) {
            return back()->withError('Payment creation failed: ' . $e->getMessage());
        }
    }
    
    public function handleReturn(Request $request)
    {
        $depositId = $request->get('depositId');
        $paymentPage = new PaymentPageFacade(config('pawapay'));
        
        try {
            $result = $paymentPage->handlePaymentReturn($depositId, $request->all());
            
            switch ($result['status']) {
                case 'completed':
                    $this->fulfillOrder($depositId);
                    return view('checkout.success');
                    
                case 'pending':
                case 'processing':
                case 'reconciling':
                    return view('checkout.waiting', compact('depositId'));
                    
                case 'failed':
                    $failureInfo = $paymentPage->handleProcessingFailure($depositId);
                    return view('checkout.failed', compact('failureInfo'));
                    
                default:
                    return view('checkout.unknown');
            }
            
        } catch (PaymentGatewayException $e) {
            return view('checkout.error', ['error' => $e->getMessage()]);
        }
    }
    
    private function fulfillOrder($depositId)
    {
        $order = Order::where('deposit_id', $depositId)->first();
        
        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'processing'
            ]);
            
            // Send confirmation email
            Mail::to($order->customer_email)->send(new OrderConfirmation($order));
            
            // Trigger fulfillment
            dispatch(new ProcessOrder($order));
        }
    }
}
```

### Subscription Service Integration

```php
<?php
class SubscriptionController
{
    public function createBilling(Subscription $subscription)
    {
        $paymentPage = new PaymentPageFacade(config('pawapay'));
        
        try {
            $result = $paymentPage->createSubscriptionBilling([
                'amount' => $subscription->amount,
                'msisdn' => $subscription->user->phone_number,
                'returnUrl' => route('subscription.return'),
                'subscriptionId' => $subscription->id,
                'billingCycle' => $subscription->billing_cycle
            ]);
            
            $subscription->update([
                'current_deposit_id' => $result['depositId'],
                'billing_status' => 'pending'
            ]);
            
            return redirect($result['redirectUrl']);
            
        } catch (PaymentGatewayException $e) {
            $subscription->update(['billing_status' => 'failed']);
            return back()->withError('Billing failed: ' . $e->getMessage());
        }
    }
}
```

### Production Webhook Handler

```php
<?php
// webhook.php - Production-ready webhook endpoint
class WebhookController
{
    public function handlePawaPay(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-PawaPay-Signature');
        
        try {
            $paymentPage = new PaymentPageFacade(config('pawapay'));
            $result = $paymentPage->processWebhook(
                json_decode($payload, true), 
                $signature
            );
            
            $this->updatePaymentStatus($result);
            
            return response('OK', 200);
            
        } catch (PaymentGatewayException $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            
            return response('Error: ' . $e->getMessage(), 400);
        }
    }
    
    private function updatePaymentStatus($result)
    {
        $depositId = $result['depositId'];
        $status = $result['status'];
        
        // Update order status
        if ($order = Order::where('deposit_id', $depositId)->first()) {
            $order->update(['payment_status' => $status]);
            
            if ($status === 'completed') {
                $this->fulfillOrder($order);
            } elseif ($status === 'failed') {
                $this->handleFailedPayment($order);
            }
        }
        
        // Update subscription status  
        if ($sub = Subscription::where('current_deposit_id', $depositId)->first()) {
            $sub->update(['billing_status' => $status]);
            
            if ($status === 'completed') {
                $this->renewSubscription($sub);
            }
        }
    }
}
```

## 🚀 Production Deployment

### Environment Configuration

```php
// config/pawapay.php
return [
    'api' => [
        'token' => env('PAWAPAY_API_TOKEN'),
        'base_url' => env('PAWAPAY_API_URL', 'https://api.pawapay.io')
    ],
    'webhook_secret' => env('PAWAPAY_WEBHOOK_SECRET'),
    'environment' => env('PAWAPAY_ENVIRONMENT', 'production')
];
```

```bash
# .env.production
PAWAPAY_ENVIRONMENT=production
PAWAPAY_API_TOKEN=your_production_token
PAWAPAY_WEBHOOK_SECRET=your_production_webhook_secret  
PAWAPAY_API_URL=https://api.pawapay.io
```

### Required Infrastructure

#### 1. Webhook Endpoints
- **SSL Certificate Required** - PawaPay only sends to HTTPS endpoints
- **Signature Verification** - Always verify webhook signatures
- **Idempotent Processing** - Handle duplicate webhooks gracefully

#### 2. Reconciliation Jobs
```bash
# Crontab entry
*/15 * * * * /usr/bin/php /var/www/artisan pawapay:reconcile
```

#### 3. Monitoring & Alerting
- **Payment Success Rate** - Monitor completion rates
- **Reconciliation Errors** - Alert on reconciliation failures
- **API Response Times** - Track performance metrics
- **Error Rate Thresholds** - Set up failure alerts

### Security Checklist

- ✅ **HTTPS Only** - All endpoints must use SSL
- ✅ **Webhook Signature Verification** - Cryptographic validation
- ✅ **Input Validation** - Sanitize all user inputs
- ✅ **Rate Limiting** - Prevent API abuse
- ✅ **Error Logging** - Secure error handling
- ✅ **Secrets Management** - Environment-based configuration

### Performance Optimization

#### Database Indexing
```sql
-- Orders table
CREATE INDEX idx_orders_deposit_id ON orders(deposit_id);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);

-- Payment sessions
CREATE INDEX idx_payment_sessions_status ON payment_sessions(status);
CREATE INDEX idx_payment_sessions_created_at ON payment_sessions(created_at);
```

#### Caching Strategy
```php
// Cache active configuration for 1 hour
$config = Cache::remember('pawapay_config', 3600, function () {
    return $this->pawaPay->getActiveConfiguration();
});
```

## 📊 Monitoring & Analytics

### Key Metrics to Track

1. **Payment Completion Rate** - Successful payments / Total initiated
2. **Reconciliation Success Rate** - Reconciled payments / Total pending
3. **Average Payment Time** - Time from initiation to completion
4. **Failure Rate by Code** - Track most common failure reasons
5. **Customer Drop-off Points** - Where customers abandon payments

### Logging Best Practices

```php
// Structured logging for analytics
Log::channel('payments')->info('Payment initiated', [
    'deposit_id' => $depositId,
    'amount' => $amount,
    'currency' => $currency,
    'scenario' => $scenario,
    'user_id' => $userId,
    'timestamp' => now()
]);
```

## 🎯 Best Practices Summary

### Payment Creation
1. **Always pre-generate deposit IDs** for reconciliation
2. **Validate amounts** against transaction limits
3. **Format phone numbers** using predict provider endpoint
4. **Store session data** before API calls
5. **Handle network errors** gracefully

### Error Handling
1. **Map failure codes** to user-friendly messages
2. **Provide retry options** for recoverable errors
3. **Log detailed context** for debugging
4. **Implement graceful degradation** for network issues

### Reconciliation
1. **Run reconciliation cycles** every 15 minutes
2. **Check payments older than 15 minutes** only
3. **Handle IN_RECONCILIATION status** appropriately
4. **Clean up expired sessions** regularly

### Security
1. **Always verify webhook signatures** cryptographically
2. **Use HTTPS endpoints** for all communications
3. **Validate all inputs** before processing
4. **Implement rate limiting** on public endpoints

---

## 📞 Support & Resources

- **PawaPay API Documentation**: [https://docs.pawapay.io](https://docs.pawapay.io)
- **SDK Examples**: `examples/comprehensive_redirect_examples.php`
- **Integration Tests**: `tests/Integration/PaymentPageIntegrationTest.php`
- **Error Reference**: See failure code mappings in `PaymentPageFacade.php`

**Production Ready**: ✅ This enhanced SDK implements all PawaPay best practices and is ready for production deployment with comprehensive error handling and automated reconciliation.