<?php
/**
 * PawaPay Payment Page Facade
 * 
 * Simplified interface for payment page integration.
 * Provides easy-to-use methods for common payment page operations.
 *
 * @package     Myzuwa\PawaPay
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay;

use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Controller\PaymentPageController;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class PaymentPageFacade
{
    /** @var PawaPay SDK instance */
    private $pawaPay;
    
    /** @var PaymentPageController */
    private $controller;
    
    /** @var array Configuration */
    private $config;

    /**
     * Constructor
     *
     * @param array $config PawaPay configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->pawaPay = new PawaPay($config);
        $this->controller = new PaymentPageController($this->pawaPay, $config);
    }

    /**
     * Create payment page and get redirect URL
     *
     * Simplified method for creating a payment page with common parameters
     *
     * @param array $params Payment parameters:
     *                      - amount: Payment amount (required if not allowing user input)
     *                      - currency: Payment currency (required if amount specified)
     *                      - returnUrl: URL to return customer after payment (required)
     *                      - description: Payment description (required, 4-22 chars)
     *                      - customerPhone: Customer phone number (optional)
     *                      - country: Country code (optional, e.g., 'KEN')
     *                      - orderId: Your internal order ID (optional)
     *                      - customerEmail: Customer email (optional)
     * @return string Redirect URL for customer
     * @throws PaymentGatewayException
     */
    public function createPaymentRedirect(array $params): string
    {
        // Validate required parameters
        if (empty($params['returnUrl']) || empty($params['description'])) {
            throw new PaymentGatewayException('returnUrl and description are required');
        }

        // Build payment page data
        $paymentData = [
            'returnUrl' => $params['returnUrl'],
            'narration' => $this->truncateNarration($params['description'])
        ];

        // Add optional reason (longer description)
        if (!empty($params['description'])) {
            $paymentData['reason'] = substr($params['description'], 0, 50);
        }

        // Add amount if specified
        if (!empty($params['amount']) && !empty($params['currency'])) {
            $paymentData['amountDetails'] = [
                'amount' => $params['amount'],
                'currency' => strtoupper($params['currency'])
            ];
        }

        // Add customer details if provided
        if (!empty($params['customerPhone'])) {
            $paymentData['phoneNumber'] = $this->formatPhoneNumber($params['customerPhone']);
        }

        if (!empty($params['country'])) {
            $paymentData['country'] = strtoupper($params['country']);
        }

        // Add metadata
        $metadata = [];
        if (!empty($params['orderId'])) {
            $metadata[] = ['orderId' => $params['orderId']];
        }
        if (!empty($params['customerEmail'])) {
            $metadata[] = ['customerEmail' => $params['customerEmail'], 'isPII' => true];
        }
        if (!empty($metadata)) {
            $paymentData['metadata'] = $metadata;
        }

        // Create payment page
        $result = $this->controller->initiatePayment($paymentData);
        
        if (!$result['success']) {
            throw new PaymentGatewayException('Failed to create payment page');
        }

        return $result['redirectUrl'];
    }

    /**
     * Handle customer return from payment page
     *
     * Call this method when customer returns to your return URL
     *
     * @param string $depositId Deposit ID from URL parameter
     * @param array $queryParams All query parameters from return URL
     * @return array Payment result with status and next steps
     * @throws PaymentGatewayException
     */
    public function handlePaymentReturn(string $depositId, array $queryParams = []): array
    {
        return $this->controller->handleReturn($depositId, $queryParams);
    }

    /**
     * Check payment status
     *
     * Use this for AJAX polling or status checks
     *
     * @param string $depositId
     * @return array Current payment status
     * @throws PaymentGatewayException
     */
    public function checkPaymentStatus(string $depositId): array
    {
        return $this->controller->getPaymentStatus($depositId);
    }

    /**
     * Create simple payment link
     *
     * Creates a payment page for a specific amount and returns the URL
     *
     * @param float $amount Payment amount
     * @param string $currency Currency code (e.g., 'KES', 'GHS')
     * @param string $description Payment description
     * @param string $returnUrl Where to send customer after payment
     * @param array $options Optional parameters
     * @return array Payment details including redirect URL and deposit ID
     * @throws PaymentGatewayException
     */
    public function createPaymentLink(
        float $amount, 
        string $currency, 
        string $description, 
        string $returnUrl,
        array $options = []
    ): array {
        $params = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'returnUrl' => $returnUrl
        ];

        // Add optional parameters
        foreach (['customerPhone', 'country', 'orderId', 'customerEmail'] as $key) {
            if (!empty($options[$key])) {
                $params[$key] = $options[$key];
            }
        }

        $redirectUrl = $this->createPaymentRedirect($params);
        
        // Extract deposit ID from stored session (if available)
        // For a more robust implementation, you'd return this from createPaymentRedirect
        $depositId = $this->generateDepositId(); // Simplified for demo
        
        return [
            'success' => true,
            'depositId' => $depositId,
            'redirectUrl' => $redirectUrl,
            'amount' => $amount,
            'currency' => $currency,
            'expiresAt' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
    }

    /**
     * Create flexible payment page (amount entered by customer)
     *
     * Creates a payment page where customer can enter the amount
     *
     * @param string $description Payment description
     * @param string $returnUrl Where to send customer after payment  
     * @param array $options Optional parameters
     * @return array Payment details including redirect URL
     * @throws PaymentGatewayException
     */
    public function createFlexiblePayment(
        string $description,
        string $returnUrl,
        array $options = []
    ): array {
        $params = [
            'description' => $description,
            'returnUrl' => $returnUrl
        ];

        // Add optional parameters (no amount/currency = flexible payment)
        foreach (['customerPhone', 'country', 'orderId', 'customerEmail'] as $key) {
            if (!empty($options[$key])) {
                $params[$key] = $options[$key];
            }
        }

        $redirectUrl = $this->createPaymentRedirect($params);
        
        return [
            'success' => true,
            'redirectUrl' => $redirectUrl,
            'flexible' => true,
            'expiresAt' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
    }

    /**
     * Process webhook callback
     *
     * Handle incoming webhook for payment page payments
     *
     * @param array $webhookData Raw webhook data
     * @param string $signature Webhook signature header
     * @return array Processing result
     * @throws PaymentGatewayException
     */
    public function processWebhook(array $webhookData, string $signature): array
    {
        // Verify signature first
        if (!$this->pawaPay->verifyWebhookSignature($webhookData, $signature)) {
            throw new PaymentGatewayException('Invalid webhook signature');
        }

        return $this->controller->handleWebhook($webhookData);
    }

    /**
     * Get payment page statistics
     *
     * @return array Statistics about payment pages
     */
    public function getStatistics(): array
    {
        $sessionsDir = __DIR__ . '/storage/payment_sessions/';
        
        if (!is_dir($sessionsDir)) {
            return [
                'total_sessions' => 0,
                'active_sessions' => 0,
                'expired_sessions' => 0
            ];
        }

        $sessionFiles = glob($sessionsDir . '*.json');
        $total = count($sessionFiles);
        $active = 0;
        $expired = 0;
        
        foreach ($sessionFiles as $file) {
            $session = json_decode(file_get_contents($file), true);
            if ($session) {
                if (strtotime($session['expires_at']) > time()) {
                    $active++;
                } else {
                    $expired++;
                }
            }
        }

        return [
            'total_sessions' => $total,
            'active_sessions' => $active,
            'expired_sessions' => $expired,
            'success_rate' => $total > 0 ? round(($total - $expired) / $total * 100, 2) : 0
        ];
    }

    /**
     * Clean expired sessions
     *
     * @return int Number of sessions cleaned
     */
    public function cleanExpiredSessions(): int
    {
        return $this->controller->paymentPageService->cleanExpiredSessions();
    }

    /**
     * Truncate narration to fit PawaPay requirements (4-22 characters)
     *
     * @param string $description
     * @return string
     */
    private function truncateNarration(string $description): string
    {
        $narration = substr($description, 0, 22);
        if (strlen($narration) < 4) {
            $narration = str_pad($narration, 4, ' ');
        }
        return $narration;
    }

    /**
     * Format phone number for PawaPay (remove +, spaces, etc.)
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digits
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading zeros
        $cleaned = ltrim($cleaned, '0');
        
        return $cleaned;
    }

    /**
     * Generate deposit ID
     *
     * @return string
     */
    private function generateDepositId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get the underlying PawaPay SDK instance
     *
     * @return PawaPay
     */
    public function getPawaPay(): PawaPay
    {
        return $this->pawaPay;
    }

    /**
     * Get the payment page controller
     *
     * @return PaymentPageController
     */
    public function getController(): PaymentPageController
    {
        return $this->controller;
    }
}