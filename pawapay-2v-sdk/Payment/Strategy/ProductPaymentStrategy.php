<?php
namespace Myzuwa\PawaPay\Payment\Strategy;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use Myzuwa\PawaPay\Payment\Model\PaymentContext;

/**
 * Product Purchase Payment Strategy
 * 
 * Handles payment processing for product purchases with specific
 * validation rules and business logic.
 */
class ProductPaymentStrategy extends AbstractPaymentStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process(PaymentContext $context): array
    {
        $this->validate($context);

        try {
            $payload = $this->prepareBasePayload($context);
            
            // Add product-specific metadata
            $payload['metadata'] = array_merge(
                $payload['metadata'],
                [
                    'order_items' => $context->getMetadata()['order_items'] ?? [],
                    'shipping_method' => $context->getMetadata()['shipping_method'] ?? null,
                ]
            );

            // Initialize deposit
            $response = $this->httpClient->request('POST', '/v2/deposits', [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            if ($result['status'] === 'REJECTED') {
                throw new PaymentGatewayException(
                    $result['failureReason']['failureMessage'] ?? 'Payment rejected',
                    4103,
                    ['error_code' => $result['failureReason']['failureCode'] ?? 'PAYMENT_REJECTED']
                );
            }

            return [
                'transaction_id' => $result['depositId'],
                'status' => $result['status'],
                'created' => $result['created'],
                'reference' => $context->getTransactionRef()
            ];

        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                'Failed to process product payment: ' . $e->getMessage(),
                is_numeric($e->getCode()) ? $e->getCode() : 0,
                [
                    'context' => [
                        'reference' => $context->getTransactionRef(),
                        'amount' => $context->getAmount()->toString(),
                        'payment_type' => $context->getPaymentType()
                    ],
                    'original_exception' => $e
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate(PaymentContext $context): bool
    {
        // Call parent validation first
        parent::validate($context);

        // Product-specific validation
        if (empty($context->getMetadata()['order_items'])) {
            throw new PaymentGatewayException(
                'Product payment requires order items',
                4101,
                ['error_code' => 'MISSING_ORDER_ITEMS']
            );
        }

        // Validate each order item
        foreach ($context->getMetadata()['order_items'] as $item) {
            if (!isset($item['id'], $item['quantity'], $item['price'])) {
                throw new PaymentGatewayException(
                    'Invalid order item format',
                    4102,
                    ['error_code' => 'INVALID_ORDER_ITEM']
                );
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(array $callbackData, PaymentContext $context): array
    {
        $response = $this->prepareCallbackResponse($callbackData, $context);

        // Add product-specific callback handling
        if ($response['status'] === 'COMPLETED') {
            // Product inventory updates or other business logic could go here
            $response['order_status'] = 'processing';
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $paymentType): bool
    {
        return $paymentType === 'product';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializePaymentLimits(): void
    {
        // Product-specific payment limits
        $this->paymentLimits = [
            'ZMW' => ['min' => '10.00', 'max' => '50000.00'],
            'USD' => ['min' => '1.00', 'max' => '5000.00'],
            // Add more currencies as needed
        ];
    }
}