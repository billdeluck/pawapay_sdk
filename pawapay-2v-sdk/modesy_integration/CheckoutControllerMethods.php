<?php
/**
 * PawaPay CheckoutController Methods for Modesy Integration
 * 
 * Add these methods to your app/Controllers/CheckoutController.php file.
 * These methods handle payment completion and webhook processing for PawaPay.
 *
 * @package     Modesy\PawaPay\Integration
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

// =============================================================================
// ADD THESE METHODS TO YOUR CheckoutController.php CLASS
// =============================================================================

/**
 * Handles the return from PawaPay after user payment completion.
 * This function is called when the user is redirected back to your site.
 * 
 * Route: GET /checkout/complete-pawapay-payment
 */
public function completePawaPayPayment()
{
    try {
        // 1. Get parameters from the return URL
        $depositId = $this->request->getGet('depositId');
        $checkoutToken = $this->request->getGet('checkout_token');
        $customerPhone = $this->request->getGet('customer_phone');
        $customerEmail = $this->request->getGet('customer_email');

        // 2. Basic validation
        if (empty($depositId)) {
            log_message('error', 'PawaPay: Payment completion called without depositId');
            return $this->paymentErrorResponse('Invalid payment reference');
        }

        // 3. Load PawaPay configuration
        $config = getPaymentGateway('pawapay');
        if (empty($config) || !$config->status) {
            log_message('error', 'PawaPay: Gateway configuration not found or disabled');
            return $this->paymentErrorResponse('Payment gateway configuration error');
        }

        // 4. Initialize PawaPay SDK
        require_once FCPATH . 'vendor/autoload.php';
        
        use Myzuwa\PawaPay\PaymentPageFacade;
        use Myzuwa\PawaPay\Service\ModesyIntegrationService;
        
        $sdkConfig = [
            'api' => [
                'token' => $config->secret_key,
                'base_url' => $config->environment === 'production' 
                    ? 'https://api.pawapay.io' 
                    : 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config->webhook_secret,
            'environment' => $config->environment
        ];

        $paymentPageFacade = new PaymentPageFacade($sdkConfig);
        $modesyIntegration = new ModesyIntegrationService($paymentPageFacade, $sdkConfig);

        // 5. Handle payment return via facade
        $returnResult = $paymentPageFacade->handlePaymentReturn($depositId, $this->request->getGet());

        // 6. Find checkout session using deposit ID or checkout token
        $checkout = $this->findCheckoutByDepositId($depositId, $checkoutToken);
        if (empty($checkout)) {
            log_message('error', "PawaPay: Checkout not found for depositId: {$depositId}");
            return $this->paymentErrorResponse('Checkout session not found');
        }

        // 7. Check if already processed
        if ($checkout->status === 'paid') {
            $redirectUrl = $this->checkoutModel->createOrderRedirectUrl($checkout);
            return redirect()->to($redirectUrl ?? base_url('orders'));
        }

        // 8. Process based on payment status
        switch ($returnResult['status']) {
            case 'completed':
                return $this->processPawaPayOrder($checkout, $returnResult, $modesyIntegration, false);
                
            case 'pending':
            case 'processing':
            case 'reconciling':
                // Show waiting page with status polling
                return view('cart/payment_waiting', [
                    'depositId' => $depositId,
                    'status' => $returnResult['status'],
                    'message' => $returnResult['message'] ?? 'Payment is being processed',
                    'statusCheckUrl' => base_url("payment/status/{$depositId}")
                ]);
                
            case 'failed':
            case 'rejected':
                // Handle failed payment
                $failureInfo = $paymentPageFacade->handleProcessingFailure($depositId);
                return view('cart/payment_failed', [
                    'error' => $returnResult['message'] ?? 'Payment failed',
                    'failureInfo' => $failureInfo,
                    'retryUrl' => base_url('cart/checkout')
                ]);
                
            default:
                log_message('warning', "PawaPay: Unknown payment status: {$returnResult['status']}");
                return $this->paymentErrorResponse('Unknown payment status');
        }

    } catch (Exception $e) {
        log_message('error', 'PawaPay completion error: ' . $e->getMessage());
        return $this->paymentErrorResponse('Payment processing failed: ' . $e->getMessage());
    }
}

/**
 * Handles incoming webhook notifications from PawaPay.
 * This ensures payment confirmation even if the user doesn't return properly.
 * 
 * Route: POST /payment/webhook/pawapay
 */
public function handlePawaPayWebhook()
{
    try {
        // 1. Get webhook payload and signature
        $payload = $this->request->getJSON(true);
        $signature = $this->request->getHeaderLine('X-PawaPay-Signature');
        
        if (empty($payload)) {
            log_message('error', 'PawaPay Webhook: Empty payload received');
            return $this->response->setStatusCode(400, 'Bad Request');
        }

        // 2. Load configuration
        $config = getPaymentGateway('pawapay');
        if (empty($config) || !$config->status) {
            log_message('error', 'PawaPay Webhook: Configuration missing or gateway disabled');
            return $this->response->setStatusCode(500, 'Configuration Error');
        }

        // 3. Initialize SDK
        require_once FCPATH . 'vendor/autoload.php';
        
        use Myzuwa\PawaPay\PaymentPageFacade;
        use Myzuwa\PawaPay\Service\ModesyIntegrationService;
        
        $sdkConfig = [
            'api' => [
                'token' => $config->secret_key,
                'base_url' => $config->environment === 'production' 
                    ? 'https://api.pawapay.io' 
                    : 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config->webhook_secret,
            'environment' => $config->environment
        ];

        $paymentPageFacade = new PaymentPageFacade($sdkConfig);
        $modesyIntegration = new ModesyIntegrationService($paymentPageFacade, $sdkConfig);

        // 4. Process webhook
        $webhookResult = $modesyIntegration->processModesyWebhook($payload, $signature);
        
        if (!$webhookResult['success']) {
            log_message('error', 'PawaPay Webhook: Processing failed');
            return $this->response->setStatusCode(400, 'Webhook Processing Failed');
        }

        $depositId = $webhookResult['deposit_id'];

        // 5. Find checkout session
        $checkout = $this->findCheckoutByDepositId($depositId);
        
        if (empty($checkout)) {
            log_message('warning', "PawaPay Webhook: Checkout not found for depositId: {$depositId}");
            // Return 200 to acknowledge receipt but log the issue
            return $this->response->setStatusCode(200, 'OK');
        }

        // 6. Check if already processed
        if ($checkout->status === 'paid') {
            return $this->response->setStatusCode(200, 'Already Processed');
        }

        // 7. Process payment if successful
        if ($webhookResult['status'] === 'completed') {
            $success = $this->processPawaPayOrder(
                $checkout, 
                $webhookResult, 
                $modesyIntegration, 
                true // isWebhook = true
            );
            
            if (!$success) {
                log_message('critical', "PawaPay Webhook: Order processing FAILED for depositId: {$depositId}");
            }
        }

        return $this->response->setStatusCode(200, 'OK');

    } catch (Throwable $e) {
        log_message('error', 'PawaPay Webhook Exception: ' . $e->getMessage());
        return $this->response->setStatusCode(500, 'Internal Server Error');
    }
}

/**
 * AJAX endpoint for checking payment status during waiting
 * 
 * Route: GET /payment/status/{depositId}
 */
public function checkPawaPayStatus($depositId = null)
{
    try {
        if (empty($depositId)) {
            return $this->response->setJSON(['error' => 'Missing deposit ID']);
        }

        // Load configuration and initialize SDK
        $config = getPaymentGateway('pawapay');
        if (empty($config)) {
            return $this->response->setJSON(['error' => 'Configuration error']);
        }

        require_once FCPATH . 'vendor/autoload.php';
        use Myzuwa\PawaPay\PaymentPageFacade;
        
        $sdkConfig = [
            'api' => [
                'token' => $config->secret_key,
                'base_url' => $config->environment === 'production' 
                    ? 'https://api.pawapay.io' 
                    : 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config->webhook_secret,
            'environment' => $config->environment
        ];

        $paymentPageFacade = new PaymentPageFacade($sdkConfig);
        
        // Check payment status
        $statusResult = $paymentPageFacade->checkPaymentStatus($depositId);
        
        // Find local checkout
        $checkout = $this->findCheckoutByDepositId($depositId);
        
        return $this->response->setJSON([
            'success' => true,
            'depositId' => $depositId,
            'status' => $statusResult['status'],
            'message' => $this->getStatusMessage($statusResult['status']),
            'isCompleted' => in_array($statusResult['status'], ['completed', 'failed', 'rejected']),
            'orderExists' => $checkout && $checkout->status === 'paid',
            'nextAction' => $this->getNextAction($statusResult['status'], $checkout)
        ]);

    } catch (Exception $e) {
        return $this->response->setJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PRIVATE HELPER METHODS
// =============================================================================

/**
 * Process PawaPay order completion
 */
private function processPawaPayOrder($checkout, $paymentResult, $modesyIntegration, $isWebhook = false)
{
    try {
        // 1. Validate payment details
        $paymentDetails = [
            'amount' => $checkout->grand_total,
            'currency' => $checkout->currency_code,
            'status' => $paymentResult['status'] ?? 'completed'
        ];
        
        $modesyIntegration->validatePaymentDetails($checkout, $paymentDetails);

        // 2. Create Modesy transaction object
        $transaction = $modesyIntegration->createModesyTransaction([
            'depositId' => $paymentResult['depositId'] ?? $paymentResult['deposit_id'],
            'status' => $paymentResult['status'],
            'status_text' => $paymentResult['status'] ?? 'completed'
        ]);

        // 3. Process multi-vendor if applicable
        if (isset($checkout->cart_items) && count($checkout->cart_items) > 1) {
            $vendorResult = $modesyIntegration->processMultiVendorPayment($checkout, $paymentDetails);
            
            // Log vendor processing details
            $modesyIntegration->logPaymentActivity('multi_vendor_processed', [
                'checkout_token' => $checkout->checkout_token,
                'vendors' => array_keys($vendorResult['vendors']),
                'total_commission' => array_sum(array_column($vendorResult['commissions'], 'commission_amount'))
            ]);
        }

        // 4. Call Modesy's core payment handler
        $result = $this->handlePayment($checkout, $transaction, $isWebhook);

        // 5. Log successful processing
        $modesyIntegration->logPaymentActivity('order_completed', [
            'checkout_token' => $checkout->checkout_token,
            'deposit_id' => $transaction->payment_id,
            'amount' => $checkout->grand_total,
            'is_webhook' => $isWebhook
        ]);

        // 6. Handle response based on context
        if ($isWebhook) {
            return $result && $result['status'] === 1;
        } else {
            return $this->handleCheckoutResponse($result);
        }

    } catch (Exception $e) {
        // Log error
        $errorResult = $modesyIntegration->handleModesyPaymentError($e, [
            'checkout_token' => $checkout->checkout_token,
            'is_webhook' => $isWebhook
        ]);
        
        $modesyIntegration->logPaymentActivity('order_processing_failed', $errorResult);
        
        if ($isWebhook) {
            return false;
        } else {
            return $this->paymentErrorResponse($errorResult['user_message']);
        }
    }
}

/**
 * Find checkout by deposit ID or checkout token
 */
private function findCheckoutByDepositId($depositId, $checkoutToken = null)
{
    // First try to find by checkout token if available
    if ($checkoutToken) {
        $checkout = $this->checkoutModel->getCheckoutByToken($checkoutToken);
        if ($checkout) {
            return $checkout;
        }
    }

    // Try to find by deposit ID in session metadata or custom field
    // Note: This depends on how you store the deposit ID during payment creation
    // You may need to modify this based on your implementation
    
    // Option 1: If you store deposit_id in checkout record
    $checkout = $this->checkoutModel->getCheckoutByDepositId($depositId);
    if ($checkout) {
        return $checkout;
    }

    // Option 2: Search in payment sessions storage
    $sessionPath = FCPATH . 'vendor/myzuwa/pawapay-2v-sdk/storage/payment_sessions/';
    if (file_exists($sessionPath . $depositId . '.json')) {
        $sessionData = json_decode(file_get_contents($sessionPath . $depositId . '.json'), true);
        if (isset($sessionData['request_data']['checkout_token'])) {
            return $this->checkoutModel->getCheckoutByToken($sessionData['request_data']['checkout_token']);
        }
    }

    return null;
}

/**
 * Get user-friendly status message
 */
private function getStatusMessage($status)
{
    $messages = [
        'pending' => 'Your payment is being processed. Please wait.',
        'processing' => 'Payment is currently being processed.',
        'reconciling' => 'Payment is being verified. This may take a few minutes.',
        'completed' => 'Payment completed successfully!',
        'failed' => 'Payment failed. Please try again.',
        'rejected' => 'Payment was rejected. Please try a different payment method.',
        'unknown' => 'Payment status is unknown. Please contact support.'
    ];
    
    return $messages[$status] ?? $messages['unknown'];
}

/**
 * Get next action based on payment status
 */
private function getNextAction($status, $checkout)
{
    if ($checkout && $checkout->status === 'paid') {
        return 'redirect_to_order';
    }
    
    switch ($status) {
        case 'completed':
            return 'redirect_to_success';
        case 'failed':
        case 'rejected':
            return 'show_retry_options';
        case 'pending':
        case 'processing':
        case 'reconciling':
            return 'continue_waiting';
        default:
            return 'contact_support';
    }
}

// =============================================================================
// ADD THESE ROUTES TO YOUR app/Config/Routes.php
// =============================================================================

/*
// PawaPay payment completion routes
$routes->get('checkout/complete-pawapay-payment', 'CheckoutController::completePawaPayPayment');
$routes->post('payment/webhook/pawapay', 'CheckoutController::handlePawaPayWebhook');
$routes->get('payment/status/(:segment)', 'CheckoutController::checkPawaPayStatus/$1');

// Add the webhook route to CSRF exceptions in app/Config/Filters.php:
'except' => [
    'payment/webhook/pawapay',
    // ... your existing exceptions
];
*/