<?php

namespace Myzuwa\PawaPay;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;

/**
 * Webhook Handler for PawaPay SDK
 *
 * Handles incoming webhooks from PawaPay API with signature verification
 * and payload processing for both deposits and payouts.
 */
class WebhookHandler
{
    private $webhookSecret;

    public function __construct($webhookSecret)
    {
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook($payload, array $headers)
    {
        $signature = isset($headers['X-PawaPay-Signature']) ? $headers['X-PawaPay-Signature'] : (isset($headers['HTTP_X_PAWAPAY_SIGNATURE']) ? $headers['HTTP_X_PAWAPAY_SIGNATURE'] : '');

        if (empty($signature)) {
            throw new PaymentGatewayException('Missing webhook signature');
        }

        if (!$this->verifySignature($payload, $signature)) {
            throw new PaymentGatewayException('Invalid webhook signature');
        }

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PaymentGatewayException('Invalid webhook payload JSON');
        }

        $webhookType = isset($data['type']) ? $data['type'] : '';
        switch ($webhookType) {
            case 'deposit.completed':
            case 'deposit.failed':
                return $this->handleDepositWebhook($data);

            case 'payout.completed':
            case 'payout.failed':
                return $this->handlePayoutWebhook($data);

            default:
                throw new PaymentGatewayException('Unknown webhook type: ' . $webhookType);
        }
    }

    /**
     * Handle deposit webhook
     */
    private function handleDepositWebhook(array $data): array
    {
        $required = ['depositId', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new PaymentGatewayException("Missing required field in deposit webhook: {$field}");
            }
        }

        return [
            'handled' => true,
            'type' => 'deposit',
            'depositId' => $data['depositId'],
            'status' => $data['status'],
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'provider' => $data['provider'] ?? null,
            'reference' => $data['reference'] ?? null,
            'processed_at' => date('Y-m-d H:i:s'),
            'metadata' => $data['metadata'] ?? []
        ];
    }

    /**
     * Handle payout webhook
     */
    private function handlePayoutWebhook(array $data): array
    {
        $required = ['payoutId', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new PaymentGatewayException("Missing required field in payout webhook: {$field}");
            }
        }

        return [
            'handled' => true,
            'type' => 'payout',
            'payoutId' => $data['payoutId'],
            'status' => $data['status'],
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'provider' => $data['provider'] ?? null,
            'reference' => $data['reference'] ?? null,
            'processed_at' => date('Y-m-d H:i:s'),
            'metadata' => $data['metadata'] ?? []
        ];
    }
}
