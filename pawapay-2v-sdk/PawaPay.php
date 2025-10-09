<?php
/**
 * PawaPay SDK Core - Myzuwa Payment Gateway Integration
 *
 * @package     Myzuwa\PawaPay
 * @version     1.0.0
 * @author      AI Assistant - September 2025
 *
 * FUNCTIONAL OVERVIEW:
 * Complete PawaPay v2 API integration with Myzuwa marketplace support.
 * Handles deposits (Customer→Myzuwa), payouts (Myzuwa→Vendor), and full payment lifecycle.
 *
 * KEY FEATURES:
 * - Deposit initiation and status tracking
 * - Vendor payout processing with bulk support
 * - Phone number validation and operator prediction
 * - Cryptographic webhook signature verification
 * - Sandbox/production environment switching
 *
 * AI ADOPTION INSTRUCTIONS:
 * ─────────────────────────
 * 1. ALWAYS use DatabaseHelper instead of direct PDO/Active Record
 * 2. ALWAYS verify webhook signatures for security
 * 3. Wrap API calls in try/catch (PaymentGatewayException)
 * 4. Use string amounts ("100.00") and uppercase currencies ("ZMW")
 * 5. Validate phone numbers before payment initiation
 * 6. Test modifications: php vendor/bin/phpunit tests/Unit/Service/PawaPayTest.php
 *
 * @link ../Docs/integration_guide.md - Complete integration guide
 * @link ../PRODUCTION_READINESS_CHECKLIST.md - Production deployment steps
 * @link ../XAMPP_TEST_RESULTS.md - Testing verification
 * @link ../app/Controllers/CartController.php - Main payment processing
 * @link ../app/Controllers/Admin/PayoutController.php - Vendor payout management
 * @link ../src/Support/DatabaseHelper.php - Database abstraction layer
 */

namespace Myzuwa\PawaPay;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;

/**
 * Main PawaPay SDK class for Myzuwa.com marketplace
 *
 * This class serves as the main entry point for the PawaPay v2 integration.
 * It is designed to be easily pluggable into Modesy's payment gateway system
 * while maintaining independence from core Modesy files.
 *
 * KEY FEATURES:
 * - Complete deposit/payout API integration
 * - Phone number validation and operator prediction
 * - Cryptographic webhook signature verification
 * - Comprehensive error handling and logging
 * - Sandbox/production environment support
 *
 * SECURITY FEATURES:
 * - All API calls use bearer token authentication
 * - Webhook signatures verified cryptographically
 * - Input sanitization and validation
 * - Prepared statement usage prevents SQL injection
 *
 * TESTING:
 * - All transactions start in sandbox environment
 * - Full integration test suite included
 * - Mock services available for unit testing
 *
 * @version 1.0.0
 * @link [Version Control Documentation: ../docs/version_control.md]
 * @link [Integration Guide: ../docs/integration_guide.md]
 * @link [API Documentation: ../docs/pawapay_documentation.md]
 *
 * @see ../app/Controllers/CartController.php - Main payment processing
 * @see ../app/Controllers/Admin/PayoutController.php - Vendor payout management
 * @see ../src/Support/DatabaseHelper.php - Database abstraction layer
 * @see ../database/migrations/003_create_vendor_payouts_table.php - Database schema
 */
class PawaPay
{
    /** @var string SDK Version */
    const VERSION = '1.0.0';
    
    /** @var string Base URL for sandbox environment */
    const SANDBOX_URL = 'https://api.sandbox.pawapay.io';
    
    /** @var string Base URL for production environment */
    const PRODUCTION_URL = 'https://api.pawapay.io';
    
    /** @var string API Version */
    const API_VERSION = 'v2';

    /** @var array SDK configuration */
    private $config;

    /** @var string Base API URL */
    private $baseUrl;

    /** @var string Environment (sandbox|production) */
    private $environment;

    /** @var \GuzzleHttp\Client HTTP client */
    private $httpClient;

    /** @var \Myzuwa\PawaPay\Service\MNOService MNO service */
    private $mnoService;

    /**
     * Initialize the SDK with configuration
     *
     * @param array $config Configuration array containing:
     *                      - public_key: PawaPay public key
     *                      - secret_key: PawaPay secret key
     *                      - webhook_secret: Webhook signature secret
     *                      - environment: 'sandbox' or 'production'
     */
    public function __construct(array $config)
    {
        // Support alternate config keys used in tests (apiKey, environment)
        if (isset($config['apiKey']) && !isset($config['api']['token'])) {
            $config['api']['token'] = $config['apiKey'];
        }

        if (isset($config['environment'])) {
            $env = strtolower($config['environment']);
            $config['api']['base_url'] = $env === 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL;
            $this->environment = $env;
        } else {
            $this->environment = 'sandbox'; // Default to sandbox
        }

        $this->validateConfig($config);
        $this->config = $config;
        $this->baseUrl = $config['api']['base_url'] ?? self::SANDBOX_URL;

        $this->initializeHttpClient();
        $this->mnoService = new \Myzuwa\PawaPay\Service\MNOService($this->httpClient);
    }

    /**
     * Get available mobile network operators for a country
     *
     * @param string $country ISO country code
     * @return array
     */
    public function getAvailableOperators(string $country): array
    {
        return $this->mnoService->getAvailableOperators($country);
    }

    // Backwards compatible alias expected by tests
    public function getMobileOperators(string $country = 'ZMB')
    {
        return $this->getAvailableOperators($country);
    }

    /**
     * Predict operator from phone number
     *
     * @param string $phoneNumber
     * @param string $country
     * @return array|null
     */
    public function predictOperator(string $phoneNumber, string $country): ?array
    {
        $operator = $this->mnoService->predictOperator($phoneNumber, $country);

        if (is_string($operator)) {
            return ['provider' => $operator];
        }

        return $operator;
    }

    /**
     * Validate phone number for specific operator
     *
     * @param string $phoneNumber
     * @param string $operatorCode
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber, string $operatorCode): bool
    {
        return $this->mnoService->validatePhoneNumber($phoneNumber, $operatorCode);
    }

    /**
     * Initialize the HTTP client
     */
    private function initializeHttpClient(): void
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api']['token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Set a custom HTTP client (mainly for testing)
     *
     * @param \GuzzleHttp\ClientInterface $client
     * @return void
     */
    public function setHttpClient(\GuzzleHttp\ClientInterface $client): void
    {
        $this->httpClient = $client;
    }

    /**
     * Get the HTTP client instance
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public function getHttpClient(): \GuzzleHttp\ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Get the current environment
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Create a Payment Page for redirect-based payments
     *
     * This method creates a hosted payment page URL that customers can be redirected to.
     * The payment page provides a complete mobile money payment experience.
     *
     * @param array $data Payment page data containing:
     *                    - depositId: UUIDv4 unique ID (required)
     *                    - returnUrl: URL to redirect customer after payment (required)
     *                    - narration: Short description 4-22 chars (required)
     *                    - reason: Payment reason 1-50 chars (optional)
     *                    - phoneNumber: Customer phone number (optional)
     *                    - country: ISO 3166-1 alpha-3 country code (optional)
     *                    - language: Payment page language (optional)
     *                    - amountDetails: Fixed amount configuration (optional)
     *                    - metadata: Additional context data (optional)
     * @return array Response containing redirectURL
     * @throws PaymentGatewayException
     */
    public function createPaymentPage(array $data): array
    {
        try {
            $preparedData = $this->preparePaymentPageData($data);
            error_log("PawaPay Payment Page Payload: " . json_encode($preparedData));

            $response = $this->httpClient->post('/v2/payment-page/deposit', [
                'json' => $preparedData
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Store payment page session for tracking
            $this->storePaymentPageSession($preparedData['depositId'], $result, $preparedData);
            
            return $result;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
            error_log("PawaPay Payment Page Error: " . $responseBody);
            
            throw new PaymentGatewayException(
                "Failed to create payment page: " . $e->getMessage() . " | Response: " . $responseBody,
                0,
                [
                    'raw_error' => $e,
                    'response_body' => $responseBody,
                    'request_payload' => $preparedData
                ]
            );
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to create payment page: " . $e->getMessage(),
                0,
                ['raw_error' => $e]
            );
        }
    }

    /**
     * Initiate a deposit transaction
     *
     * @param array $data Deposit data
     * @return array Response data
     * @throws PaymentGatewayException
     */
    public function initiateDeposit(array $data): array
    {
        try {
            $preparedData = $this->prepareDepositData($data);
            error_log("Final PawaPay Payload: " . json_encode($preparedData)); // Added for deep debugging

            $response = $this->httpClient->post('/v2/deposits', [
                'json' => $preparedData
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Get full response body for detailed error logging
            $responseBody = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';

            // Log the full error details
            error_log("PawaPay Full API Error Response: " . $responseBody);
            error_log("PawaPay Request Payload: " . json_encode($this->prepareDepositData($data)));

            throw new PaymentGatewayException(
                "Failed to initiate deposit: " . $e->getMessage() . " | Full Response: " . $responseBody,
                0,
                [
                    'raw_error' => $e,
                    'response_body' => $responseBody,
                    'request_payload' => $this->prepareDepositData($data)
                ]
            );
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to initiate deposit: " . $e->getMessage(),
                0,
                ['raw_error' => $e]
            );
        }
    }

    // Backwards compatible alias expected by tests
    public function createDeposit(array $data, ?string $idempotencyKey = null): array
    {
        $options = [];
        if ($idempotencyKey) {
            $options['headers'] = ['Idempotency-Key' => $idempotencyKey];
        }

        try {
            $response = $this->httpClient->post('/v2/deposits', array_merge(['json' => $this->prepareDepositData($data)], $options));
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to create deposit: " . $e->getMessage(),
                0,
                ['raw_error' => $e]
            );
        }
    }

    /**
     * Check deposit status
     *
     * @param string $depositId
     * @return array
     * @throws PaymentGatewayException
     */
    public function checkDepositStatus(string $depositId): array
    {
        try {
            $response = $this->httpClient->get("/v2/deposits/{$depositId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to check deposit status: " . $e->getMessage(),
                0,
                ['deposit_id' => $depositId]
            );
        }
    }

    /**
     * Initiate a payout (vendor payment)
     *
     * @param array $data Payout data
     * @return array Response data
     * @throws PaymentGatewayException
     */
    public function initiatePayout(array $data): array
    {
        try {
            $response = $this->httpClient->post('/v2/payouts', [
                'json' => $this->preparePayoutData($data)
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to initiate payout: " . $e->getMessage(),
                0,
                ['payout_data' => $data]
            );
        }
    }

    /**
     * Check payout status
     *
     * @param string $payoutId
     * @return array
     * @throws PaymentGatewayException
     */
    public function checkPayoutStatus(string $payoutId): array
    {
        try {
            $response = $this->httpClient->get("/v2/payouts/{$payoutId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to check payout status: " . $e->getMessage(),
                0,
                ['payout_id' => $payoutId]
            );
        }
    }

    /**
     * Resend payout callback
     *
     * @param string $payoutId
     * @return array
     * @throws PaymentGatewayException
     */
    public function resendPayoutCallback(string $payoutId): array
    {
        try {
            $response = $this->httpClient->post("/v2/payouts/resend-callback/{$payoutId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to resend payout callback: " . $e->getMessage(),
                0,
                ['payout_id' => $payoutId]
            );
        }
    }

    /**
     * Bulk payouts
     *
     * @param array $payouts Array of payout data
     * @return array Response data
     * @throws PaymentGatewayException
     */
    public function initiateBulkPayouts(array $payouts): array
    {
        try {
            $preparedPayouts = array_map([$this, 'preparePayoutData'], $payouts);

            $response = $this->httpClient->post('/v2/payouts/bulk', [
                'json' => $preparedPayouts
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to initiate bulk payouts: " . $e->getMessage(),
                0,
                ['payout_count' => count($payouts)]
            );
        }
    }

    /**
     * Cancel enqueued payout
     *
     * @param string $payoutId
     * @return array
     * @throws PaymentGatewayException
     */
    public function cancelEnqueuedPayout(string $payoutId): array
    {
        try {
            $response = $this->httpClient->post("/v2/payouts/fail-enqueued/{$payoutId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to cancel enqueued payout: " . $e->getMessage(),
                0,
                ['payout_id' => $payoutId]
            );
        }
    }

    /**
     * Wallet balances
     *
     * @param string|null $country ISO country code filter
     * @return array
     * @throws PaymentGatewayException
     */
    public function getWalletBalances(?string $country = null): array
    {
        try {
            $uri = '/v2/wallet-balances';
            if ($country) {
                $uri .= "?country={$country}";
            }

            $response = $this->httpClient->get($uri);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to get wallet balances: " . $e->getMessage(),
                0,
                ['country' => $country]
            );
        }
    }

    /**
     * Initiate a refund
     *
     * @param array $data Refund data
     * @return array
     * @throws PaymentGatewayException
     */
    public function initiateRefund(array $data): array
    {
        try {
            $response = $this->httpClient->post('/v2/refunds', [
                'json' => $this->prepareRefundData($data)
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to initiate refund: " . $e->getMessage(),
                0,
                ['refund_data' => $data]
            );
        }
    }



    /**
     * Verify webhook signature
     *
     * @param array $data Webhook payload
     * @param string|null $signature Signature from X-PawaPay-Signature header
     * @return bool
     */
    public function verifyWebhookSignature(array $data, ?string $signature = null): bool
    {
        if (!$signature) {
            $signature = $_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? '';
        }

        if (empty($signature)) {
            return false;
        }

        $payload = json_encode($data);
        $expectedSignature = hash_hmac('sha256', $payload, $this->config['webhook_secret']);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process callback data
     *
     * @param array $data
     * @return array Processed callback data
     */
    public function processCallback(array $data): array
    {
        // Validate required callback fields
        if (!isset($data['depositId'], $data['status'])) {
            throw new PaymentGatewayException("Invalid callback data");
        }

        return [
            'transaction_id' => $data['depositId'],
            'status' => $data['status'],
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'metadata' => $data['metadata'] ?? []
        ];
    }

    /**
     * Prepare deposit data
     *
     * @param array $data
     * @return array
     */
    private function prepareDepositData(array $data): array
    {
        $required = ['amount', 'currency', 'phoneNumber', 'provider'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required deposit field: {$field}");
            }
        }

        // PawaPay API expects amounts as strings
        $amount = is_string($data['amount']) ? $data['amount'] : number_format((float)$data['amount'], 2, '.', '');

        // Generate depositId if not provided (UUIDv4 format)
        $depositId = $data['depositId'] ?? sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,  // Version 4
            mt_rand(0, 0x3fff) | 0x8000,  // Variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Store phone number in global for country detection
        $GLOBALS['phoneNumber'] = $data['phoneNumber'];

        // Auto-detect country from phone number
        $country = $this->detectCountry($data['phoneNumber']);

        // Validate currency for country
        $supportedCurrencies = $this->getSupportedCurrencies($country);
        if (!in_array(strtoupper($data['currency']), $supportedCurrencies)) {
            throw new PaymentGatewayException(
                "Currency {$data['currency']} not supported in {$country}. Supported: " .
                implode(', ', $supportedCurrencies)
            );
        }

        return [
            'depositId' => $depositId,  // Required by PawaPay v2 API
            'amount' => $amount,
            'currency' => strtoupper($data['currency']),
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $data['phoneNumber'],
                    'provider' => $this->mapProviderToApiCode($data['provider'], $country)
                ]
            ]
        ];
    }

    /**
     * Process multi-country payment with automatic country detection
     *
     * @param array $paymentData
     * @return array
     * @throws PaymentGatewayException
     */
    public function processMultiCountryPayment(array $paymentData): array
    {
        // Auto-detect country from phone number
        $country = $this->detectCountry($paymentData['phoneNumber']);

        // Validate phone number for detected country
        if (!$this->validatePhoneForCountry($paymentData['phoneNumber'], $country)) {
            throw new PaymentGatewayException(
                "Invalid phone number format for {$country}: {$paymentData['phoneNumber']}"
            );
        }

        // Validate currency for country
        $supportedCurrencies = $this->getSupportedCurrencies($country);
        if (!in_array(strtoupper($paymentData['currency']), $supportedCurrencies)) {
            throw new PaymentGatewayException(
                "Currency {$paymentData['currency']} not supported in {$country}. " .
                "Supported currencies: " . implode(', ', $supportedCurrencies)
            );
        }

        // Log country detection for debugging
        error_log("PawaPay Multi-Country: Detected country {$country} for phone {$paymentData['phoneNumber']}");

        // Process payment with country-specific logic
        return $this->initiateDeposit($paymentData);
    }

    /**
     * Map lowercase provider names to PawaPay API codes
     *
     * @param string $provider
     * @param string|null $country
     * @return string
     */
    private function mapProviderToApiCode(string $provider, ?string $country = null): string
    {
        // Auto-detect country from phone number if not provided
        if (!$country && isset($GLOBALS['phoneNumber'])) {
            $country = $this->detectCountry($GLOBALS['phoneNumber']);
        }
        $country = $country ?? 'ZMB'; // Default to Zambia

        $mapping = [
            // Zambia (ZMB)
            'airtel' => 'AIRTEL_OAPI_ZMB',
            'mtn' => 'MTN_MOMO_ZMB',
            'zamtel' => 'ZAMTEL_ZMB',

            // Ghana (GHA)
            'mtn_ghana' => 'MTN_MOMO_GHA',
            'vodafone' => 'VODAFONE_GHA',
            'airtel_tigo' => 'AIRTEL_TIGO_GHA',
            'mtn_gha' => 'MTN_MOMO_GHA',
            'vodafone_gha' => 'VODAFONE_GHA',
            'airteltigo' => 'AIRTEL_TIGO_GHA',

            // Kenya (KEN)
            'mpesa' => 'MPESA_KE',
            'airtel_kenya' => 'AIRTEL_KE',
            'mpesa_kenya' => 'MPESA_KE',
            'airtel_ke' => 'AIRTEL_KE',

            // Nigeria (NGA)
            'mtn_nigeria' => 'MTN_MOMO_NGA',
            'glo' => 'GLO_NGA',
            'airtel_nigeria' => 'AIRTEL_NGA',
            'mtn_nga' => 'MTN_MOMO_NGA',
            'glo_nga' => 'GLO_NGA',
            'airtel_nga' => 'AIRTEL_NGA',
        ];

        $lowercaseProvider = strtolower($provider);

        if (isset($mapping[$lowercaseProvider])) {
            return $mapping[$lowercaseProvider];
        }

        // If already in proper format, return as-is
        if (strpos($provider, '_' . $country) !== false) {
            return $provider;
        }

        // Try to get available operators and find match
        try {
            $operators = $this->getAvailableOperators($country);

            foreach ($operators['operators'] ?? [] as $code => $details) {
                $name = strtolower($details['name'] ?? '');
                if ($name === $lowercaseProvider) {
                    return $code;
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail
            error_log("Failed to map provider {$provider}: " . $e->getMessage());
        }

        throw new PaymentGatewayException("Invalid or unsupported provider: {$provider} for country: {$country}");
    }

    /**
     * Detect country from phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    private function detectCountry(string $phoneNumber): string
    {
        $prefixes = [
            '260' => 'ZMB', // Zambia
            '233' => 'GHA', // Ghana
            '254' => 'KEN', // Kenya
            '234' => 'NGA', // Nigeria
            '256' => 'UGA', // Uganda
            '255' => 'TZA', // Tanzania
            '225' => 'CIV', // Ivory Coast
            '221' => 'SEN', // Senegal
            '229' => 'BEN', // Benin
            '226' => 'BFA', // Burkina Faso
            '237' => 'CMR', // Cameroon
            '243' => 'COD', // DRC
            '241' => 'GAB', // Gabon
            '265' => 'MWI', // Malawi
            '258' => 'MOZ', // Mozambique
            '242' => 'COG', // Congo
            '250' => 'RWA', // Rwanda
            '232' => 'SLE', // Sierra Leone
        ];

        $countryCode = substr($phoneNumber, 0, 3);
        return $prefixes[$countryCode] ?? 'ZMB'; // Default to Zambia
    }

    /**
     * Get supported currencies for a country
     *
     * @param string $country
     * @return array
     */
    private function getSupportedCurrencies(string $country): array
    {
        $currencies = [
            'ZMB' => ['ZMW'],
            'GHA' => ['GHS'],
            'KEN' => ['KES'],
            'NGA' => ['NGN'],
            'UGA' => ['UGX'],
            'TZA' => ['TZS'],
            'CIV' => ['XOF'],
            'SEN' => ['XOF'],
            'BEN' => ['XOF'],
            'BFA' => ['XOF'],
            'CMR' => ['XAF'],
            'COD' => ['CDF', 'USD'],
            'GAB' => ['XAF'],
            'MWI' => ['MWK'],
            'MOZ' => ['MZN'],
            'COG' => ['XAF'],
            'RWA' => ['RWF'],
            'SLE' => ['SLL'],
        ];

        return $currencies[$country] ?? ['ZMW'];
    }

    /**
     * Validate phone number for specific country
     *
     * @param string $phoneNumber
     * @param string $country
     * @return bool
     */
    public function validatePhoneForCountry(string $phoneNumber, string $country): bool
    {
        $patterns = [
            'ZMB' => '/^260[0-9]{9}$/',     // Zambia: 260 + 9 digits = 260XXXXXXXXX
            'GHA' => '/^233[0-9]{9}$/',     // Ghana: 233 + 9 digits = 233XXXXXXXXX
            'KEN' => '/^254[0-9]{9}$/',     // Kenya: 254 + 9 digits = 254XXXXXXXXX
            'NGA' => '/^234[0-9]{10}$/',    // Nigeria: 234 + 10 digits = 234XXXXXXXXXX
            'UGA' => '/^256[0-9]{9}$/',     // Uganda: 256 + 9 digits = 256XXXXXXXXX
            'TZA' => '/^255[0-9]{9}$/',     // Tanzania: 255 + 9 digits = 255XXXXXXXXX
            'CIV' => '/^225[0-9]{8}$/',     // Ivory Coast: 225 + 8 digits = 225XXXXXXXX
            'SEN' => '/^221[0-9]{9}$/',     // Senegal: 221 + 9 digits = 221XXXXXXXXX
            'BEN' => '/^229[0-9]{8}$/',     // Benin: 229 + 8 digits = 229XXXXXXXX
            'BFA' => '/^226[0-9]{8}$/',     // Burkina Faso: 226 + 8 digits = 226XXXXXXXX
            'CMR' => '/^237[0-9]{8}$/',     // Cameroon: 237 + 8 digits = 237XXXXXXXX
            'COD' => '/^243[0-9]{9}$/',     // DRC: 243 + 9 digits = 243XXXXXXXXX
            'GAB' => '/^241[0-9]{8}$/',     // Gabon: 241 + 8 digits = 241XXXXXXXX
            'MWI' => '/^265[0-9]{9}$/',     // Malawi: 265 + 9 digits = 265XXXXXXXXX
            'MOZ' => '/^258[0-9]{9}$/',     // Mozambique: 258 + 9 digits = 258XXXXXXXXX
            'COG' => '/^242[0-9]{9}$/',     // Congo: 242 + 9 digits = 242XXXXXXXXX
            'RWA' => '/^250[0-9]{9}$/',     // Rwanda: 250 + 9 digits = 250XXXXXXXXX
            'SLE' => '/^232[0-9]{8}$/',     // Sierra Leone: 232 + 8 digits = 232XXXXXXXX
        ];

        $pattern = $patterns[$country] ?? $patterns['ZMB'];

        // Check if phone number matches pattern
        $isValid = preg_match($pattern, $phoneNumber) === 1;

        // Log validation details for debugging
        error_log("Phone validation for {$country}: {$phoneNumber} -> " .
                  ($isValid ? 'VALID' : 'INVALID') . " (pattern: {$pattern})");

        return $isValid;
    }

    /**
     * Prepare payout data
     *
     * @param array $data
     * @return array
     */
    private function preparePayoutData(array $data): array
    {
        $required = ['payoutId', 'amount', 'currency', 'recipient'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required payout field: {$field}");
            }
        }

        // Ensure recipient has correct structure
        if (!isset($data['recipient']['type']) || !isset($data['recipient']['accountDetails'])) {
            throw new PaymentGatewayException("Invalid recipient structure for payout");
        }

        return [
            'payoutId' => $data['payoutId'],
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'recipient' => [
                'type' => $data['recipient']['type'], // 'MMO'
                'accountDetails' => [
                    'provider' => $data['recipient']['accountDetails']['provider'],
                    'phoneNumber' => $data['recipient']['accountDetails']['phoneNumber']
                ]
            ],
            'customerMessage' => $data['customerMessage'] ?? 'Payout from Myzuwa.com',
            'metadata' => $data['metadata'] ?? []
        ];
    }



    /**
     * Prepare refund data
     *
     * @param array $data
     * @return array
     */
    private function prepareRefundData(array $data): array
    {
        if (empty($data['depositId'])) {
            throw new PaymentGatewayException("Missing required refund field: depositId");
        }

        return [
            'depositId' => $data['depositId'],
            'amount' => $data['amount'] ?? null,
            'reason' => $data['reason'] ?? 'Customer requested refund'
        ];
    }
 
    /**
     * Validate SDK configuration
     *
     * @param array $config
     * @throws PaymentGatewayException
     */
    private function validateConfig(array $config)
    {
        if (!isset($config['api']['token'])) {
            throw new PaymentGatewayException("Missing required configuration field: api.token");
        }
    }

    /**
     * Prepare payment page data for API call
     *
     * @param array $data
     * @return array
     * @throws PaymentGatewayException
     */
    private function preparePaymentPageData(array $data): array
    {
        // Validate required fields
        $required = ['returnUrl', 'narration'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required payment page field: {$field}");
            }
        }

        // Generate depositId if not provided (UUIDv4 format)
        $depositId = $data['depositId'] ?? sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,  // Version 4
            mt_rand(0, 0x3fff) | 0x8000,  // Variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Validate narration length (4-22 characters)
        if (strlen($data['narration']) < 4 || strlen($data['narration']) > 22) {
            throw new PaymentGatewayException("Narration must be between 4 and 22 characters");
        }

        $payload = [
            'depositId' => $depositId,
            'returnUrl' => $data['returnUrl'],
            'narration' => $data['narration']
        ];

        // Add optional fields
        if (!empty($data['reason'])) {
            if (strlen($data['reason']) < 1 || strlen($data['reason']) > 50) {
                throw new PaymentGatewayException("Reason must be between 1 and 50 characters");
            }
            $payload['reason'] = $data['reason'];
        }

        if (!empty($data['phoneNumber'])) {
            // Validate phone number format
            if (!$this->validatePhoneNumberFormat($data['phoneNumber'])) {
                throw new PaymentGatewayException("Invalid phone number format. Use digits only with country code, no '+' prefix");
            }
            $payload['phoneNumber'] = $data['phoneNumber'];
        }

        if (!empty($data['country'])) {
            // Validate country code (ISO 3166-1 alpha-3)
            if (!preg_match('/^[A-Z]{3}$/', $data['country'])) {
                throw new PaymentGatewayException("Country must be ISO 3166-1 alpha-3 format (e.g., 'KEN')");
            }
            $payload['country'] = strtoupper($data['country']);
        }

        if (!empty($data['language'])) {
            $payload['language'] = $data['language'];
        }

        if (!empty($data['amountDetails'])) {
            $payload['amountDetails'] = $this->prepareAmountDetails($data['amountDetails']);
        }

        if (!empty($data['metadata']) && is_array($data['metadata'])) {
            if (count($data['metadata']) > 10) {
                throw new PaymentGatewayException("Maximum 10 metadata fields allowed");
            }
            $payload['metadata'] = $data['metadata'];
        }

        return $payload;
    }

    /**
     * Prepare amount details for payment page
     *
     * @param array $amountDetails
     * @return array
     * @throws PaymentGatewayException
     */
    private function prepareAmountDetails(array $amountDetails): array
    {
        $required = ['amount', 'currency'];
        foreach ($required as $field) {
            if (!isset($amountDetails[$field])) {
                throw new PaymentGatewayException("Missing required amount detail field: {$field}");
            }
        }

        // Ensure amount is string format
        $amount = is_string($amountDetails['amount']) ? 
            $amountDetails['amount'] : 
            number_format((float)$amountDetails['amount'], 2, '.', '');

        return [
            'amount' => $amount,
            'currency' => strtoupper($amountDetails['currency'])
        ];
    }

    /**
     * Validate phone number format for payment page
     *
     * @param string $phoneNumber
     * @return bool
     */
    private function validatePhoneNumberFormat(string $phoneNumber): bool
    {
        // Phone number should be digits only, with country code, no '+' prefix
        // Should not start with zero
        return preg_match('/^[1-9][0-9]{8,14}$/', $phoneNumber) === 1;
    }

    /**
     * Store payment page session for tracking and validation
     *
     * @param string $depositId
     * @param array $response
     * @param array $originalData
     * @return void
     */
    private function storePaymentPageSession(string $depositId, array $response, array $originalData): void
    {
        $sessionData = [
            'deposit_id' => $depositId,
            'redirect_url' => $response['redirectURL'] ?? null,
            'return_url' => $originalData['returnUrl'],
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')), // Payment pages expire after 15 minutes
            'status' => 'created',
            'metadata' => $originalData['metadata'] ?? []
        ];

        // Store in session file (you can replace this with database storage)
        $sessionFile = __DIR__ . '/storage/payment_sessions/' . $depositId . '.json';
        $sessionDir = dirname($sessionFile);

        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0777, true);
        }

        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
        
        error_log("Payment page session stored: {$depositId}");
    }

    /**
     * Get payment page session data
     *
     * @param string $depositId
     * @return array|null
     */
    public function getPaymentPageSession(string $depositId): ?array
    {
        $sessionFile = __DIR__ . '/storage/payment_sessions/' . $depositId . '.json';
        
        if (!file_exists($sessionFile)) {
            return null;
        }

        $sessionData = json_decode(file_get_contents($sessionFile), true);
        
        // Check if session is expired
        if (strtotime($sessionData['expires_at']) < time()) {
            $sessionData['status'] = 'expired';
        }

        return $sessionData;
    }

    /**
     * Handle return from payment page
     *
     * This method processes the customer return from the payment page
     * and provides the payment status.
     *
     * @param string $depositId The deposit ID from the return URL
     * @return array Payment status and details
     * @throws PaymentGatewayException
     */
    public function handlePaymentPageReturn(string $depositId): array
    {
        try {
            // Get session data
            $session = $this->getPaymentPageSession($depositId);
            if (!$session) {
                throw new PaymentGatewayException("Payment session not found: {$depositId}");
            }

            // Check current deposit status via API
            $depositStatus = $this->checkDepositStatus($depositId);
            
            // Update session status
            $session['status'] = $this->mapDepositStatus($depositStatus['status'] ?? 'unknown');
            $session['updated_at'] = date('Y-m-d H:i:s');
            $session['deposit_response'] = $depositStatus;
            
            // Save updated session
            $this->updatePaymentPageSession($depositId, $session);
            
            return [
                'depositId' => $depositId,
                'status' => $session['status'],
                'deposit_details' => $depositStatus,
                'session_data' => $session,
                'return_handled_at' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            error_log("Payment page return handling error: " . $e->getMessage());
            throw new PaymentGatewayException(
                "Failed to handle payment page return: " . $e->getMessage(),
                0,
                ['deposit_id' => $depositId, 'raw_error' => $e]
            );
        }
    }

    /**
     * Map PawaPay deposit status to internal status
     *
     * @param string $pawaPayStatus
     * @return string
     */
    private function mapDepositStatus(string $pawaPayStatus): string
    {
        $statusMap = [
            'ACCEPTED' => 'accepted',
            'ENQUEUED' => 'pending', 
            'SUBMITTED' => 'processing',
            'IN_RECONCILIATION' => 'reconciling',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'REJECTED' => 'rejected',
            'DUPLICATE' => 'duplicate'
        ];

        return $statusMap[strtoupper($pawaPayStatus)] ?? 'unknown';
    }

    /**
     * Update payment page session data
     *
     * @param string $depositId
     * @param array $sessionData
     * @return void
     */
    private function updatePaymentPageSession(string $depositId, array $sessionData): void
    {
        $sessionFile = __DIR__ . '/storage/payment_sessions/' . $depositId . '.json';
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    }

    /**
     * Get redirect URL for payment processing
     *
     * Convenience method to get the redirect URL from a payment page response
     *
     * @param array $paymentPageResponse Response from createPaymentPage()
     * @return string The URL to redirect customer to
     * @throws PaymentGatewayException
     */
    public function getRedirectUrl(array $paymentPageResponse): string
    {
        if (!isset($paymentPageResponse['redirectURL'])) {
            throw new PaymentGatewayException("No redirect URL found in payment page response");
        }

        return $paymentPageResponse['redirectURL'];
    }

    /**
     * Test metadata handling (for debugging purposes)
     *
     * @param array $data
     * @return array
     */
    public function testMetadataHandling(array $data): array
    {
        return $this->prepareDepositData($data);
    }
}
