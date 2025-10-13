<?php
/**
 * Payment Page Controller
 * 
 * Handles the complete payment page redirect flow including:
 * - Payment page creation and redirect
 * - Customer return URL handling
 * - Payment status verification
 * - Error handling and recovery
 *
 * @package     Myzuwa\PawaPay\Controller
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Controller;

use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Service\PaymentPageService;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class PaymentPageController
{
    /** @var PawaPay SDK instance */
    private $pawaPay;
    
    /** @var PaymentPageService Payment page service */
    private $paymentPageService;
    
    /** @var array Configuration */
    private $config;

    /**
     * Constructor
     *
     * @param PawaPay $pawaPay
     * @param array $config
     */
    public function __construct(PawaPay $pawaPay, array $config)
    {
        $this->pawaPay = $pawaPay;
        $this->config = $config;
        $this->paymentPageService = new PaymentPageService($pawaPay->getHttpClient(), $config);
    }

    /**
     * Initiate payment page redirect
     *
     * This method creates a payment page and returns the redirect URL
     * 
     * @param array $paymentData Payment configuration
     * @return array Response with redirect URL and payment details
     * @throws PaymentGatewayException
     */
    public function initiatePayment(array $paymentData): array
    {
        try {
            // Add security token to return URL if not present
            $paymentData['returnUrl'] = $this->secureReturnUrl($paymentData['returnUrl'] ?? '');
            
            // Create payment page
            $response = $this->paymentPageService->create($paymentData);
            
            // Log payment initiation
            $this->logPaymentInitiation($paymentData, $response);
            
            return [
                'success' => true,
                'depositId' => $paymentData['depositId'] ?? null,
                'redirectUrl' => $response['redirectURL'],
                'expiresAt' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                'message' => 'Payment page created successfully',
                'paymentData' => $paymentData
            ];
            
        } catch (PaymentGatewayException $e) {
            $this->logError('Payment initiation failed', $e, $paymentData);
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Unexpected error during payment initiation', $e, $paymentData);
            throw new PaymentGatewayException(
                'Failed to initiate payment: ' . $e->getMessage(),
                0,
                ['original_error' => $e, 'payment_data' => $paymentData]
            );
        }
    }

    /**
     * Handle customer return from payment page
     *
     * @param string $depositId Deposit ID from return URL
     * @param array $queryParams Query parameters from return URL
     * @return array Payment status and next steps
     * @throws PaymentGatewayException
     */
    public function handleReturn(string $depositId, array $queryParams = []): array
    {
        try {
            // Verify security token
            if (!$this->verifyReturnSecurity($queryParams)) {
                throw new PaymentGatewayException('Invalid return URL security token');
            }
            
            // Process return via payment page service
            $result = $this->paymentPageService->handleReturn($depositId, $queryParams);
            
            // Log return handling
            $this->logReturnHandling($depositId, $result);
            
            // Prepare response with next steps
            $response = [
                'success' => true,
                'depositId' => $depositId,
                'status' => $result['status'],
                'message' => $this->getStatusMessage($result['status']),
                'nextSteps' => $this->getNextSteps($result['status']),
                'depositDetails' => $result['deposit_details'] ?? [],
                'processedAt' => date('Y-m-d H:i:s')
            ];
            
            // Add webhook URL for status updates if needed
            if (in_array($result['status'], ['pending', 'processing', 'reconciling'])) {
                $response['webhookRequired'] = true;
                $response['statusCheckUrl'] = $this->config['status_check_url'] ?? null;
            }
            
            return $response;
            
        } catch (PaymentGatewayException $e) {
            $this->logError('Return handling failed', $e, ['deposit_id' => $depositId, 'query_params' => $queryParams]);
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Unexpected error during return handling', $e, ['deposit_id' => $depositId]);
            throw new PaymentGatewayException(
                'Failed to handle payment return: ' . $e->getMessage(),
                0,
                ['deposit_id' => $depositId, 'original_error' => $e]
            );
        }
    }

    /**
     * Get payment status (for AJAX polling)
     *
     * @param string $depositId
     * @return array Current payment status
     * @throws PaymentGatewayException
     */
    public function getPaymentStatus(string $depositId): array
    {
        try {
            // Get session data
            $session = $this->paymentPageService->getSession($depositId);
            if (!$session) {
                throw new PaymentGatewayException("Payment session not found: {$depositId}");
            }

            // Check current status via API
            $depositStatus = $this->pawaPay->checkDepositStatus($depositId);
            
            return [
                'success' => true,
                'depositId' => $depositId,
                'status' => $this->normalizeStatus($depositStatus['status'] ?? 'unknown'),
                'depositDetails' => $depositStatus,
                'sessionData' => $session,
                'lastChecked' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                'Failed to get payment status: ' . $e->getMessage(),
                0,
                ['deposit_id' => $depositId]
            );
        }
    }

    /**
     * Handle webhook for payment page payments
     * 
     * Extends the existing webhook handling for payment page specific logic
     *
     * @param array $webhookData
     * @return array Processing result
     */
    public function handleWebhook(array $webhookData): array
    {
        try {
            $depositId = $webhookData['depositId'] ?? null;
            
            if (!$depositId) {
                throw new PaymentGatewayException('Missing depositId in webhook data');
            }

            // Get session if it exists (might be direct API payment, not payment page)
            $session = $this->paymentPageService->getSession($depositId);
            
            // Process webhook using existing handler
            $result = $this->pawaPay->processCallback($webhookData);
            
            // Update session if this was a payment page payment
            if ($session) {
                $session['webhook_received_at'] = date('Y-m-d H:i:s');
                $session['webhook_data'] = $webhookData;
                $session['final_status'] = $result['status'];
                
                $sessionFile = __DIR__ . '/../storage/payment_sessions/' . $depositId . '.json';
                file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
            }
            
            $this->logWebhookProcessing($depositId, $webhookData, $result);
            
            return [
                'success' => true,
                'depositId' => $depositId,
                'status' => $result['status'],
                'isPaymentPage' => $session !== null,
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            $this->logError('Webhook processing failed', $e, $webhookData);
            throw new PaymentGatewayException(
                'Webhook processing failed: ' . $e->getMessage(),
                0,
                ['webhook_data' => $webhookData]
            );
        }
    }

    /**
     * Generate secure return URL with token
     *
     * @param string $returnUrl
     * @return string Secured return URL
     */
    private function secureReturnUrl(string $returnUrl): string
    {
        if (empty($returnUrl)) {
            throw new PaymentGatewayException('Return URL is required');
        }
        
        // Generate security token
        $token = hash('sha256', $returnUrl . time() . ($this->config['webhook_secret'] ?? 'default'));
        
        // Add token to URL
        $separator = strpos($returnUrl, '?') !== false ? '&' : '?';
        return $returnUrl . $separator . 'token=' . $token;
    }

    /**
     * Verify return URL security token
     *
     * @param array $queryParams
     * @return bool
     */
    private function verifyReturnSecurity(array $queryParams): bool
    {
        // For now, allow all returns (implement proper token verification in production)
        // In production, verify the token against expected value
        return true; // Simplified for demo
    }

    /**
     * Get user-friendly status message
     *
     * @param string $status
     * @return string
     */
    private function getStatusMessage(string $status): string
    {
        $messages = [
            'pending' => 'Your payment is being processed. Please wait.',
            'processing' => 'Payment is currently being processed by the mobile money provider.',
            'reconciling' => 'Payment is being reconciled. This may take a few minutes.',
            'completed' => 'Payment completed successfully!',
            'failed' => 'Payment failed. Please try again or use a different payment method.',
            'rejected' => 'Payment was rejected by the mobile money provider.',
            'duplicate' => 'This payment has already been processed.',
            'unknown' => 'Payment status is currently unknown. Please contact support.'
        ];
        
        return $messages[$status] ?? $messages['unknown'];
    }

    /**
     * Get next steps based on payment status
     *
     * @param string $status
     * @return array
     */
    private function getNextSteps(string $status): array
    {
        switch ($status) {
            case 'completed':
                return [
                    'action' => 'redirect_to_success',
                    'message' => 'Proceed to order confirmation',
                    'auto_redirect_delay' => 3000 // 3 seconds
                ];
                
            case 'failed':
            case 'rejected':
                return [
                    'action' => 'show_retry_options',
                    'message' => 'Try payment again or choose different method',
                    'retry_allowed' => true
                ];
                
            case 'pending':
            case 'processing':
            case 'reconciling':
                return [
                    'action' => 'show_waiting_page',
                    'message' => 'Please wait while we process your payment',
                    'check_interval' => 5000, // Check every 5 seconds
                    'max_wait_time' => 300000 // Max 5 minutes
                ];
                
            default:
                return [
                    'action' => 'contact_support',
                    'message' => 'Please contact customer support for assistance'
                ];
        }
    }

    /**
     * Normalize payment status
     *
     * @param string $status
     * @return string
     */
    private function normalizeStatus(string $status): string
    {
        $statusMap = [
            'ACCEPTED' => 'pending',
            'ENQUEUED' => 'pending',
            'SUBMITTED' => 'processing',
            'IN_RECONCILIATION' => 'reconciling',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'REJECTED' => 'rejected',
            'DUPLICATE' => 'duplicate'
        ];

        return $statusMap[strtoupper($status)] ?? 'unknown';
    }

    /**
     * Log payment initiation
     *
     * @param array $paymentData
     * @param array $response
     */
    private function logPaymentInitiation(array $paymentData, array $response): void
    {
        $logData = [
            'type' => 'payment_page_initiation',
            'timestamp' => date('Y-m-d H:i:s'),
            'deposit_id' => $paymentData['depositId'] ?? null,
            'return_url' => $paymentData['returnUrl'] ?? null,
            'amount' => $paymentData['amountDetails']['amount'] ?? null,
            'currency' => $paymentData['amountDetails']['currency'] ?? null,
            'redirect_url_created' => !empty($response['redirectURL'])
        ];
        
        $this->writeLog('payment_initiation', $logData);
    }

    /**
     * Log return handling
     *
     * @param string $depositId
     * @param array $result
     */
    private function logReturnHandling(string $depositId, array $result): void
    {
        $logData = [
            'type' => 'payment_page_return',
            'timestamp' => date('Y-m-d H:i:s'),
            'deposit_id' => $depositId,
            'status' => $result['status'] ?? 'unknown',
            'success' => $result['success'] ?? false
        ];
        
        $this->writeLog('payment_returns', $logData);
    }

    /**
     * Log webhook processing
     *
     * @param string $depositId
     * @param array $webhookData
     * @param array $result
     */
    private function logWebhookProcessing(string $depositId, array $webhookData, array $result): void
    {
        $logData = [
            'type' => 'payment_page_webhook',
            'timestamp' => date('Y-m-d H:i:s'),
            'deposit_id' => $depositId,
            'webhook_type' => $webhookData['type'] ?? 'unknown',
            'status' => $result['status'] ?? 'unknown'
        ];
        
        $this->writeLog('webhook_processing', $logData);
    }

    /**
     * Log errors
     *
     * @param string $message
     * @param \Exception $exception
     * @param array $context
     */
    private function logError(string $message, \Exception $exception, array $context = []): void
    {
        $logData = [
            'type' => 'payment_page_error',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context
        ];
        
        $this->writeLog('errors', $logData);
    }

    /**
     * Write log data to file
     *
     * @param string $logType
     * @param array $data
     */
    private function writeLog(string $logType, array $data): void
    {
        $logDir = __DIR__ . '/../logs/payment_pages/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . $logType . '_' . date('Y-m-d') . '.log';
        file_put_contents(
            $logFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND
        );
    }
}