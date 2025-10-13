<?php
/**
 * PawaPay Modesy Integration Service
 * 
 * Core service for seamless integration with Modesy marketplace platform.
 * Handles transaction processing, multi-vendor support, and commission calculations.
 *
 * @package     Myzuwa\PawaPay\Service
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Service;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use Myzuwa\PawaPay\PaymentPageFacade;

class ModesyIntegrationService
{
    /** @var PaymentPageFacade */
    private $paymentPageFacade;
    
    /** @var array Configuration */
    private $config;
    
    /** @var string Gateway name key */
    private $gatewayNameKey = 'pawapay';

    public function __construct(PaymentPageFacade $paymentPageFacade, array $config)
    {
        $this->paymentPageFacade = $paymentPageFacade;
        $this->config = $config;
    }

    // =============================================================================
    // MODESY TRANSACTION PROCESSING
    // =============================================================================

    /**
     * Create Modesy-compliant transaction object
     *
     * @param array $paymentDetails Payment details from PawaPay
     * @return object Transaction object for handlePayment()
     */
    public function createModesyTransaction(array $paymentDetails): object
    {
        return (object)[
            'payment_id' => $paymentDetails['depositId'] ?? $paymentDetails['transaction_id'],
            'status_text' => $paymentDetails['status_text'] ?? $paymentDetails['status'],
            'status' => $this->normalizePaymentStatus($paymentDetails['status']),
            'payment_method' => $this->gatewayNameKey
        ];
    }

    /**
     * Normalize payment status for Modesy compatibility
     *
     * @param string $status PawaPay status
     * @return int Modesy status (1 = success, 0 = failed)
     */
    private function normalizePaymentStatus(string $status): int
    {
        $successStatuses = [
            'completed', 'COMPLETED', 
            'successful', 'SUCCESSFUL',
            'paid', 'PAID',
            'success', 'SUCCESS'
        ];
        
        return in_array(strtolower($status), array_map('strtolower', $successStatuses)) ? 1 : 0;
    }

    /**
     * Validate payment amount and currency against checkout
     *
     * @param object $checkout Modesy checkout object
     * @param array $paymentDetails Payment details from gateway
     * @throws PaymentGatewayException
     */
    public function validatePaymentDetails(object $checkout, array $paymentDetails): void
    {
        // Amount validation (handle both decimal and subunit formats)
        $paidAmount = $this->normalizeAmount($paymentDetails['amount'] ?? 0);
        $expectedAmount = $this->normalizeAmount($checkout->grand_total);
        
        if ($paidAmount < $expectedAmount) {
            throw new PaymentGatewayException(
                "PawaPay: Amount mismatch for token {$checkout->checkout_token}. " .
                "Expected: {$expectedAmount}, Paid: {$paidAmount}"
            );
        }

        // Currency validation
        $paidCurrency = strtoupper($paymentDetails['currency'] ?? '');
        $expectedCurrency = strtoupper($checkout->currency_code ?? '');
        
        if ($paidCurrency !== $expectedCurrency) {
            throw new PaymentGatewayException(
                "PawaPay: Currency mismatch for token {$checkout->checkout_token}. " .
                "Expected: {$expectedCurrency}, Paid: {$paidCurrency}"
            );
        }
    }

    /**
     * Normalize amount to consistent format (subunit)
     *
     * @param mixed $amount Amount in various formats
     * @return int Amount in subunits (e.g., 1099 for $10.99)
     */
    private function normalizeAmount($amount): int
    {
        if (is_string($amount)) {
            $amount = (float) $amount;
        }
        
        // If amount is already in subunits (> 1000), assume it's correct
        if ($amount >= 1000) {
            return (int) $amount;
        }
        
        // Convert decimal to subunits
        return (int) round($amount * 100);
    }

    // =============================================================================
    // MULTI-VENDOR SUPPORT
    // =============================================================================

    /**
     * Process multi-vendor cart payment
     *
     * @param object $checkout Modesy checkout object
     * @param array $paymentDetails Payment details
     * @return array Processing result with vendor breakdowns
     */
    public function processMultiVendorPayment(object $checkout, array $paymentDetails): array
    {
        $result = [
            'total_amount' => $checkout->grand_total,
            'currency' => $checkout->currency_code,
            'vendors' => [],
            'commissions' => []
        ];

        // Extract vendor information from checkout
        if (isset($checkout->cart_items) && is_array($checkout->cart_items)) {
            foreach ($checkout->cart_items as $item) {
                $vendorId = $item->vendor_id ?? null;
                if ($vendorId) {
                    if (!isset($result['vendors'][$vendorId])) {
                        $result['vendors'][$vendorId] = [
                            'vendor_id' => $vendorId,
                            'items' => [],
                            'subtotal' => 0,
                            'commission_rate' => $item->commission_rate ?? 0,
                            'commission_amount' => 0
                        ];
                    }
                    
                    $itemTotal = ($item->price ?? 0) * ($item->quantity ?? 1);
                    $result['vendors'][$vendorId]['items'][] = $item;
                    $result['vendors'][$vendorId]['subtotal'] += $itemTotal;
                    
                    // Calculate commission
                    $commissionRate = $item->commission_rate ?? 0;
                    $commissionAmount = $itemTotal * ($commissionRate / 100);
                    $result['vendors'][$vendorId]['commission_amount'] += $commissionAmount;
                    
                    $result['commissions'][] = [
                        'vendor_id' => $vendorId,
                        'item_id' => $item->id ?? null,
                        'amount' => $itemTotal,
                        'commission_rate' => $commissionRate,
                        'commission_amount' => $commissionAmount
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Calculate hierarchical commission rates
     *
     * @param array $item Item details
     * @param object $vendor Vendor information
     * @param object $category Category information
     * @return float Final commission rate
     */
    public function calculateHierarchicalCommission(array $item, object $vendor = null, object $category = null): float
    {
        // Priority: Product → Vendor → Category → Global
        
        // 1. Product-specific commission
        if (!empty($item['commission_rate'])) {
            return (float) $item['commission_rate'];
        }
        
        // 2. Vendor-specific commission
        if ($vendor && !empty($vendor->commission_rate)) {
            return (float) $vendor->commission_rate;
        }
        
        // 3. Category-specific commission
        if ($category && !empty($category->commission_rate)) {
            return (float) $category->commission_rate;
        }
        
        // 4. Global default commission
        return (float) ($this->config['default_commission_rate'] ?? 5.0);
    }

    // =============================================================================
    // PAYMENT TYPE HANDLING
    // =============================================================================

    /**
     * Handle different Modesy payment types
     *
     * @param string $paymentType Type of payment
     * @param array $params Payment parameters
     * @return array Payment processing result
     */
    public function handlePaymentType(string $paymentType, array $params): array
    {
        switch (strtolower($paymentType)) {
            case 'product_sale':
                return $this->handleProductSalePayment($params);
                
            case 'service_payment':
                return $this->handleServicePayment($params);
                
            case 'wallet_deposit':
                return $this->handleWalletDepositPayment($params);
                
            case 'membership_plan':
                return $this->handleMembershipPayment($params);
                
            case 'featured_promotion':
                return $this->handleFeaturedPromotionPayment($params);
                
            case 'commission_payment':
                return $this->handleCommissionPayment($params);
                
            default:
                throw new PaymentGatewayException("Unsupported payment type: {$paymentType}");
        }
    }

    /**
     * Handle product sale payment
     */
    private function handleProductSalePayment(array $params): array
    {
        return [
            'payment_type' => 'product_sale',
            'redirect_url' => $this->paymentPageFacade->createEcommerceCheckout($params)['redirectUrl'],
            'requires_inventory_update' => true,
            'requires_commission_calculation' => true
        ];
    }

    /**
     * Handle service payment (membership, promotions)
     */
    private function handleServicePayment(array $params): array
    {
        return [
            'payment_type' => 'service_payment',
            'redirect_url' => $this->paymentPageFacade->createFixedAmountPaymentPage(
                $params['amount'],
                $params['country'],
                $params['returnUrl'],
                $params['service_description']
            )['redirectUrl'],
            'requires_service_activation' => true,
            'requires_commission_calculation' => false
        ];
    }

    /**
     * Handle wallet deposit payment
     */
    private function handleWalletDepositPayment(array $params): array
    {
        return [
            'payment_type' => 'wallet_deposit',
            'redirect_url' => $this->paymentPageFacade->createWalletTopup($params)['redirectUrl'],
            'requires_balance_update' => true,
            'requires_commission_calculation' => false
        ];
    }

    /**
     * Handle membership plan payment
     */
    private function handleMembershipPayment(array $params): array
    {
        return [
            'payment_type' => 'membership_plan',
            'redirect_url' => $this->paymentPageFacade->createSubscriptionBilling($params)['redirectUrl'],
            'requires_plan_activation' => true,
            'requires_commission_calculation' => false
        ];
    }

    /**
     * Handle featured promotion payment
     */
    private function handleFeaturedPromotionPayment(array $params): array
    {
        return [
            'payment_type' => 'featured_promotion',
            'redirect_url' => $this->paymentPageFacade->createFixedAmountPaymentPage(
                $params['promotion_fee'],
                $params['country'],
                $params['returnUrl'],
                "Featured promotion for product #{$params['product_id']}"
            )['redirectUrl'],
            'requires_feature_activation' => true,
            'requires_commission_calculation' => false
        ];
    }

    /**
     * Handle commission payment
     */
    private function handleCommissionPayment(array $params): array
    {
        return [
            'payment_type' => 'commission_payment',
            'redirect_url' => $this->paymentPageFacade->createFixedAmountAndPhonePaymentPage(
                $params['vendor_phone'],
                $params['commission_amount'],
                $params['returnUrl'],
                "Commission payment for vendor #{$params['vendor_id']}"
            )['redirectUrl'],
            'requires_commission_settlement' => true,
            'requires_commission_calculation' => false
        ];
    }

    // =============================================================================
    // WEBHOOK PROCESSING
    // =============================================================================

    /**
     * Process PawaPay webhook for Modesy
     *
     * @param array $webhookData Raw webhook data
     * @param string $signature Webhook signature
     * @return array Processing result
     */
    public function processModesyWebhook(array $webhookData, string $signature): array
    {
        // Verify webhook signature
        $result = $this->paymentPageFacade->processWebhook($webhookData, $signature);
        
        if (!$result['success']) {
            throw new PaymentGatewayException('Webhook verification failed');
        }

        $depositId = $webhookData['depositId'] ?? null;
        if (!$depositId) {
            throw new PaymentGatewayException('Missing depositId in webhook data');
        }

        return [
            'success' => true,
            'deposit_id' => $depositId,
            'status' => $result['status'],
            'transaction_data' => $this->createModesyTransaction([
                'depositId' => $depositId,
                'status' => $result['status'],
                'status_text' => $webhookData['status'] ?? $result['status']
            ]),
            'requires_order_processing' => true,
            'webhook_processed_at' => date('Y-m-d H:i:s')
        ];
    }

    // =============================================================================
    // DATABASE INTEGRATION
    // =============================================================================

    /**
     * Generate payment gateway database configuration
     *
     * @return array Database configuration array
     */
    public function generateGatewayConfig(): array
    {
        return [
            'name' => 'PawaPay',
            'name_key' => $this->gatewayNameKey,
            'public_key' => $this->config['api']['token'] ?? '',
            'secret_key' => $this->config['api']['token'] ?? '',
            'webhook_secret' => $this->config['webhook_secret'] ?? '',
            'environment' => $this->config['environment'] ?? 'sandbox',
            'status' => 1,
            'logos' => 'pawapay,mobile-money,mtn,airtel,safaricom'
        ];
    }

    /**
     * Validate Modesy gateway configuration
     *
     * @param array $config Gateway configuration from database
     * @throws PaymentGatewayException
     */
    public function validateModesyConfig(array $config): void
    {
        $required = ['name_key', 'public_key', 'secret_key', 'webhook_secret'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new PaymentGatewayException("Missing required gateway configuration: {$field}");
            }
        }

        if ($config['name_key'] !== $this->gatewayNameKey) {
            throw new PaymentGatewayException("Invalid gateway name_key. Expected: {$this->gatewayNameKey}");
        }

        if (!in_array($config['environment'], ['sandbox', 'production'])) {
            throw new PaymentGatewayException("Invalid environment. Must be 'sandbox' or 'production'");
        }
    }

    // =============================================================================
    // ERROR HANDLING
    // =============================================================================

    /**
     * Handle Modesy-specific payment errors
     *
     * @param \Exception $e Exception details
     * @param array $context Error context
     * @return array Error handling result
     */
    public function handleModesyPaymentError(\Exception $e, array $context = []): array
    {
        $errorType = $this->classifyPaymentError($e);
        
        return [
            'success' => false,
            'error_type' => $errorType,
            'error_message' => $e->getMessage(),
            'user_message' => $this->getUserFriendlyErrorMessage($errorType),
            'retry_allowed' => $this->isRetryAllowed($errorType),
            'context' => $context,
            'logged_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Classify payment error type
     */
    private function classifyPaymentError(\Exception $e): string
    {
        $message = strtolower($e->getMessage());
        
        if (strpos($message, 'amount mismatch') !== false) {
            return 'amount_mismatch';
        }
        if (strpos($message, 'currency mismatch') !== false) {
            return 'currency_mismatch';
        }
        if (strpos($message, 'webhook') !== false) {
            return 'webhook_error';
        }
        if (strpos($message, 'configuration') !== false) {
            return 'configuration_error';
        }
        if (strpos($message, 'network') !== false) {
            return 'network_error';
        }
        
        return 'unknown_error';
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyErrorMessage(string $errorType): string
    {
        $messages = [
            'amount_mismatch' => 'The payment amount does not match the order total. Please try again.',
            'currency_mismatch' => 'The payment currency is incorrect. Please check your payment method.',
            'webhook_error' => 'Payment confirmation is being processed. Please wait a moment.',
            'configuration_error' => 'Payment system configuration error. Please contact support.',
            'network_error' => 'Network connection issue. Please try again in a few minutes.',
            'unknown_error' => 'An unexpected error occurred. Please contact support if the problem persists.'
        ];
        
        return $messages[$errorType] ?? $messages['unknown_error'];
    }

    /**
     * Check if error allows retry
     */
    private function isRetryAllowed(string $errorType): bool
    {
        $retryableErrors = ['network_error', 'unknown_error'];
        return in_array($errorType, $retryableErrors);
    }

    // =============================================================================
    // UTILITY METHODS
    // =============================================================================

    /**
     * Get gateway name key
     *
     * @return string
     */
    public function getGatewayNameKey(): string
    {
        return $this->gatewayNameKey;
    }

    /**
     * Generate checkout token
     *
     * @return string
     */
    public function generateCheckoutToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Log Modesy payment activity
     *
     * @param string $action
     * @param array $data
     */
    public function logPaymentActivity(string $action, array $data): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'gateway' => $this->gatewayNameKey,
            'data' => $data
        ];

        $logDir = __DIR__ . '/../logs/modesy_integration/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . 'payment_activity_' . date('Y-m-d') . '.log';
        file_put_contents(
            $logFile,
            json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND
        );
    }
}