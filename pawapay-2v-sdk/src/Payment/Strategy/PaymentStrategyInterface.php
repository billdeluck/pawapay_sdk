<?php
namespace Myzuwa\PawaPay\Payment\Strategy;

use Myzuwa\PawaPay\Payment\Model\PaymentContext;

/**
 * Payment Strategy Interface
 * 
 * Defines the contract for different payment processing strategies.
 * Each payment type (product, membership, promotion) can have its own
 * implementation with specific validation and processing rules.
 */
interface PaymentStrategyInterface
{
    /**
     * Process payment
     *
     * @param PaymentContext $context
     * @return array
     * @throws \Myzuwa\PawaPay\Exception\PaymentGatewayException
     */
    public function process(PaymentContext $context): array;

    /**
     * Validate payment context
     *
     * @param PaymentContext $context
     * @return bool
     * @throws \Myzuwa\PawaPay\Exception\PaymentGatewayException
     */
    public function validate(PaymentContext $context): bool;

    /**
     * Handle payment callback
     *
     * @param array $callbackData
     * @param PaymentContext $context
     * @return array
     * @throws \Myzuwa\PawaPay\Exception\PaymentGatewayException
     */
    public function handleCallback(array $callbackData, PaymentContext $context): array;

    /**
     * Get supported payment limits
     *
     * @param string $currency
     * @return array
     */
    public function getPaymentLimits(string $currency): array;

    /**
     * Check if strategy supports payment type
     *
     * @param string $paymentType
     * @return bool
     */
    public function supports(string $paymentType): bool;
}