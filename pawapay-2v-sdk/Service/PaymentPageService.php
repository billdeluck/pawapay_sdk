<?php
/**
 * PawaPay Payment Page Service
 * 
 * Handles hosted payment page functionality including redirect flows,
 * session management, and return URL handling.
 *
 * @package     Myzuwa\PawaPay\Service
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Service;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use GuzzleHttp\ClientInterface;

class PaymentPageService
{
    /** @var ClientInterface HTTP client */
    private $httpClient;
    
    /** @var array Configuration */
    private $config;
    
    /** @var string Storage path for session data */
    private $storagePath;

    /**
     * Constructor
     *
     * @param ClientInterface $httpClient
     * @param array $config
     */
    public function __construct(ClientInterface $httpClient, array $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->storagePath = __DIR__ . '/../storage/payment_sessions/';
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    /**
     * Create a payment page session
     *
     * @param array $data Payment page configuration
     * @return array Payment page response with redirect URL
     * @throws PaymentGatewayException
     */
    public function create(array $data): array
    {
        $this->validatePaymentPageData($data);
        $payload = $this->preparePayload($data);
        
        try {
            $response = $this->httpClient->post('/v2/payment-page/deposit', [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Store session for tracking
            $this->storeSession($payload['depositId'], $result, $payload);
            
            return $result;
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseBody = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response';
            
            throw new PaymentGatewayException(
                "Payment page creation failed: " . $e->getMessage(),
                $e->getCode(),
                [
                    'request_data' => $payload,
                    'response' => $responseBody,
                    'http_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
                ]
            );
        }
    }

    /**
     * Handle customer return from payment page
     *
     * @param string $depositId
     * @param array $queryParams Query parameters from return URL
     * @return array Payment status and session details
     * @throws PaymentGatewayException
     */
    public function handleReturn(string $depositId, array $queryParams = []): array
    {
        $session = $this->getSession($depositId);
        
        if (!$session) {
            throw new PaymentGatewayException("Payment session not found: {$depositId}");
        }

        // Verify deposit status via API
        try {
            $depositResponse = $this->httpClient->get("/v2/deposits/{$depositId}");
            $depositData = json_decode($depositResponse->getBody()->getContents(), true);
            
            // Update session with latest status
            $session['payment_status'] = $depositData['status'] ?? 'unknown';
            $session['deposit_data'] = $depositData;
            $session['return_processed_at'] = date('Y-m-d H:i:s');
            $session['return_query_params'] = $queryParams;
            
            $this->updateSession($depositId, $session);
            
            return [
                'success' => true,
                'depositId' => $depositId,
                'status' => $this->normalizeStatus($depositData['status'] ?? 'unknown'),
                'session' => $session,
                'deposit_details' => $depositData
            ];
            
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                "Failed to verify payment status: " . $e->getMessage(),
                0,
                ['deposit_id' => $depositId, 'session' => $session]
            );
        }
    }

    /**
     * Get session data by deposit ID
     *
     * @param string $depositId
     * @return array|null
     */
    public function getSession(string $depositId): ?array
    {
        $sessionFile = $this->storagePath . $depositId . '.json';
        
        if (!file_exists($sessionFile)) {
            return null;
        }

        return json_decode(file_get_contents($sessionFile), true);
    }

    /**
     * Check if payment page session is expired
     *
     * @param array $session
     * @return bool
     */
    public function isExpired(array $session): bool
    {
        if (!isset($session['expires_at'])) {
            return true;
        }
        
        return strtotime($session['expires_at']) < time();
    }

    /**
     * Clean up expired sessions
     *
     * @return int Number of sessions cleaned
     */
    public function cleanExpiredSessions(): int
    {
        $cleaned = 0;
        $sessionFiles = glob($this->storagePath . '*.json');
        
        foreach ($sessionFiles as $file) {
            $session = json_decode(file_get_contents($file), true);
            
            if ($session && $this->isExpired($session)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    /**
     * Validate payment page data
     *
     * @param array $data
     * @throws PaymentGatewayException
     */
    private function validatePaymentPageData(array $data): void
    {
        $required = ['returnUrl', 'narration'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required field: {$field}");
            }
        }

        // Validate narration length
        if (strlen($data['narration']) < 4 || strlen($data['narration']) > 22) {
            throw new PaymentGatewayException("Narration must be 4-22 characters long");
        }

        // Validate returnUrl format
        if (!filter_var($data['returnUrl'], FILTER_VALIDATE_URL)) {
            throw new PaymentGatewayException("Invalid return URL format");
        }

        // Validate optional fields
        if (!empty($data['reason']) && (strlen($data['reason']) < 1 || strlen($data['reason']) > 50)) {
            throw new PaymentGatewayException("Reason must be 1-50 characters long");
        }

        if (!empty($data['phoneNumber']) && !$this->isValidPhoneNumber($data['phoneNumber'])) {
            throw new PaymentGatewayException("Invalid phone number format");
        }

        if (!empty($data['country']) && !$this->isValidCountryCode($data['country'])) {
            throw new PaymentGatewayException("Invalid country code. Use ISO 3166-1 alpha-3 format (e.g. 'KEN')");
        }
    }

    /**
     * Prepare API payload
     *
     * @param array $data
     * @return array
     */
    private function preparePayload(array $data): array
    {
        // Generate depositId if not provided
        $depositId = $data['depositId'] ?? $this->generateDepositId();

        $payload = [
            'depositId' => $depositId,
            'returnUrl' => $data['returnUrl'],
            'narration' => $data['narration']
        ];

        // Add optional fields
        $optionalFields = ['reason', 'phoneNumber', 'country', 'language'];
        foreach ($optionalFields as $field) {
            if (!empty($data[$field])) {
                $payload[$field] = $field === 'country' ? strtoupper($data[$field]) : $data[$field];
            }
        }

        // Add amount details if provided
        if (!empty($data['amountDetails'])) {
            $payload['amountDetails'] = $this->prepareAmountDetails($data['amountDetails']);
        }

        // Add metadata (up to 10 fields)
        if (!empty($data['metadata']) && is_array($data['metadata'])) {
            if (count($data['metadata']) > 10) {
                throw new PaymentGatewayException("Maximum 10 metadata fields allowed");
            }
            $payload['metadata'] = $data['metadata'];
        }

        return $payload;
    }

    /**
     * Prepare amount details
     *
     * @param array $amountDetails
     * @return array
     * @throws PaymentGatewayException
     */
    private function prepareAmountDetails(array $amountDetails): array
    {
        if (empty($amountDetails['amount']) || empty($amountDetails['currency'])) {
            throw new PaymentGatewayException("Amount details must include 'amount' and 'currency'");
        }

        return [
            'amount' => number_format((float)$amountDetails['amount'], 2, '.', ''),
            'currency' => strtoupper($amountDetails['currency'])
        ];
    }

    /**
     * Store payment session
     *
     * @param string $depositId
     * @param array $response
     * @param array $requestData
     */
    private function storeSession(string $depositId, array $response, array $requestData): void
    {
        $session = [
            'deposit_id' => $depositId,
            'redirect_url' => $response['redirectURL'] ?? null,
            'return_url' => $requestData['returnUrl'],
            'narration' => $requestData['narration'],
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
            'status' => 'created',
            'request_data' => $requestData,
            'response_data' => $response
        ];

        $sessionFile = $this->storagePath . $depositId . '.json';
        file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Update session data
     *
     * @param string $depositId
     * @param array $sessionData
     */
    private function updateSession(string $depositId, array $sessionData): void
    {
        $sessionFile = $this->storagePath . $depositId . '.json';
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Generate UUIDv4 for deposit ID
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
     * Validate phone number format
     *
     * @param string $phoneNumber
     * @return bool
     */
    private function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Digits only, with country code, no '+' prefix, doesn't start with 0
        return preg_match('/^[1-9][0-9]{8,14}$/', $phoneNumber) === 1;
    }

    /**
     * Validate country code format
     *
     * @param string $countryCode
     * @return bool
     */
    private function isValidCountryCode(string $countryCode): bool
    {
        return preg_match('/^[A-Z]{3}$/', strtoupper($countryCode)) === 1;
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
}