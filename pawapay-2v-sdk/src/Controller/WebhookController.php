<?php
namespace Myzuwa\PawaPay\Controller;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use Myzuwa\PawaPay\PawaPay;

class WebhookController
{
    private $pawaPay;
    private $config;

    public function __construct(PawaPay $pawaPay, array $config)
    {
        $this->pawaPay = $pawaPay;
        $this->config = $config;
    }

    /**
     * Handle PawaPay webhook callback
     *
     * @return array
     * @throws PaymentGatewayException
     */
    public function handleCallback(): array
    {
        try {
            // Get the raw POST data
            $payload = file_get_contents('php://input');
            $data = json_decode($payload, true);

            if (!$data) {
                throw new PaymentGatewayException('Invalid webhook payload');
            }

            // Get the signature from headers
            $signature = $_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? null;

            if (!$signature) {
                throw new PaymentGatewayException('Missing PawaPay signature');
            }

            // Verify the signature
            if (!$this->pawaPay->verifyWebhookSignature($data, $signature)) {
                throw new PaymentGatewayException('Invalid webhook signature');
            }

            // Process the webhook data
            $result = $this->pawaPay->processCallback($data);

            // Log the webhook (you can implement your logging mechanism here)
            $this->logWebhook($data, $result);

            return [
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'data' => $result
            ];

        } catch (\Exception $e) {
            // Log the error
            $this->logError($e);

            // Return error response
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Log webhook data
     *
     * @param array $payload
     * @param array $processedData
     * @return void
     */
    private function logWebhook(array $payload, array $processedData): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'payload' => $payload,
            'processed_data' => $processedData
        ];

        // For now, we'll just write to a log file
        $logFile = __DIR__ . '/../../../logs/webhook.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            $logFile,
            json_encode($logData, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );
    }

    /**
     * Log error
     *
     * @param \Exception $e
     * @return void
     */
    private function logError(\Exception $e): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];

        // For now, we'll just write to a log file
        $logFile = __DIR__ . '/../../../logs/webhook_errors.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            $logFile,
            json_encode($logData, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );
    }
}