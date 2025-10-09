# PawaPay SDK - Production Deployment Guide

## 🚀 Production Deployment Checklist

This guide walks you through deploying the PawaPay SDK with Payment Page support to production.

## 📋 Pre-Deployment Requirements

### 1. PawaPay Account Setup
- [ ] **Production PawaPay account** approved and active
- [ ] **API credentials** generated from production dashboard
- [ ] **Webhook secret** configured in PawaPay dashboard
- [ ] **Return/callback URLs** configured in PawaPay dashboard
- [ ] **Countries and currencies** activated for your account

### 2. Server Requirements
- [ ] **PHP 7.4+** (8.0+ recommended)
- [ ] **Composer** for dependency management
- [ ] **SSL certificate** installed and active
- [ ] **Webhook endpoint** accessible from internet
- [ ] **File system permissions** for logging and sessions
- [ ] **Cron job capability** for cleanup tasks

### 3. Security Requirements
- [ ] **HTTPS enforced** on all endpoints
- [ ] **Webhook endpoint protected** with signature verification
- [ ] **Environment variables** securely stored
- [ ] **Error logging** configured but not exposing sensitive data
- [ ] **Rate limiting** implemented on payment endpoints

## ⚙️ Step 1: Environment Configuration

### Production Environment Variables

Create `.env.production` with your production credentials:

```env
# PawaPay Production Configuration
PAWAPAY_ENVIRONMENT=production
PAWAPAY_API_TOKEN=your_production_api_token_here
PAWAPAY_WEBHOOK_SECRET=your_production_webhook_secret_here
PAWAPAY_API_URL=https://api.pawapay.io

# Application Configuration
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# URLs
BASE_URL=https://yoursite.com
WEBHOOK_URL=https://yoursite.com/webhooks/pawapay
RETURN_URL_BASE=https://yoursite.com/payment/return
```

### Secure Configuration Loading

```php
<?php
// config/production.php
return [
    'api' => [
        'token' => $_ENV['PAWAPAY_API_TOKEN'] ?? null,
        'base_url' => $_ENV['PAWAPAY_API_URL'] ?? 'https://api.pawapay.io'
    ],
    'webhook_secret' => $_ENV['PAWAPAY_WEBHOOK_SECRET'] ?? null,
    'environment' => $_ENV['PAWAPAY_ENVIRONMENT'] ?? 'production',
    'return_url_base' => $_ENV['RETURN_URL_BASE'] ?? 'https://yoursite.com/payment/return',
    'webhook_url' => $_ENV['WEBHOOK_URL'] ?? 'https://yoursite.com/webhooks/pawapay'
];
```

## 🔧 Step 2: Production Code Setup

### Initialize SDK with Production Config

```php
<?php
// app/Services/PaymentService.php
namespace App\Services;

use Myzuwa\PawaPay\PaymentPageFacade;

class PaymentService
{
    private $paymentPage;
    
    public function __construct()
    {
        $config = require_once __DIR__ . '/../../config/production.php';
        
        // Validate required config
        if (empty($config['api']['token']) || empty($config['webhook_secret'])) {
            throw new \Exception('Missing required PawaPay production configuration');
        }
        
        $this->paymentPage = new PaymentPageFacade($config);
    }
    
    public function createPayment(array $orderData): string
    {
        return $this->paymentPage->createPaymentRedirect([
            'amount' => $orderData['amount'],
            'currency' => $orderData['currency'],
            'description' => $orderData['description'],
            'returnUrl' => $this->generateReturnUrl($orderData['order_id']),
            'customerPhone' => $orderData['customer_phone'] ?? null,
            'country' => $orderData['country'] ?? null,
            'orderId' => $orderData['order_id'],
            'customerEmail' => $orderData['customer_email'] ?? null
        ]);
    }
    
    private function generateReturnUrl(string $orderId): string
    {
        $baseUrl = $_ENV['RETURN_URL_BASE'];
        return "{$baseUrl}?order_id={$orderId}";
    }
}
```

## 🔗 Step 3: Webhook Endpoint Setup

### Production Webhook Handler

```php
<?php
// public/webhooks/pawapay.php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\PaymentService;
use App\Services\OrderService;

// Enable error logging but don't display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("PawaPay webhook called: " . date('Y-m-d H:i:s'));

try {
    // Validate HTTP method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    
    // Get raw payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($signature)) {
        http_response_code(400);
        exit('Missing payload or signature');
    }
    
    // Parse webhook data
    $webhookData = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        exit('Invalid JSON payload');
    }
    
    // Process webhook
    $paymentService = new PaymentService();
    $result = $paymentService->getPaymentPage()->processWebhook($webhookData, $signature);
    
    // Handle successful payment
    if ($result['status'] === 'completed') {
        $orderService = new OrderService();
        $orderService->markAsPaid($result['depositId']);
        
        // Send confirmation email, update inventory, etc.
        $orderService->fulfillOrder($result['depositId']);
    }
    
    // Log successful processing
    error_log("Webhook processed successfully: " . json_encode($result));
    
    http_response_code(200);
    echo 'OK';
    
} catch (\Exception $e) {
    // Log error with context
    error_log("Webhook error: " . $e->getMessage() . " | Payload: " . ($payload ?? 'none'));
    
    http_response_code(500);
    echo 'Internal server error';
}
```

### Webhook URL Configuration

1. **Configure in PawaPay Dashboard**:
   - Go to your PawaPay merchant dashboard
   - Navigate to Webhooks section
   - Add webhook URL: `https://yoursite.com/webhooks/pawapay.php`
   - Ensure webhook secret matches your configuration

2. **Test Webhook Connectivity**:
```bash
# Test webhook endpoint accessibility
curl -X POST https://yoursite.com/webhooks/pawapay.php \
  -H "Content-Type: application/json" \
  -H "X-PawaPay-Signature: test_signature" \
  -d '{"test": "payload"}'
```

## 📋 Step 4: Return URL Handler

### Production Return Handler

```php
<?php
// public/payment/return.php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\PaymentService;
use App\Services\OrderService;

try {
    $orderId = $_GET['order_id'] ?? null;
    $depositId = $_GET['depositId'] ?? null;
    
    if (!$orderId || !$depositId) {
        throw new Exception('Missing required parameters');
    }
    
    // Get payment status
    $paymentService = new PaymentService();
    $result = $paymentService->getPaymentPage()->handlePaymentReturn($depositId, $_GET);
    
    // Get order details
    $orderService = new OrderService();
    $order = $orderService->getOrder($orderId);
    
    switch ($result['status']) {
        case 'completed':
            // Payment successful
            header('Location: /payment/success?order_id=' . $orderId);
            break;
            
        case 'pending':
        case 'processing':
        case 'reconciling':
            // Show waiting page
            header('Location: /payment/waiting?order_id=' . $orderId . '&deposit_id=' . $depositId);
            break;
            
        case 'failed':
        case 'rejected':
            // Payment failed
            header('Location: /payment/failed?order_id=' . $orderId . '&reason=' . urlencode($result['message']));
            break;
            
        default:
            // Unknown status
            header('Location: /payment/error?order_id=' . $orderId);
    }
    
} catch (Exception $e) {
    error_log("Return handler error: " . $e->getMessage());
    header('Location: /payment/error');
}
exit;
```

## 📊 Step 5: Database Integration

### Order Status Management

```sql
-- Add payment tracking to orders table
ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN deposit_id VARCHAR(50) NULL;
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'pawapay';
ALTER TABLE orders ADD COLUMN payment_completed_at TIMESTAMP NULL;

-- Create payment logs table
CREATE TABLE payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deposit_id VARCHAR(50) NOT NULL,
    order_id VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    payment_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_deposit_id (deposit_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);
```

### Order Service Implementation

```php
<?php
// app/Services/OrderService.php
namespace App\Services;

class OrderService
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    public function markAsPaid(string $depositId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE orders SET payment_status = 'completed', payment_completed_at = NOW() 
             WHERE deposit_id = ?"
        );
        $stmt->execute([$depositId]);
        
        // Log payment completion
        $this->logPayment($depositId, 'completed', ['webhook_processed' => true]);
    }
    
    public function createOrder(array $orderData): array
    {
        // Create order logic
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders (order_id, customer_email, amount, currency, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())"
        );
        
        $orderId = 'ORD-' . time() . '-' . rand(1000, 9999);
        $stmt->execute([
            $orderId,
            $orderData['customer_email'],
            $orderData['amount'],
            $orderData['currency']
        ]);
        
        return ['order_id' => $orderId];
    }
    
    private function logPayment(string $depositId, string $status, array $data = []): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO payment_logs (deposit_id, order_id, status, payment_data)
             VALUES (?, (SELECT order_id FROM orders WHERE deposit_id = ?), ?, ?)"
        );
        
        $stmt->execute([$depositId, $depositId, $status, json_encode($data)]);
    }
}
```

## 🔄 Step 6: Session Management & Cleanup

### Production Session Configuration

```php
<?php
// config/session.php
return [
    'storage' => 'database', // or 'filesystem'
    'cleanup_interval' => 3600, // 1 hour
    'session_lifetime' => 900, // 15 minutes (same as PawaPay)
    'batch_cleanup_size' => 100
];
```

### Automated Cleanup Cron Job

```bash
# Add to crontab (crontab -e)
# Clean expired payment sessions every hour
0 * * * * /usr/bin/php /path/to/your/app/scripts/cleanup_sessions.php
```

```php
<?php
// scripts/cleanup_sessions.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PaymentService;

try {
    $paymentService = new PaymentService();
    $cleaned = $paymentService->getPaymentPage()->cleanExpiredSessions();
    
    echo date('Y-m-d H:i:s') . " - Cleaned {$cleaned} expired sessions\n";
    
} catch (Exception $e) {
    echo date('Y-m-d H:i:s') . " - Cleanup error: " . $e->getMessage() . "\n";
}
```

## 📈 Step 7: Monitoring & Alerting

### Production Logging Configuration

```php
<?php
// config/logging.php
return [
    'payment_success' => '/var/log/pawapay/payments_success.log',
    'payment_errors' => '/var/log/pawapay/payments_errors.log',
    'webhook_events' => '/var/log/pawapay/webhooks.log',
    'session_cleanup' => '/var/log/pawapay/cleanup.log'
];
```

### Health Check Endpoint

```php
<?php
// public/health/pawapay.php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\PaymentService;

header('Content-Type: application/json');

try {
    $paymentService = new PaymentService();
    $stats = $paymentService->getPaymentPage()->getStatistics();
    
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'payment_pages' => [
            'total_sessions' => $stats['total_sessions'],
            'active_sessions' => $stats['active_sessions'],
            'success_rate' => $stats['success_rate']
        ],
        'api' => [
            'pawapay_reachable' => $this->testPawaPayConnectivity()
        ]
    ];
    
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}

function testPawaPayConnectivity(): bool
{
    // Simple connectivity test (implement based on your needs)
    $ch = curl_init('https://api.pawapay.io/health');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
```

## 🧪 Step 8: Production Testing

### Pre-Launch Testing Checklist

- [ ] **Small amount test transactions** (e.g., $0.01 or equivalent)
- [ ] **Payment flow testing** with real mobile money accounts
- [ ] **Webhook delivery verification** 
- [ ] **Return URL handling** for all status types
- [ ] **Error handling** testing with invalid data
- [ ] **Load testing** with multiple concurrent sessions
- [ ] **Mobile device testing** across different carriers
- [ ] **Multi-currency testing** if applicable

### Production Testing Script

```php
<?php
// tests/production_test.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PaymentService;

// Test configuration
$testConfig = [
    'amount' => 1.00, // Test with small amount
    'currency' => 'KES',
    'phone' => '254700000000', // Use test phone number
    'email' => 'test@yoursite.com'
];

try {
    $paymentService = new PaymentService();
    
    // Test payment page creation
    echo "Testing payment page creation...\n";
    $redirectUrl = $paymentService->createPayment([
        'amount' => $testConfig['amount'],
        'currency' => $testConfig['currency'],
        'description' => 'Production Test Payment',
        'order_id' => 'TEST-' . time(),
        'customer_phone' => $testConfig['phone'],
        'customer_email' => $testConfig['email']
    ]);
    
    echo "✅ Payment page created: {$redirectUrl}\n";
    
    // Test statistics
    $stats = $paymentService->getPaymentPage()->getStatistics();
    echo "📊 Current sessions: {$stats['active_sessions']}\n";
    
    echo "Production test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Production test failed: " . $e->getMessage() . "\n";
    exit(1);
}
```

## 🔒 Step 9: Security Hardening

### Production Security Configuration

```php
<?php
// security/middleware.php

// Rate limiting middleware
class RateLimitMiddleware
{
    public static function checkPaymentCreation(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "payment_creation_rate:{$ip}";
        
        // Allow max 10 payment page creations per minute per IP
        if (self::getRequestCount($key) > 10) {
            http_response_code(429);
            exit('Rate limit exceeded');
        }
        
        self::incrementRequestCount($key);
    }
    
    private static function getRequestCount(string $key): int
    {
        // Implement using Redis, Memcached, or database
        return 0;
    }
    
    private static function incrementRequestCount(string $key): void
    {
        // Implement counter with 60-second expiry
    }
}

// CSRF protection for forms
class CSRFProtection
{
    public static function generateToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

### Input Validation

```php
<?php
// validation/PaymentValidator.php
class PaymentValidator
{
    public static function validatePaymentData(array $data): array
    {
        $errors = [];
        
        // Amount validation
        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'Invalid amount';
        }
        
        // Currency validation
        $allowedCurrencies = ['KES', 'GHS', 'ZMW', 'NGN'];
        if (!isset($data['currency']) || !in_array($data['currency'], $allowedCurrencies)) {
            $errors[] = 'Invalid currency';
        }
        
        // Phone validation
        if (isset($data['customer_phone'])) {
            if (!preg_match('/^[1-9][0-9]{8,14}$/', $data['customer_phone'])) {
                $errors[] = 'Invalid phone number format';
            }
        }
        
        // Email validation
        if (isset($data['customer_email'])) {
            if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address';
            }
        }
        
        return $errors;
    }
}
```

## 📋 Step 10: Go-Live Checklist

### Final Pre-Launch Verification

- [ ] **All production credentials configured and tested**
- [ ] **Webhook endpoint responding correctly to test requests**
- [ ] **Return URLs accessible and handling all payment statuses**
- [ ] **Database tables created and permissions set**
- [ ] **Cron jobs configured for session cleanup**
- [ ] **Monitoring and alerting configured**
- [ ] **Error logging working but not exposing sensitive data**
- [ ] **Rate limiting and CSRF protection active**
- [ ] **SSL certificate valid and HTTPS enforced**
- [ ] **Load balancer health checks configured**
- [ ] **Backup and recovery procedures tested**
- [ ] **Team trained on troubleshooting procedures**

### Post-Launch Monitoring

```bash
# Monitor key metrics
tail -f /var/log/pawapay/payments_success.log | grep "$(date +'%Y-%m-%d')"
tail -f /var/log/pawapay/payments_errors.log | grep "$(date +'%Y-%m-%d')"

# Check system health
curl -s https://yoursite.com/health/pawapay | jq '.'

# Monitor payment success rate
# Set up alerts for:
# - Success rate below 95%
# - Webhook failures > 5% 
# - API response time > 5 seconds
# - Unusual error rate spikes
```

---

**🎉 Congratulations!** Your PawaPay SDK with Payment Page support is now deployed to production.

**📞 Support**: If you encounter issues, check the logs first, then review the troubleshooting section in the main README.

**🔄 Updates**: Regularly update the SDK and monitor PawaPay API changelog for any breaking changes.