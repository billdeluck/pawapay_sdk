<?php
namespace Myzuwa\PawaPay\Service;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use GuzzleHttp\ClientInterface;

/**
 * MNO (Mobile Network Operator) Service
 * 
 * Handles all mobile network operator related operations including:
 * - Fetching available operators
 * - Validating phone numbers against operators
 * - Caching operator information
 */
class MNOService implements MNOServiceInterface
{
    /** @var ClientInterface */
    private $httpClient;

    /** @var array Cache of MNO data */
    private $mnoCache = [];

    /** @var int Cache lifetime in seconds */
    private $cacheLifetime = 3600; // 1 hour

    /** @var string|null Last cache update timestamp */
    private $lastCacheUpdate = null;

    /**
     * Constructor
     *
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get available mobile network operators
     *
     * @param string $country ISO country code (e.g., 'ZMB' for Zambia)
     * @return array List of available operators
     * @throws PaymentGatewayException
     */
    public function getAvailableOperators(string $country): array
    {
        try {
            if ($this->shouldRefreshCache()) {
                $response = $this->httpClient->request('GET', '/v2/active-conf?country=' . $country . '&operationType=DEPOSIT');
                $data = json_decode($response->getBody()->getContents(), true);

                // Debug: Log the actual response structure
                error_log("MNO Service Debug - Response keys: " . json_encode(array_keys($data)));
                error_log("MNO Service Debug - Full response: " . json_encode($data));

                if (!isset($data['countries']) || !is_array($data['countries'])) {
                    throw new PaymentGatewayException("Invalid response format from active-conf endpoint. Expected 'countries' key. Got: " . json_encode(array_keys($data)));
                }

                $this->updateCacheFromActiveConf($data['countries']);
            }

            return $this->filterOperatorsByCountry($country);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to fetch mobile network operators: " . $e->getMessage(),
                0,
                ['country' => $country]
            );
        }
    }

    /**
     * Predict operator from phone number
     *
     * @param string $phoneNumber
     * @param string $country ISO country code
     * @return array|string|null Predicted operator details or null if not found
     * @throws PaymentGatewayException
     */
    public function predictOperator(string $phoneNumber, string $country): array|string|null
    {
        try {
            $response = $this->httpClient->request('POST', '/v2/predict-provider', [
                'json' => [
                    'phoneNumber' => $phoneNumber
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Handle both array and string responses
            if (is_array($data) && isset($data['provider'])) {
                return $data['provider'];
            } elseif (is_string($data)) {
                return $data;
            }

            return null;
        } catch (\Exception $e) {
            // Log the error but don't throw exception - prediction is optional
            error_log("Operator prediction failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate phone number format for a specific operator
     *
     * @param string $phoneNumber
     * @param string $operatorCode
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber, string $operatorCode): bool
    {
        // Get operator-specific validation rules
        $operator = $this->getOperatorDetails($operatorCode);
        
        if (!$operator) {
            return false;
        }

        // Apply operator-specific validation rules
        $pattern = $operator['phoneNumberPattern'] ?? null;
        if (!$pattern) {
            // Default validation if no specific pattern exists
            return preg_match('/^\+?\d{10,15}$/', $phoneNumber);
        }

        return preg_match($pattern, $phoneNumber);
    }

    /**
     * Check if an operator is currently available
     *
     * @param string $operatorCode
     * @return bool
     */
    public function isOperatorAvailable(string $operatorCode): bool
    {
        $operator = $this->getOperatorDetails($operatorCode);
        if (!$operator) {
            return false;
        }

        return $operator['status'] === 'AVAILABLE';
    }

    /**
     * Get supported currencies for an operator
     *
     * @param string $operatorCode
     * @return array
     */
    public function getSupportedCurrencies(string $operatorCode): array
    {
        $operator = $this->getOperatorDetails($operatorCode);
        if (!$operator) {
            return [];
        }

        return $operator['supportedCurrencies'] ?? [];
    }

    /**
     * Filter operators by country
     *
     * @param string $country
     * @return array
     */
    private function filterOperatorsByCountry(string $country): array
    {
        return array_filter($this->mnoCache, function($operator) use ($country) {
            return $operator['country'] === $country && $operator['status'] === 'OPERATIONAL';
        });
    }

    /**
     * Get operator details from cache
     *
     * @param string $operatorCode
     * @return array|null
     */
    private function getOperatorDetails(string $operatorCode): ?array
    {
        return $this->mnoCache[$operatorCode] ?? null;
    }

    /**
     * Check if cache should be refreshed
     *
     * @return bool
     */
    private function shouldRefreshCache(): bool
    {
        if (empty($this->mnoCache) || !$this->lastCacheUpdate) {
            return true;
        }

        return (time() - strtotime($this->lastCacheUpdate)) > $this->cacheLifetime;
    }

    /**
     * Update the MNO cache from active-conf response
     *
     * @param array $countries
     * @return void
     */
    private function updateCacheFromActiveConf(array $countries): void
    {
        $this->mnoCache = [];

        foreach ($countries as $country) {
            if (isset($country['providers']) && is_array($country['providers'])) {
                foreach ($country['providers'] as $provider) {
                    // Get the first currency's operation types
                    $currencies = $provider['currencies'] ?? [];
                    $firstCurrency = $currencies[0] ?? [];
                    $operationTypes = $firstCurrency['operationTypes'] ?? [];
                    $depositOperation = $operationTypes['DEPOSIT'] ?? [];

                    $this->mnoCache[$provider['provider']] = [
                        'code' => $provider['provider'],
                        'name' => $provider['displayName'] ?? $provider['provider'],
                        'country' => $country['country'],
                        'status' => $depositOperation['status'] ?? 'UNKNOWN',
                        'supportedCurrencies' => array_column($currencies, 'currency'),
                        'minAmount' => $depositOperation['minAmount'] ?? null,
                        'maxAmount' => $depositOperation['maxAmount'] ?? null,
                        'authType' => $depositOperation['authType'] ?? null,
                        'pinPrompt' => $depositOperation['pinPrompt'] ?? null,
                        'decimalsInAmount' => $depositOperation['decimalsInAmount'] ?? null,
                        'logo' => $provider['logo'] ?? null,
                        'nameDisplayedToCustomer' => $provider['nameDisplayedToCustomer'] ?? null
                    ];
                }
            }
        }

        $this->lastCacheUpdate = date('Y-m-d H:i:s');
    }

    /**
     * Update the MNO cache
     *
     * @param array $providers
     * @return void
     */
    private function updateCache(array $providers): void
    {
        $this->mnoCache = [];
        foreach ($providers as $provider) {
            $this->mnoCache[$provider['code']] = $provider;
        }
        $this->lastCacheUpdate = date('Y-m-d H:i:s');
    }

    /**
     * Set cache lifetime
     *
     * @param int $seconds
     * @return void
     */
    public function setCacheLifetime(int $seconds): void
    {
        $this->cacheLifetime = $seconds;
    }
}
