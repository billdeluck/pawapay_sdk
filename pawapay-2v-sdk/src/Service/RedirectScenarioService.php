<?php
/**
 * PawaPay Redirect Scenario Service
 * 
 * Modular service for handling different payment page redirect scenarios.
 * Each scenario implements specific PawaPay use cases with proper validation.
 *
 * @package     Myzuwa\PawaPay\Service
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Service;

use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class RedirectScenarioService
{
    /** @var array Configuration */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Scenario: E-commerce checkout with known product price
     * Fixed amount, customer chooses payment method
     */
    public function ecommerceCheckout(array $params): array
    {
        $this->validateRequired($params, ['amount', 'currency', 'country', 'returnUrl', 'orderId']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'amount' => $params['amount'],
            'country' => strtoupper($params['country']),
            'narration' => $this->createNarration('Order ' . $params['orderId']),
            'reason' => "E-commerce purchase - Order #{$params['orderId']}",
            'metadata' => [
                ['orderId' => $params['orderId']],
                ['scenario' => 'ecommerce_checkout'],
                ['customerEmail' => $params['customerEmail'] ?? '', 'isPII' => true]
            ]
        ];
    }

    /**
     * Scenario: Subscription billing for registered users
     * Fixed amount and phone number (recurring payment)
     */
    public function subscriptionBilling(array $params): array
    {
        $this->validateRequired($params, ['amount', 'msisdn', 'returnUrl', 'subscriptionId']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'msisdn' => $params['msisdn'],
            'amount' => $params['amount'],
            'narration' => $this->createNarration('Subscription'),
            'reason' => "Subscription payment - {$params['subscriptionId']}",
            'metadata' => [
                ['subscriptionId' => $params['subscriptionId']],
                ['scenario' => 'subscription_billing'],
                ['billingCycle' => $params['billingCycle'] ?? 'monthly']
            ]
        ];
    }

    /**
     * Scenario: Wallet top-up with flexible amount
     * Customer enters desired top-up amount
     */
    public function walletTopup(array $params): array
    {
        $this->validateRequired($params, ['returnUrl', 'userId']);
        
        $payload = [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'narration' => $this->createNarration('Wallet topup'),
            'reason' => 'Wallet balance top-up',
            'metadata' => [
                ['userId' => $params['userId']],
                ['scenario' => 'wallet_topup']
            ]
        ];

        // Add optional country preference
        if (!empty($params['country'])) {
            $payload['country'] = strtoupper($params['country']);
        }

        // Add optional phone number
        if (!empty($params['msisdn'])) {
            $payload['msisdn'] = $params['msisdn'];
        }

        return $payload;
    }

    /**
     * Scenario: Bill payment with known amount
     * Customer paying utility bills, etc.
     */
    public function billPayment(array $params): array
    {
        $this->validateRequired($params, ['amount', 'currency', 'country', 'returnUrl', 'billType', 'billReference']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'amount' => $params['amount'],
            'country' => strtoupper($params['country']),
            'narration' => $this->createNarration($params['billType']),
            'reason' => "Bill payment - {$params['billType']} ({$params['billReference']})",
            'metadata' => [
                ['billType' => $params['billType']],
                ['billReference' => $params['billReference']],
                ['scenario' => 'bill_payment'],
                ['customerEmail' => $params['customerEmail'] ?? '', 'isPII' => true]
            ]
        ];
    }

    /**
     * Scenario: Donation with flexible or fixed amount
     * Supporting charitable contributions
     */
    public function donation(array $params): array
    {
        $this->validateRequired($params, ['returnUrl', 'cause']);
        
        $payload = [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'narration' => $this->createNarration('Donation'),
            'reason' => "Donation for {$params['cause']}",
            'metadata' => [
                ['cause' => $params['cause']],
                ['scenario' => 'donation'],
                ['donorName' => $params['donorName'] ?? 'Anonymous', 'isPII' => true]
            ]
        ];

        // Fixed amount donation
        if (!empty($params['amount']) && !empty($params['country'])) {
            $payload['amount'] = $params['amount'];
            $payload['country'] = strtoupper($params['country']);
        }

        // Pre-fill phone number if provided
        if (!empty($params['msisdn'])) {
            $payload['msisdn'] = $params['msisdn'];
        }

        return $payload;
    }

    /**
     * Scenario: Event ticket purchase
     * Fixed price tickets with attendee information
     */
    public function eventTicket(array $params): array
    {
        $this->validateRequired($params, ['amount', 'currency', 'country', 'returnUrl', 'eventId', 'ticketType']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'amount' => $params['amount'],
            'country' => strtoupper($params['country']),
            'narration' => $this->createNarration('Event ticket'),
            'reason' => "Event ticket - {$params['ticketType']}",
            'metadata' => [
                ['eventId' => $params['eventId']],
                ['ticketType' => $params['ticketType']],
                ['attendeeName' => $params['attendeeName'] ?? '', 'isPII' => true],
                ['attendeeEmail' => $params['attendeeEmail'] ?? '', 'isPII' => true],
                ['scenario' => 'event_ticket']
            ]
        ];
    }

    /**
     * Scenario: Marketplace vendor payment
     * Payment to specific vendor with commission handling
     */
    public function marketplaceVendorPayment(array $params): array
    {
        $this->validateRequired($params, ['amount', 'currency', 'country', 'returnUrl', 'vendorId', 'orderId']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'amount' => $params['amount'],
            'country' => strtoupper($params['country']),
            'narration' => $this->createNarration('Marketplace'),
            'reason' => "Marketplace purchase from vendor {$params['vendorId']}",
            'metadata' => [
                ['vendorId' => $params['vendorId']],
                ['orderId' => $params['orderId']],
                ['commission' => $params['commission'] ?? '0'],
                ['customerEmail' => $params['customerEmail'] ?? '', 'isPII' => true],
                ['scenario' => 'marketplace_vendor']
            ]
        ];
    }

    /**
     * Scenario: Premium service upgrade
     * User upgrading to premium features with known pricing
     */
    public function premiumUpgrade(array $params): array
    {
        $this->validateRequired($params, ['amount', 'currency', 'country', 'returnUrl', 'userId', 'planId']);
        
        return [
            'depositId' => $params['depositId'] ?? $this->generateDepositId(),
            'returnUrl' => $params['returnUrl'],
            'amount' => $params['amount'],
            'country' => strtoupper($params['country']),
            'narration' => $this->createNarration('Premium'),
            'reason' => "Premium upgrade - Plan {$params['planId']}",
            'metadata' => [
                ['userId' => $params['userId']],
                ['planId' => $params['planId']],
                ['upgradeType' => $params['upgradeType'] ?? 'premium'],
                ['scenario' => 'premium_upgrade']
            ]
        ];
    }

    /**
     * Get scenario configuration for specific use case
     *
     * @param string $scenarioName
     * @return array Scenario configuration
     */
    public function getScenarioConfig(string $scenarioName): array
    {
        $scenarios = [
            'ecommerce_checkout' => [
                'name' => 'E-commerce Checkout',
                'description' => 'Fixed amount payment for product purchases',
                'required_fields' => ['amount', 'currency', 'country', 'orderId'],
                'fixed_amount' => true,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'subscription_billing' => [
                'name' => 'Subscription Billing',
                'description' => 'Recurring payments for registered users',
                'required_fields' => ['amount', 'msisdn', 'subscriptionId'],
                'fixed_amount' => true,
                'fixed_phone' => true,
                'supports_metadata' => true
            ],
            'wallet_topup' => [
                'name' => 'Wallet Top-up',
                'description' => 'Flexible amount wallet funding',
                'required_fields' => ['userId'],
                'fixed_amount' => false,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'bill_payment' => [
                'name' => 'Bill Payment',
                'description' => 'Utility and service bill payments',
                'required_fields' => ['amount', 'currency', 'country', 'billType'],
                'fixed_amount' => true,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'donation' => [
                'name' => 'Donation',
                'description' => 'Charitable contributions (fixed or flexible)',
                'required_fields' => ['cause'],
                'fixed_amount' => false,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'event_ticket' => [
                'name' => 'Event Ticket',
                'description' => 'Event ticket purchases',
                'required_fields' => ['amount', 'currency', 'country', 'eventId'],
                'fixed_amount' => true,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'marketplace_vendor' => [
                'name' => 'Marketplace Vendor Payment',
                'description' => 'Payments to marketplace vendors',
                'required_fields' => ['amount', 'currency', 'country', 'vendorId'],
                'fixed_amount' => true,
                'fixed_phone' => false,
                'supports_metadata' => true
            ],
            'premium_upgrade' => [
                'name' => 'Premium Upgrade',
                'description' => 'Service upgrade payments',
                'required_fields' => ['amount', 'currency', 'country', 'userId', 'planId'],
                'fixed_amount' => true,
                'fixed_phone' => false,
                'supports_metadata' => true
            ]
        ];

        if (!isset($scenarios[$scenarioName])) {
            throw new PaymentGatewayException("Unknown scenario: {$scenarioName}");
        }

        return $scenarios[$scenarioName];
    }

    /**
     * Get all available scenarios
     *
     * @return array
     */
    public function getAvailableScenarios(): array
    {
        return [
            'ecommerce_checkout',
            'subscription_billing',
            'wallet_topup',
            'bill_payment',
            'donation',
            'event_ticket',
            'marketplace_vendor',
            'premium_upgrade'
        ];
    }

    /**
     * Validate required fields
     *
     * @param array $params
     * @param array $required
     * @throws PaymentGatewayException
     */
    private function validateRequired(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new PaymentGatewayException("Missing required field for scenario: {$field}");
            }
        }
    }

    /**
     * Create narration (4-22 characters)
     *
     * @param string $description
     * @return string
     */
    private function createNarration(string $description): string
    {
        $narration = substr($description, 0, 22);
        if (strlen($narration) < 4) {
            $narration = str_pad($narration, 4, ' ');
        }
        return $narration;
    }

    /**
     * Generate UUID v4
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
}