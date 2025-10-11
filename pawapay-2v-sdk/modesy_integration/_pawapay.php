<?php
/**
 * PawaPay Payment Method View for Modesy Integration
 * 
 * This view file handles the payment form display and payment initiation
 * for PawaPay gateway integration with Modesy marketplace platform.
 * 
 * Place this file in: app/Views/cart/payment_methods/_pawapay.php
 *
 * @package     Modesy\PawaPay\Integration
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

// Security check - prevent direct access
if (!defined('BASEPATH') && !defined('FCPATH')) {
    exit('No direct script access allowed');
}

try {
    // Load PawaPay SDK configuration from Modesy's payment gateway settings
    $pawaPayConfig = getPaymentGateway('pawapay');
    
    if (empty($pawaPayConfig) || !$pawaPayConfig->status) {
        throw new Exception('PawaPay gateway is not configured or disabled');
    }

    // Initialize PawaPay SDK with Modesy configuration
    require_once FCPATH . 'vendor/autoload.php';
    
    use Myzuwa\PawaPay\PaymentPageFacade;
    use Myzuwa\PawaPay\Service\ModesyIntegrationService;
    use Myzuwa\PawaPay\Exception\PaymentGatewayException;

    // Configure SDK with Modesy settings
    $sdkConfig = [
        'api' => [
            'token' => $pawaPayConfig->secret_key, // Using secret_key as API token
            'base_url' => $pawaPayConfig->environment === 'production' 
                ? 'https://api.pawapay.io' 
                : 'https://api.sandbox.pawapay.io'
        ],
        'webhook_secret' => $pawaPayConfig->webhook_secret,
        'environment' => $pawaPayConfig->environment
    ];

    $paymentPageFacade = new PaymentPageFacade($sdkConfig);
    $modesyIntegration = new ModesyIntegrationService($paymentPageFacade, $sdkConfig);
    
    // Determine payment type based on checkout content
    $paymentType = 'product_sale'; // Default
    if (isset($checkout->payment_type)) {
        $paymentType = $checkout->payment_type;
    } elseif (isset($checkout->service_type)) {
        $paymentType = 'service_payment';
    } elseif (isset($checkout->wallet_deposit)) {
        $paymentType = 'wallet_deposit';
    }

    // Prepare payment parameters based on Modesy checkout object
    $paymentParams = [
        'amount' => number_format($checkout->grand_total, 2, '.', ''),
        'currency' => $checkout->currency_code,
        'country' => $checkout->country ?? 'KEN', // Default to Kenya
        'returnUrl' => base_url('checkout/complete-pawapay-payment'),
        'checkoutToken' => $checkout->checkout_token,
        'description' => 'Payment for Order #' . ($checkout->id ?? time()),
        'customerEmail' => $checkout->customer_email ?? '',
        'orderId' => $checkout->id ?? uniqid('order_')
    ];

    // Add customer phone if available
    if (!empty($checkout->customer_phone)) {
        $paymentParams['customerPhone'] = $checkout->customer_phone;
    }

    // Handle different payment types
    switch ($paymentType) {
        case 'service_payment':
            if (isset($checkout->service_type)) {
                $paymentParams['service_description'] = 'Service: ' . $checkout->service_type;
                if ($checkout->service_type === 'membership') {
                    $paymentParams['subscriptionId'] = 'membership_' . ($checkout->user_id ?? time());
                }
            }
            break;
            
        case 'wallet_deposit':
            $paymentParams['userId'] = $checkout->user_id ?? 'anonymous';
            break;
            
        case 'featured_promotion':
            $paymentParams['promotion_fee'] = $paymentParams['amount'];
            $paymentParams['product_id'] = $checkout->product_id ?? 'unknown';
            break;
    }

    // Create payment page based on type
    try {
        $paymentResult = $modesyIntegration->handlePaymentType($paymentType, $paymentParams);
        $redirectUrl = $paymentResult['redirect_url'];
        
        // Log payment initiation
        $modesyIntegration->logPaymentActivity('payment_initiated', [
            'checkout_token' => $checkout->checkout_token,
            'payment_type' => $paymentType,
            'amount' => $checkout->grand_total,
            'currency' => $checkout->currency_code
        ]);
        
    } catch (PaymentGatewayException $e) {
        error_log('PawaPay Payment Initiation Error: ' . $e->getMessage());
        $error = $e->getMessage();
    }

} catch (Exception $e) {
    error_log('PawaPay View Error: ' . $e->getMessage());
    $error = 'Payment system temporarily unavailable. Please try again later.';
}
?>

<!-- PawaPay Payment Method Display -->
<div class="payment-method-container" id="pawapay-payment-container">
    <div class="payment-method-header">
        <div class="payment-logos">
            <?php if (!empty($pawaPayConfig->logos)): ?>
                <?php $logos = explode(',', $pawaPayConfig->logos); ?>
                <?php foreach ($logos as $logo): ?>
                    <img src="<?= base_url('assets/img/payment/' . trim($logo) . '.svg') ?>" 
                         alt="<?= ucfirst(trim($logo)) ?>" 
                         class="payment-logo">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="<?= base_url('assets/img/payment/pawapay.svg') ?>" 
                     alt="PawaPay" 
                     class="payment-logo">
            <?php endif; ?>
        </div>
        <h4 class="payment-title"><?= trans('pawapay_mobile_money') ?></h4>
        <p class="payment-description"><?= trans('pawapay_description') ?></p>
    </div>

    <?php if (isset($error)): ?>
        <!-- Error Display -->
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <?= esc($error) ?>
        </div>
    <?php else: ?>
        
        <!-- Payment Information -->
        <div class="payment-info">
            <div class="payment-summary">
                <div class="row">
                    <div class="col-sm-6">
                        <strong><?= trans('total_amount') ?>:</strong>
                    </div>
                    <div class="col-sm-6 text-right">
                        <strong class="text-success">
                            <?= price_formatted($checkout->grand_total, $checkout->currency_code) ?>
                        </strong>
                    </div>
                </div>
                
                <?php if ($paymentType !== 'product_sale'): ?>
                <div class="row mt-2">
                    <div class="col-sm-6">
                        <strong><?= trans('payment_type') ?>:</strong>
                    </div>
                    <div class="col-sm-6 text-right">
                        <?= ucwords(str_replace('_', ' ', $paymentType)) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Money Information -->
        <div class="mobile-money-info">
            <h5><i class="fa fa-mobile"></i> <?= trans('mobile_money_payment') ?></h5>
            <div class="supported-networks">
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="network-item">
                            <img src="<?= base_url('assets/img/payment/mtn.svg') ?>" alt="MTN Mobile Money">
                            <span>MTN Mobile Money</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="network-item">
                            <img src="<?= base_url('assets/img/payment/airtel.svg') ?>" alt="Airtel Money">
                            <span>Airtel Money</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="network-item">
                            <img src="<?= base_url('assets/img/payment/safaricom.svg') ?>" alt="M-Pesa">
                            <span>M-Pesa</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="network-item">
                            <img src="<?= base_url('assets/img/payment/mobile-money.svg') ?>" alt="Other Networks">
                            <span><?= trans('other_networks') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div class="payment-instructions">
            <h6><i class="fa fa-info-circle"></i> <?= trans('how_to_pay') ?></h6>
            <ol class="instruction-list">
                <li><?= trans('pawapay_step_1') ?></li>
                <li><?= trans('pawapay_step_2') ?></li>
                <li><?= trans('pawapay_step_3') ?></li>
                <li><?= trans('pawapay_step_4') ?></li>
            </ol>
        </div>

        <!-- Customer Information Form -->
        <div class="customer-info-form" id="pawapay-customer-form">
            <h6><i class="fa fa-user"></i> <?= trans('customer_information') ?></h6>
            
            <div class="form-group">
                <label for="customer_phone"><?= trans('mobile_phone_number') ?> *</label>
                <input type="tel" 
                       class="form-control" 
                       id="customer_phone" 
                       name="customer_phone"
                       value="<?= esc($checkout->customer_phone ?? '') ?>"
                       placeholder="<?= trans('enter_mobile_number') ?>"
                       pattern="[0-9]{9,15}"
                       <?= !empty($checkout->customer_phone) ? 'readonly' : '' ?>>
                <small class="form-text text-muted">
                    <?= trans('phone_number_help') ?>
                </small>
            </div>

            <div class="form-group">
                <label for="customer_email"><?= trans('email_address') ?></label>
                <input type="email" 
                       class="form-control" 
                       id="customer_email" 
                       name="customer_email"
                       value="<?= esc($checkout->customer_email ?? '') ?>"
                       placeholder="<?= trans('enter_email_optional') ?>">
                <small class="form-text text-muted">
                    <?= trans('email_receipt_help') ?>
                </small>
            </div>
        </div>

        <!-- Payment Button -->
        <div class="payment-actions">
            <button type="button" 
                    class="btn btn-lg btn-success btn-block" 
                    id="pawapay-pay-button"
                    data-redirect-url="<?= esc($redirectUrl) ?>">
                <i class="fa fa-mobile"></i>
                <?= trans('pay_with_mobile_money') ?>
                <span class="amount"><?= price_formatted($checkout->grand_total, $checkout->currency_code) ?></span>
            </button>
        </div>

    <?php endif; ?>
</div>

<!-- PawaPay Payment Styles -->
<style>
.payment-method-container {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
    background: #fff;
}

.payment-method-header {
    text-align: center;
    margin-bottom: 20px;
}

.payment-logos {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.payment-logo {
    height: 32px;
    width: auto;
    max-width: 80px;
}

.payment-title {
    color: #2c3e50;
    margin-bottom: 5px;
}

.payment-description {
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 0;
}

.payment-info {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.mobile-money-info {
    margin-bottom: 20px;
}

.supported-networks {
    margin-top: 15px;
}

.network-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 13px;
}

.network-item img {
    width: 24px;
    height: 24px;
}

.payment-instructions {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 15px;
    margin-bottom: 20px;
}

.instruction-list {
    margin-bottom: 0;
    padding-left: 20px;
}

.instruction-list li {
    margin-bottom: 5px;
}

.customer-info-form {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.payment-actions {
    text-align: center;
}

#pawapay-pay-button {
    font-size: 16px;
    font-weight: bold;
    padding: 12px 30px;
    position: relative;
}

#pawapay-pay-button .amount {
    float: right;
    font-weight: normal;
}

@media (max-width: 768px) {
    .payment-logos {
        flex-wrap: wrap;
    }
    
    .payment-logo {
        height: 28px;
    }
    
    .network-item {
        justify-content: center;
    }
    
    #pawapay-pay-button .amount {
        float: none;
        display: block;
        margin-top: 5px;
    }
}
</style>

<!-- PawaPay Payment JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payButton = document.getElementById('pawapay-pay-button');
    const customerForm = document.getElementById('pawapay-customer-form');
    
    if (payButton) {
        payButton.addEventListener('click', function() {
            // Validate customer information
            if (!validateCustomerInfo()) {
                return;
            }
            
            // Disable button and show loading
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?= trans("processing_payment") ?>';
            
            // Get redirect URL
            const redirectUrl = payButton.getAttribute('data-redirect-url');
            
            // Add customer info to redirect URL if needed
            const updatedUrl = addCustomerInfoToUrl(redirectUrl);
            
            // Redirect to PawaPay payment page
            window.location.href = updatedUrl;
        });
    }
    
    function validateCustomerInfo() {
        const phoneInput = document.getElementById('customer_phone');
        const emailInput = document.getElementById('customer_email');
        
        // Validate phone number
        if (!phoneInput.value.trim()) {
            alert('<?= trans("phone_number_required") ?>');
            phoneInput.focus();
            return false;
        }
        
        // Basic phone number validation
        const phoneRegex = /^[0-9]{9,15}$/;
        const cleanPhone = phoneInput.value.replace(/[^0-9]/g, '');
        
        if (!phoneRegex.test(cleanPhone)) {
            alert('<?= trans("invalid_phone_number") ?>');
            phoneInput.focus();
            return false;
        }
        
        // Validate email if provided
        if (emailInput.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                alert('<?= trans("invalid_email_format") ?>');
                emailInput.focus();
                return false;
            }
        }
        
        return true;
    }
    
    function addCustomerInfoToUrl(baseUrl) {
        const phone = document.getElementById('customer_phone').value.trim();
        const email = document.getElementById('customer_email').value.trim();
        
        const url = new URL(baseUrl);
        
        if (phone) {
            url.searchParams.set('customer_phone', phone);
        }
        
        if (email) {
            url.searchParams.set('customer_email', email);
        }
        
        return url.toString();
    }
});

// Handle payment page return
window.addEventListener('message', function(event) {
    // Handle postMessage communication from payment page if needed
    if (event.origin === '<?= $pawaPayConfig->environment === "production" ? "https://api.pawapay.io" : "https://api.sandbox.pawapay.io" ?>') {
        if (event.data.type === 'payment_status') {
            // Handle payment status updates
            console.log('Payment status update:', event.data);
        }
    }
});
</script>

<!-- Translation fallbacks for missing translations -->
<script>
// Provide JavaScript translations for dynamic content
window.pawaPayTranslations = {
    processing_payment: '<?= trans_exists("processing_payment") ? trans("processing_payment") : "Processing Payment..." ?>',
    phone_number_required: '<?= trans_exists("phone_number_required") ? trans("phone_number_required") : "Phone number is required" ?>',
    invalid_phone_number: '<?= trans_exists("invalid_phone_number") ? trans("invalid_phone_number") : "Please enter a valid phone number" ?>',
    invalid_email_format: '<?= trans_exists("invalid_email_format") ? trans("invalid_email_format") : "Please enter a valid email address" ?>'
};
</script>