# 🚀 Modesy PawaPay - 15-Minute Quick Setup Guide

> **Get PawaPay mobile money payments working in your Modesy marketplace in 15 minutes!**

## ⏱️ Quick Setup Checklist

- [ ] **Step 1:** Database setup (2 minutes)
- [ ] **Step 2:** Copy files (3 minutes)  
- [ ] **Step 3:** Configure credentials (2 minutes)
- [ ] **Step 4:** Update routes (3 minutes)
- [ ] **Step 5:** Test payment (5 minutes)

---

## 1️⃣ Database Setup (2 minutes)

```bash
# Run the migration script
mysql -u your_username -p your_database < database_migration.sql

# Verify tables created
mysql -u your_username -p your_database -e "SHOW TABLES LIKE '%payment%'"
```

**Expected output:** `payment_gateways`, `transactions`, `webhook_logs`

---

## 2️⃣ Copy Integration Files (3 minutes)

```bash
# Copy to your Modesy installation
cp ModesyIntegrationService.php /path/to/modesy/app/Services/
cp DatabaseConfig.php /path/to/modesy/app/Services/
cp _pawapay.php /path/to/modesy/resources/views/payment/methods/

# Copy controller methods to your CheckoutController
# (Add the 3 methods from CheckoutControllerMethods.php)
```

---

## 3️⃣ Configure PawaPay Credentials (2 minutes)

```sql
-- Update your PawaPay configuration
UPDATE payment_gateways SET
    public_key = 'YOUR_PAWAPAY_API_TOKEN',
    secret_key = 'YOUR_WEBHOOK_SECRET',
    environment = 'sandbox',
    webhook_url = 'https://yourdomain.com/pawapay/webhook',
    redirect_urls = '{"success_url":"https://yourdomain.com/pawapay/callback/{transactionId}","cancel_url":"https://yourdomain.com/checkout?cancelled=1","failure_url":"https://yourdomain.com/checkout?error=payment_failed"}'
WHERE payment_option = 'pawapay';
```

---

## 4️⃣ Update Routes & CSRF (3 minutes)

### Laravel Modesy:
```php
// web.php
Route::post('checkout/pawapay/initiate', 'CheckoutController@initiatePawaPayPayment')
    ->name('pawapay.initiate')->middleware('auth');
Route::get('pawapay/callback/{transactionId}', 'CheckoutController@completePawaPayPayment')
    ->name('pawapay.callback');

// api.php  
Route::post('pawapay/webhook', 'CheckoutController@handlePawaPayWebhook')
    ->name('pawapay.webhook');

// VerifyCsrfToken.php
protected $except = [
    'pawapay/webhook',
    'payment/pawapay/webhook'
];
```

### CodeIgniter Modesy:
```php
// Config/Routes.php
$routes->post('checkout/pawapay/initiate', 'Checkout::initiatePawaPayPayment');
$routes->get('pawapay/callback/(:any)', 'Checkout::completePawaPayPayment/$1');
$routes->post('pawapay/webhook', 'Checkout::handlePawaPayWebhook', ['filter' => 'cors']);

// Config/Security.php
public $csrfExcludeURIs = ['pawapay/webhook'];
```

---

## 5️⃣ Test Payment Flow (5 minutes)

### Quick Test:
1. **Create test order** in your Modesy checkout
2. **Select PawaPay** as payment method  
3. **Enter phone number**: `+256700000001` (sandbox test number)
4. **Complete payment** on PawaPay redirect page
5. **Verify success** - should return to order confirmation

### Test Commands:
```bash
# Test webhook endpoint
curl -X POST https://yourdomain.com/pawapay/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'

# Expected: 200 OK or authentication error (both mean endpoint works)
```

---

## ✅ Verification Checklist

After setup, verify these work:

- [ ] **Payment initiation** - PawaPay redirect loads correctly
- [ ] **Payment completion** - Returns to success page after payment  
- [ ] **Order status** - Order marked as paid in Modesy admin
- [ ] **Webhook processing** - Check webhook_logs table for entries
- [ ] **Multi-vendor** - Commission calculations work for marketplace orders

---

## 🚨 Common Quick Fixes

### Payment redirect fails:
```php
// Check PawaPay credentials are correct
$gateway = $dbConfig->getPaymentGatewayConfig('pawapay');
var_dump($gateway); // Should show your API token
```

### Webhook not working:
```bash
# Check webhook URL accessibility
curl -I https://yourdomain.com/pawapay/webhook
# Should return 200 or 405, not 404
```

### CSRF errors:
```php
// Ensure webhook URL is in CSRF exclusion list
// Clear route cache: php artisan route:cache
```

---

## 🆘 Need Help?

**Stuck? Get instant help:**

1. **Check logs**: `tail -f storage/logs/laravel.log`
2. **Enable debug**: Set `APP_DEBUG=true` in `.env`  
3. **Test with sandbox**: Use `environment = 'sandbox'` first
4. **Contact support**: support@pawapay.co.uk

---

## 🎯 Next Steps

After basic setup works:

1. **Switch to production** - Update credentials and environment
2. **Customize UI** - Style the payment form to match your brand
3. **Add error handling** - Implement custom error messages
4. **Set up monitoring** - Monitor webhook logs and failed payments
5. **Run full tests** - Use the comprehensive test suite

---

**🎉 Congratulations! Your Modesy marketplace now accepts mobile money payments through PawaPay!**

**Total setup time:** ~15 minutes  
**Payment processing:** Instant  
**Supported networks:** MTN, Airtel, M-Pesa, Orange Money, and more!