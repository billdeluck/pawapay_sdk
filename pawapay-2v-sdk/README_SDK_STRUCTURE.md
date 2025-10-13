# 🚀 PawaPay SDK v2 - Production-Ready Structure

## 📁 What Files to Keep in the SDK Folder

The PawaPay SDK has been restructured for production use with proper Composer support and real API testing. Here's what each directory contains:

### 📂 **Core SDK Structure**

```
pawapay-2v-sdk/
├── src/                          # Main SDK source code (PSR-4 autoloaded)
│   ├── Service/                  # Business logic services
│   │   ├── FeeCalculator.php     # Real PawaPay fee calculations
│   │   ├── MNOService.php        # Mobile money operator services
│   │   └── ModesyIntegrationService.php  # Modesy marketplace integration
│   ├── Config/                   # Configuration management
│   │   └── PawaPayConfig.php     # Environment & config loader
│   ├── Exception/                # Custom exceptions
│   │   └── FeeCalculationException.php
│   ├── Payment/                  # Payment processing
│   ├── Adapter/                  # API adapters
│   ├── Controller/               # HTTP controllers
│   ├── Support/                  # Utility classes
│   ├── PawaPay.php              # Main SDK class
│   ├── PaymentPageFacade.php    # Payment redirect facade
│   ├── WebhookHandler.php       # Webhook processing
│   └── helpers.php              # Global helper functions
├── tests/                        # Comprehensive test suite
│   ├── Unit/                     # Unit tests
│   ├── Integration/              # Real API integration tests
│   ├── bootstrap.php             # Test environment setup
│   └── reports/                  # Test coverage reports
├── modesy_integration/           # Complete Modesy marketplace integration
├── docs/                         # Documentation
├── examples/                     # Usage examples
├── config/                       # Configuration templates
├── composer.json                 # Composer dependencies
├── phpunit.xml                   # PHPUnit configuration
├── .env                         # Environment configuration
├── .env.example                 # Configuration template
└── README.md                    # Main documentation
```

### 🔧 **Composer Dependencies**

The SDK now includes proper Composer support:

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run real API tests
composer run sdk-test

# Check code style
composer run cs-check

# Static analysis
composer run stan
```

## 🧪 **Real API Testing (No Placeholders)**

### **Setup for Real Testing**

1. **Copy environment configuration:**
   ```bash
   cp .env.example .env
   ```

2. **Add your PawaPay credentials:**
   ```env
   PAWAPAY_API_TOKEN=your_sandbox_api_token_here
   PAWAPAY_WEBHOOK_SECRET=your_webhook_secret_here
   PAWAPAY_ENVIRONMENT=sandbox
   ```

3. **Run real API tests:**
   ```bash
   composer test
   # or specifically for integration tests
   ./vendor/bin/phpunit tests/Integration/RealApiTest.php
   ```

### **What the Real Tests Do**

✅ **Make actual HTTP calls** to PawaPay sandbox servers  
✅ **Create real payment redirects** with valid URLs  
✅ **Verify webhook signatures** using HMAC-SHA256  
✅ **Test fee calculations** against live API responses  
✅ **Validate redirect URL accessibility**  
✅ **Test network error handling** and retries  
✅ **Load testing** with multiple simultaneous payments  

### **Test Coverage**

- **Unit Tests**: Fee calculations, configuration, validators
- **Integration Tests**: Real PawaPay API calls and responses
- **Feature Tests**: Complete payment flows with Modesy integration

## 💰 **Fee Configuration & Calculations**

### **Real PawaPay Fee Structure Implementation**

The `FeeCalculator` class implements the complete fee structure from https://www.pawapay.io/fees:

```php
use PawaPay\Service\FeeCalculator;

$calculator = new FeeCalculator();

// Calculate fees for Kenya M-PESA
$fees = $calculator->calculateFee(1000, 'KSH', 'KE', 'MPESA', 'collections');
// Result: KSh 10 (MMO) + KSh 10 (1% PawaPay) = KSh 20 total

// Get total amount with fees
$total = $calculator->calculateTotalWithFees(1000, 'KSH', 'KE', 'MPESA');
// Result: Original KSh 1000 + KSh 20 fees = KSh 1020 total
```

### **Environment-Based Fee Override**

Configure fees in `.env` file:

```env
# Set to 0 to disable all fees for customers
PAWAPAY_FEE_OVERRIDE_PERCENTAGE=0

# Set custom percentage (e.g., 2.5% = 0.025)
PAWAPAY_FEE_OVERRIDE_PERCENTAGE=0.025

# Leave empty to use real PawaPay fee structure
PAWAPAY_FEE_OVERRIDE_PERCENTAGE=
```

### **Fee Integration with Product Pricing**

```php
// Helper function for easy fee calculation
$totalWithFees = pawapay_total_with_fees(100, 'USD', 'KE', 'MPESA');

// Add to product price before sending to PawaPay
$productPrice = 100.00;
$feeAmount = pawapay_calculate_fee($productPrice, 'USD')['total_fee_amount'];
$totalAmount = $productPrice + $feeAmount;

// Send total amount to PawaPay
$redirectData = [
    'amount' => $totalAmount,  // Price + fees
    'currency' => 'USD',
    // ... other parameters
];
```

## 🌍 **Supported Countries & Fee Structures**

The SDK includes real fee structures for:

- **Kenya (KE)**: M-PESA with tiered fixed fees
- **Uganda (UG)**: MTN & Airtel with percentage and tiered fees
- **Tanzania (TZ)**: Airtel percentage fees + generic disbursement tiers
- **Rwanda (RW)**: MTN & Airtel percentage fees
- **Zambia (ZM)**: MTN, Airtel, Zamtel with complex tiered structures
- **Mozambique (MZ)**: Mixed percentage and fixed fee tiers
- **Malawi (MW)**: Airtel percentage fees
- **Ivory Coast (CI)**: MTN & Orange percentage fees
- **Ethiopia (ET)**: Safaricom M-PESA percentage fees
- **Cameroon (CM)**: MTN & Orange percentage fees
- **DRC (CD)**: Vodacom percentage fees
- **Benin (BJ)**: MTN & Moov percentage fees
- **Sierra Leone (SL)**: Orange percentage fees

## 📊 **Production Considerations**

### **Environment Variables for Production**

```env
# Production settings
PAWAPAY_ENVIRONMENT=production
PAWAPAY_API_TOKEN=your_production_api_token
PAWAPAY_WEBHOOK_SECRET=your_production_webhook_secret
PAWAPAY_TEST_MODE=false
PAWAPAY_DEBUG_MODE=false

# Production URLs
PAWAPAY_WEBHOOK_URL=https://yourdomain.com/pawapay/webhook
PAWAPAY_SUCCESS_URL=https://yourdomain.com/payment/success/{transactionId}
PAWAPAY_FAILURE_URL=https://yourdomain.com/payment/failed
```

### **Performance & Caching**

```env
# Enable fee caching for production
PAWAPAY_CACHE_FEES=true
PAWAPAY_CACHE_DURATION=3600

# Rate limiting
PAWAPAY_RATE_LIMITING=true
PAWAPAY_RATE_LIMIT_RPM=60
```

### **Monitoring & Logging**

```env
# Logging configuration
PAWAPAY_LOG_LEVEL=info
PAWAPAY_LOG_FILE=logs/pawapay.log
PAWAPAY_LOG_API_REQUESTS=false

# Notification settings
PAWAPAY_EMAIL_NOTIFICATIONS=true
PAWAPAY_NOTIFICATION_EMAIL=admin@yourdomain.com
```

## 🔐 **Security Features**

- ✅ **HMAC-SHA256 webhook verification**
- ✅ **Input validation and sanitization**
- ✅ **Rate limiting support**
- ✅ **IP whitelisting for webhooks**
- ✅ **Environment-based configuration**
- ✅ **Secure credential management**

## 🚀 **Quick Start**

1. **Install via Composer:**
   ```bash
   composer require pawapay/pawapay-2v-sdk
   ```

2. **Configure environment:**
   ```bash
   cp vendor/pawapay/pawapay-2v-sdk/.env.example .env
   # Edit .env with your credentials
   ```

3. **Create payment with fees:**
   ```php
   <?php
   require 'vendor/autoload.php';
   
   use PawaPay\PaymentPageFacade;
   use PawaPay\Service\FeeCalculator;
   
   // Calculate total with fees
   $calculator = new FeeCalculator();
   $total = $calculator->calculateTotalWithFees(100, 'USD');
   
   // Create payment redirect
   $facade = new PaymentPageFacade();
   $redirect = $facade->createPaymentPageRedirect([
       'deposit_id' => 'ORDER_' . time(),
       'amount' => $total['total_amount'], // Includes fees
       'currency' => 'USD',
       'description' => 'Product purchase with fees',
       'payer_msisdn' => '+256700000001',
       'payer_email' => 'customer@example.com'
   ]);
   
   // Redirect customer to payment page
   header('Location: ' . $redirect['redirect_url']);
   ```

4. **Run tests to verify:**
   ```bash
   composer test
   ```

## 📞 **Support**

- **Documentation**: Complete integration guides in `/docs`
- **Examples**: Working code examples in `/examples`
- **Tests**: Real API tests demonstrate all functionality
- **Issues**: Report bugs via GitHub issues

The SDK is now production-ready with real fee calculations, comprehensive testing, and proper Composer structure! 🎉