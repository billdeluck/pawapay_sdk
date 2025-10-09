<?php

namespace Myzuwa\PawaPay\Service;

use GuzzleHttp\ClientInterface;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class PhoneValidationService
{
    private $httpClient;

    public function __construct($httpClient)
    {
        if (!($httpClient instanceof ClientInterface)) {
            throw new \InvalidArgumentException('Expected GuzzleHttp\ClientInterface instance');
        }
        $this->httpClient = $httpClient;
    }

    /**
     * Validate phone number and predict provider
     *
     * @param string $phoneNumber
     * @return array
     * @throws PaymentGatewayException
     */
    public function validateAndPredictProvider(string $phoneNumber): array
    {
        try {
            $response = $this->httpClient->request('POST', '/v2/predict-provider', [
                'json' => [
                    'phoneNumber' => $this->sanitizePhoneNumber($phoneNumber)
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'country' => $data['country'],
                'provider' => $data['provider'],
                'phoneNumber' => $data['phoneNumber'] // Sanitized phone number
            ];
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Phone number validation failed: " . $e->getMessage(),
                0,
                ['phone_number' => $phoneNumber]
            );
        }
    }

    /**
     * Clean phone number input
     *
     * @param string $phoneNumber
     * @return string
     */
    private function sanitizePhoneNumber(string $phoneNumber): string
    {
        // Remove any whitespace, hyphens or other special characters
        $number = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Ensure number starts with country code
        if (strlen($number) > 9 && !str_starts_with($number, '00') && !str_starts_with($number, '+')) {
            $number = '+' . $number;
        }
        
        return $number;
    }

    /**
     * Validate phone number format for a specific country
     *
     * @param string $phoneNumber
     * @param string $countryCode
     * @return bool
     */
    public function validateFormat(string $phoneNumber, string $countryCode): bool
    {
        // Basic format validation patterns by country
        $patterns = [
            'RWA' => '/^250[7][0-9]{8}$/',
            'ZMB' => '/^260[9][0-9]{8}$/',
            'BEN' => '/^229[0-9]{8}$/',
            // Add more country patterns as needed
        ];

        $sanitized = $this->sanitizePhoneNumber($phoneNumber);
        
        if (!isset($patterns[$countryCode])) {
            throw new PaymentGatewayException("Unsupported country code: {$countryCode}");
        }

        return preg_match($patterns[$countryCode], $sanitized) === 1;
    }

    /**
     * Format phone number with proper country code
     *
     * @param string $phoneNumber
     * @param string $countryCode
     * @return string
     */
    public function formatWithCountryCode(string $phoneNumber, string $countryCode): string
    {
        $countryCodes = [
            'RWA' => '250',
            'ZMB' => '260',
            'BEN' => '229',
            // Add more as needed
        ];

        if (!isset($countryCodes[$countryCode])) {
            throw new PaymentGatewayException("Unsupported country code: {$countryCode}");
        }

        $number = $this->sanitizePhoneNumber($phoneNumber);
        
        // If number already has country code, return as is
        if (str_starts_with($number, $countryCodes[$countryCode])) {
            return $number;
        }

        // Remove leading zeros and add country code
        $number = ltrim($number, '0');
        return $countryCodes[$countryCode] . $number;
    }
}