# PawaPay SDK for Production

## 📁 Production Structure

This `src/` directory contains the complete PawaPay SDK ready for production deployment.

```
src/
├── .env.test.md              # Test environment configuration
├── PawaPay.php              # Main SDK class
├── WebhookHandler.php       # Webhook processing handler
├── config/
│   └── bootstrap.php        # ✅ Production bootstrap configuration
├── Adapter/                 # Payment gateway adapters
├── Controller/              # Webhook controllers
├── Exception/               # Custom exceptions
├── Payment/                 # Payment processing strategies
├── Service/                 # Core SDK services
└── Support/                 # Helper utilities
```

## 🚀 Production Deployment

### 1. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 2. Configure Environment
Copy `.env.test.md` to `.env.production.md` and update credentials:
```bash
cp .env.test.md .env.production.md
```

### 3. Update Production Credentials
Edit `.env.production.md`:
```env
PAWAPAY_ENVIRONMENT=production
PAWAPAY_API_TOKEN=your_production_token
PAWAPAY_WEBHOOK_SECRET=your_production_webhook_secret
PAWAPAY_API_URL=https://api.pawapay.io
```

## 💻 Usage Example

```php
<?php
require_once 'vendor/autoload.php';

use Myzuwa\PawaPay\PawaPay;

// Load production configuration
$config = [
    'api' => [
        'token' => getenv('PAWAPAY_API_TOKEN'),
        'base_url' => getenv('PAWAPAY_API_URL')
    ],
    'webhook_secret' => getenv('PAWAPAY_WEBHOOK_SECRET'),
    'environment' => 'production'
];

// Initialize SDK
$pawaPay = new PawaPay($config);

// Initiate payment
$depositData = [
    'amount' => '100.00',
    'currency' => 'ZMW',
    'phoneNumber' => '260971234567',
    'provider' => 'zamtel',
    'reference' => 'order_123'
];

$response = $pawaPay->initiateDeposit($depositData);
echo "Deposit ID: " . $response['depositId'];
```

## 🔧 Configuration

### Required Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `PAWAPAY_API_TOKEN` | PawaPay API bearer token | ✅ |
| `PAWAPAY_WEBHOOK_SECRET` | Webhook signature secret | ✅ |
| `PAWAPAY_API_URL` | API base URL | ✅ |
| `PAWAPAY_WEBHOOK_URL` | Webhook endpoint URL | ✅ |

### Optional Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PAWAPAY_ENVIRONMENT` | Environment mode | `sandbox` |
| `APP_DEBUG` | Debug mode | `false` |
| `LOG_LEVEL` | Logging level | `error` |

## 🧪 Testing

The SDK includes comprehensive testing in the `../api/` directory:

- **Test Dashboard**: `../index.php`
- **Individual Tests**: `../api/test-*.php`
- **Test Runner**: `../api/test-runner.php`

## 📚 Documentation

- **Integration Guide**: `../Docs/integration_guide.md`
- **API Documentation**: `../Docs/pawapay_documentation.md`
- **Testing Guide**: `../TESTING_README.md`

## 🔒 Security Features

- ✅ Webhook signature verification
- ✅ Input validation and sanitization
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ CSRF protection (for webhooks)

## 🚨 Production Checklist

- [ ] Update API credentials in `.env.production.md`
- [ ] Configure webhook endpoint URL
- [ ] Set up SSL certificates
- [ ] Test with production credentials
- [ ] Configure monitoring and logging
- [ ] Set up backup strategies
- [ ] Review security settings

## 📞 Support

For production issues:
1. Check the troubleshooting guide in `../TESTING_README.md`
2. Review PHP error logs
3. Verify API credentials and connectivity
4. Test with sandbox environment first

---

**Production Ready**: ✅ All SDK files are organized for production deployment
