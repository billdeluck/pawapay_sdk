<?php
namespace Myzuwa\PawaPay\Adapter;

/**
 * Payment Gateway Adapter Interface
 * 
 * This interface defines the contract for adapting PawaPay SDK
 * to different e-commerce platforms. Implementing this interface
 * allows the SDK to work with different platforms while maintaining
 * consistent behavior.
 */
interface PaymentGatewayAdapterInterface
{
    /**
     * Initialize the payment gateway with configuration
     *
     * @param array $config Platform-specific configuration
     * @return void
     */
    public function initialize(array $config): void;

    /**
     * Process a payment
     *
     * @param float $amount Amount to process
     * @param string $currency Currency code
     * @param array $customerData Customer information
     * @param array $metadata Additional transaction metadata
     * @return array Transaction result
     */
    public function processPayment(float $amount, string $currency, array $customerData, array $metadata = []): array;

    /**
     * Handle payment callback/webhook
     *
     * @param array $data Callback data
     * @return bool Success status
     */
    public function handleCallback(array $data): bool;

    /**
     * Get payment status
     *
     * @param string $transactionId Transaction ID to check
     * @return array Payment status details
     */
    public function getPaymentStatus(string $transactionId): array;

    /**
     * Process refund
     *
     * @param string $transactionId Original transaction ID
     * @param float $amount Amount to refund
     * @param string $reason Refund reason
     * @return array Refund result
     */
    public function processRefund(string $transactionId, float $amount, string $reason = ''): array;
}