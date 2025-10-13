<?php
namespace Myzuwa\PawaPay\Payment\Strategy;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use Myzuwa\PawaPay\Payment\Model\PaymentContext;
use Myzuwa\PawaPay\Service\MNOServiceInterface;

/**
 * Abstract base class for payment strategies
 * 
 * Provides common functionality and validation for all payment types.
 */
abstract class AbstractPaymentStrategy implements PaymentStrategyInterface
{
    /** @var \GuzzleHttp\ClientInterface */
    protected $httpClient;

    /** @var MNOServiceInterface */
    protected $mnoService;

    /** @var array Payment limits by currency */
    protected $paymentLimits = [];

    /**
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @param MNOServiceInterface $mnoService
     */
    public function __construct(
        \GuzzleHttp\ClientInterface $httpClient,
        MNOServiceInterface $mnoService
    ) {
        $this->httpClient = $httpClient;
        $this->mnoService = $mnoService;
        $this->initializePaymentLimits();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(PaymentContext $context): bool
    {
        // Validate phone number
        if (!$this->mnoService->validatePhoneNumber(
            $context->getPhoneNumber(),
            $context->getProviderCode()
        )) {
            throw new PaymentGatewayException(
                'Invalid phone number for selected provider',
                (int)4001,
                ['error_code' => 'INVALID_PHONE_NUMBER']
            );
        }

        // Validate operator availability
        if (!$this->mnoService->isOperatorAvailable($context->getProviderCode())) {
            throw new PaymentGatewayException(
                'Selected payment provider is currently unavailable',
                (int)4002,
                ['error_code' => 'PROVIDER_UNAVAILABLE']
            );
        }

        // Validate currency support
        $supportedCurrencies = $this->mnoService->getSupportedCurrencies(
            $context->getProviderCode()
        );
        if (!in_array($context->getAmount()->getCurrency(), $supportedCurrencies)) {
            throw new PaymentGatewayException(
                'Currency not supported by selected provider',
                (int)4003,
                ['error_code' => 'UNSUPPORTED_CURRENCY']
            );
        }

        // Validate payment limits
        $this->validatePaymentLimits($context);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentLimits(string $currency): array
    {
        return $this->paymentLimits[$currency] ?? [
            'min' => '1.00',
            'max' => '10000.00'
        ];
    }

    /**
     * Initialize payment limits for different currencies
     */
    protected function initializePaymentLimits(): void
    {
        // Default limits - should be overridden by specific implementations
        $this->paymentLimits = [
            'ZMW' => ['min' => '1.00', 'max' => '10000.00'],
            'USD' => ['min' => '1.00', 'max' => '1000.00'],
        ];
    }

    /**
     * Validate payment amount against limits
     *
     * @param PaymentContext $context
     * @throws PaymentGatewayException
     */
    protected function validatePaymentLimits(PaymentContext $context): void
    {
        $amount = $context->getAmount();
        $currency = $amount->getCurrency();
        $limits = $this->getPaymentLimits($currency);

        if (bccomp($amount->getAmount(), $limits['min'], 2) === -1) {
            throw new PaymentGatewayException(
                sprintf(
                    'Amount below minimum limit of %s %s',
                    $limits['min'],
                    $currency
                ),
                (int)4004,
                ['error_code' => 'AMOUNT_BELOW_MINIMUM']
            );
        }

        if (bccomp($amount->getAmount(), $limits['max'], 2) === 1) {
            throw new PaymentGatewayException(
                sprintf(
                    'Amount above maximum limit of %s %s',
                    $limits['max'],
                    $currency
                ),
                (int)4005,
                ['error_code' => 'AMOUNT_ABOVE_MAXIMUM']
            );
        }
    }

    /**
     * Prepare base payment payload
     *
     * @param PaymentContext $context
     * @return array
     */
    protected function prepareBasePayload(PaymentContext $context): array
    {
        return [
            'amount' => $context->getAmount()->getAmount(),
            'currency' => $context->getAmount()->getCurrency(),
            'recipient' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $context->getPhoneNumber(),
                    'provider' => $context->getProviderCode()
                ]
            ],
            'reference' => $context->getTransactionRef(),
            'metadata' => array_merge(
                $context->getMetadata(),
                [
                    'payment_type' => $context->getPaymentType(),
                    'created_at' => $context->getCreatedAt()->format('c')
                ]
            )
        ];
    }

    /**
     * Prepare callback response
     *
     * @param array $callbackData
     * @param PaymentContext $context
     * @return array
     */
    protected function prepareCallbackResponse(
        array $callbackData,
        PaymentContext $context
    ): array {
        return [
            'transaction_id' => $callbackData['depositId'] ?? null,
            'status' => $callbackData['status'] ?? 'UNKNOWN',
            'amount' => $context->getAmount()->getAmount(),
            'currency' => $context->getAmount()->getCurrency(),
            'reference' => $context->getTransactionRef(),
            'provider_transaction_id' => $callbackData['providerTransactionId'] ?? null,
            'payment_type' => $context->getPaymentType(),
            'metadata' => $context->getMetadata(),
            'raw_response' => $callbackData
        ];
    }
}