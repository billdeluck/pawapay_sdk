<?php
namespace Myzuwa\PawaPay\Adapter;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;

/**
 * Modesy Platform Adapter for PawaPay
 * 
 * This adapter implements the PaymentGatewayAdapterInterface for Modesy platform.
 * It handles all Modesy-specific integration points and data transformations.
 */
class ModesyAdapter implements PaymentGatewayAdapterInterface
{
    /** @var array Modesy configuration */
    private $config;

    /** @var \Myzuwa\PawaPay\PawaPay SDK instance */
    private $sdk;

    /**
     * Initialize adapter with Modesy configuration
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->validateModesyConfig($config);
        $this->config = $config;
        
        // Initialize SDK with Modesy configuration
        $this->sdk = new \Myzuwa\PawaPay\PawaPay([
            'public_key' => $config['public_key'],
            'secret_key' => $config['secret_key'],
            'webhook_secret' => $config['webhook_secret'],
            'environment' => $config['environment'] ?? 'sandbox'
        ]);
    }

    /**
     * Process payment through PawaPay
     *
     * @param float $amount
     * @param string $currency
     * @param array $customerData
     * @param array $metadata
     * @return array
     * @throws PaymentGatewayException
     */
    public function processPayment(float $amount, string $currency, array $customerData, array $metadata = []): array
    {
        try {
            // Transform Modesy order data to PawaPay format
            $pawapayData = $this->transformToDepositData($amount, $currency, $customerData, $metadata);
            
            // Initiate deposit via SDK
            $response = $this->sdk->initiateDeposit($pawapayData);
            
            // Transform PawaPay response to Modesy format
            return $this->transformToModesyResponse($response);
        } catch (\Exception $e) {
            throw new PaymentGatewayException("Payment processing failed: " . $e->getMessage());
        }
    }

    /**
     * Handle PawaPay callback in Modesy format
     *
     * @param array $data
     * @return bool
     */
    public function handleCallback(array $data): bool
    {
        try {
            // Verify callback signature
            if (!$this->sdk->verifyWebhookSignature($data)) {
                throw new PaymentGatewayException("Invalid webhook signature");
            }

            // Process callback based on Modesy requirements
            $status = $this->sdk->processCallback($data);

            // Update Modesy order status
            return $this->updateModesyOrderStatus($status);
        } catch (\Exception $e) {
            // Log error and return false
            error_log("PawaPay callback processing failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment status in Modesy format
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $status = $this->sdk->checkDepositStatus($transactionId);
            return $this->transformToModesyStatus($status);
        } catch (\Exception $e) {
            throw new PaymentGatewayException("Status check failed: " . $e->getMessage());
        }
    }

    /**
     * Process refund through PawaPay
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function processRefund(string $transactionId, float $amount, string $reason = ''): array
    {
        try {
            $refundData = [
                'depositId' => $transactionId,
                'amount' => $amount,
                'reason' => $reason
            ];
            
            $response = $this->sdk->initiateRefund($refundData);
            return $this->transformToModesyRefundResponse($response);
        } catch (\Exception $e) {
            throw new PaymentGatewayException("Refund processing failed: " . $e->getMessage());
        }
    }

    /**
     * Transform Modesy data to PawaPay deposit format
     *
     * @param float $amount
     * @param string $currency
     * @param array $customerData
     * @param array $metadata
     * @return array
     */
    private function transformToDepositData(float $amount, string $currency, array $customerData, array $metadata): array
    {
        return [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => strtoupper($currency),
            'phoneNumber' => $customerData['phone'] ?? '',
            'provider' => $customerData['provider'] ?? '',
            'reference' => $metadata['order_id'] ?? uniqid('ORDER-'),
            'customerEmail' => $customerData['email'] ?? '',
            'metadata' => $metadata
        ];
    }

    /**
     * Transform PawaPay response to Modesy format
     *
     * @param array $response
     * @return array
     */
    private function transformToModesyResponse(array $response): array
    {
        return [
            'transaction_id' => $response['depositId'] ?? '',
            'status' => $this->mapPawapayStatusToModesy($response['status']),
            'message' => $response['message'] ?? '',
            'raw_response' => $response
        ];
    }

    /**
     * Map PawaPay status to Modesy status
     *
     * @param string $pawapayStatus
     * @return string
     */
    private function mapPawapayStatusToModesy(string $pawapayStatus): string
    {
        $statusMap = [
            'ACCEPTED' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'REJECTED' => 'failed',
        ];

        return $statusMap[$pawapayStatus] ?? 'pending';
    }

    /**
     * Transform PawaPay status response to Modesy format
     *
     * @param array $status PawaPay status response
     * @return array Modesy formatted status
     */
    private function transformToModesyStatus(array $status): array
    {
        return [
            'transaction_id' => $status['depositId'] ?? '',
            'status' => $this->mapPawapayStatusToModesy($status['status'] ?? ''),
            'amount' => $status['amount'] ?? null,
            'currency' => $status['currency'] ?? null,
            'created_at' => $status['created'] ?? null,
            'provider_transaction_id' => $status['providerTransactionId'] ?? null,
            'metadata' => $status['metadata'] ?? [],
            'raw_response' => $status
        ];
    }

    /**
     * Transform PawaPay refund response to Modesy format
     *
     * @param array $response PawaPay refund response
     * @return array Modesy formatted refund response
     */
    private function transformToModesyRefundResponse(array $response): array
    {
        return [
            'refund_id' => $response['refundId'] ?? '',
            'status' => $this->mapPawapayStatusToModesy($response['status'] ?? ''),
            'amount' => $response['amount'] ?? null,
            'reason' => $response['reason'] ?? '',
            'created_at' => $response['created'] ?? null,
            'raw_response' => $response
        ];
    }

    /**
     * Update Modesy order status
     *
     * @param array $status
     * @return bool
     */
    private function updateModesyOrderStatus(array $status): bool
    {
        // Implementation will depend on Modesy's order management system
        // This is a placeholder that will be implemented based on Modesy requirements
        return true;
    }

    /**
     * Validate Modesy configuration
     *
     * @param array $config
     * @throws PaymentGatewayException
     */
    private function validateModesyConfig(array $config): void
    {
        $required = ['public_key', 'secret_key', 'webhook_secret'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new PaymentGatewayException("Missing required Modesy configuration: {$field}");
            }
        }
    }
}