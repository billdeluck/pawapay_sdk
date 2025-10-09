<?php

namespace Myzuwa\PawaPay\Service;

use GuzzleHttp\Client;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class ProviderService
{
    private Client $httpClient;
    private array $providers = [];

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Fetch and cache provider configuration for a specific country
     *
     * @param string $country ISO country code
     * @param string $operationType DEPOSIT|PAYOUT
     * @return array
     * @throws PaymentGatewayException
     */
    public function getProviderConfig(string $country, string $operationType = 'DEPOSIT'): array
    {
        $cacheKey = "{$country}_{$operationType}";
        
        if (isset($this->providers[$cacheKey])) {
            return $this->providers[$cacheKey];
        }

        try {
            $response = $this->httpClient->get(
                "/v2/active-conf",
                [
                    'query' => [
                        'country' => $country,
                        'operationType' => $operationType
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Cache the response
            $this->providers[$cacheKey] = $data;
            
            return $data;
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to fetch provider configuration: " . $e->getMessage(),
                0,
                ['country' => $country]
            );
        }
    }

    /**
     * Get available providers for a country
     *
     * @param string $country
     * @return array
     */
    public function getAvailableProviders(string $country): array
    {
        $config = $this->getProviderConfig($country);
        $providers = [];

        foreach ($config['countries'] as $countryData) {
            if ($countryData['country'] === $country) {
                foreach ($countryData['providers'] as $provider) {
                    // Only include operational providers
                    $isOperational = $this->isProviderOperational($provider);
                    if ($isOperational) {
                        $providers[] = [
                            'code' => $provider['provider'],
                            'name' => $provider['displayName'],
                            'logo' => $provider['logo'],
                            'currencies' => $this->getProviderCurrencies($provider),
                            'limits' => $this->getProviderLimits($provider)
                        ];
                    }
                }
                break;
            }
        }

        return $providers;
    }

    /**
     * Check if provider is operational
     *
     * @param array $provider
     * @return bool
     */
    private function isProviderOperational(array $provider): bool
    {
        foreach ($provider['currencies'] as $currency) {
            if (isset($currency['operationTypes']['DEPOSIT']['status'])) {
                return $currency['operationTypes']['DEPOSIT']['status'] === 'OPERATIONAL';
            }
        }
        return false;
    }

    /**
     * Get supported currencies for a provider
     *
     * @param array $provider
     * @return array
     */
    private function getProviderCurrencies(array $provider): array
    {
        $currencies = [];
        foreach ($provider['currencies'] as $currency) {
            $currencies[] = [
                'code' => $currency['currency'],
                'display' => $currency['displayName'],
                'decimals' => $currency['operationTypes']['DEPOSIT']['decimalsInAmount'] ?? 'NONE'
            ];
        }
        return $currencies;
    }

    /**
     * Get transaction limits for a provider
     *
     * @param array $provider
     * @return array
     */
    private function getProviderLimits(array $provider): array
    {
        $limits = [];
        foreach ($provider['currencies'] as $currency) {
            $depositOps = $currency['operationTypes']['DEPOSIT'] ?? [];
            $limits[$currency['currency']] = [
                'min' => $depositOps['minAmount'] ?? null,
                'max' => $depositOps['maxAmount'] ?? null
            ];
        }
        return $limits;
    }

    /**
     * Validate amount for a specific provider and currency
     *
     * @param string $amount
     * @param string $currency
     * @param string $provider
     * @param string $country
     * @return bool
     * @throws PaymentGatewayException
     */
    public function validateAmount(string $amount, string $currency, string $provider, string $country): bool
    {
        $config = $this->getProviderConfig($country);
        
        foreach ($config['countries'] as $countryData) {
            if ($countryData['country'] === $country) {
                foreach ($countryData['providers'] as $providerData) {
                    if ($providerData['provider'] === $provider) {
                        foreach ($providerData['currencies'] as $currencyData) {
                            if ($currencyData['currency'] === $currency) {
                                $ops = $currencyData['operationTypes']['DEPOSIT'];
                                
                                // Check decimal places
                                if ($ops['decimalsInAmount'] === 'NONE' && strpos($amount, '.') !== false) {
                                    throw new PaymentGatewayException(
                                        "Provider does not support decimal amounts"
                                    );
                                }
                                
                                // Check limits
                                $amountFloat = (float)$amount;
                                if ($amountFloat < (float)$ops['minAmount'] || $amountFloat > (float)$ops['maxAmount']) {
                                    throw new PaymentGatewayException(
                                        "Amount outside provider limits"
                                    );
                                }
                                
                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        throw new PaymentGatewayException("Provider or currency not found");
    }
}
