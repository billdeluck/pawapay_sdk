<?php

namespace Myzuwa\PawaPay\Service;

/**
 * Interface for Mobile Network Operator (MNO) services
 */
interface MNOServiceInterface
{
    /**
     * Validate a phone number for a given provider
     *
     * @param string $phoneNumber
     * @param string $providerCode
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber, string $providerCode): bool;

    /**
     * Check if an operator is currently available
     *
     * @param string $providerCode
     * @return bool
     */
    public function isOperatorAvailable(string $providerCode): bool;

    /**
     * Get supported currencies for a provider
     *
     * @param string $providerCode
     * @return array
     */
    public function getSupportedCurrencies(string $providerCode): array;

    /**
     * Predict operator from phone number
     *
     * @param string $phoneNumber
     * @param string $country ISO country code
     * @return array|string|null Predicted operator details or null if not found
     */
    public function predictOperator(string $phoneNumber, string $country): array|string|null;
}
