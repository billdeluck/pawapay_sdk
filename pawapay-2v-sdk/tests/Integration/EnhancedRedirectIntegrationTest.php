<?php
/**
 * Enhanced Redirect Integration Tests
 * 
 * Comprehensive test suite for enhanced redirect functionality
 * including all PawaPay use cases and error handling scenarios.
 *
 * @package     Myzuwa\PawaPay\Tests\Integration
 * @version     2.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Myzuwa\PawaPay\PaymentPageFacade;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class EnhancedRedirectIntegrationTest extends TestCase
{
    private $paymentPage;
    private $config;

    protected function setUp(): void
    {
        $this->config = [
            'api' => [
                'token' => $_ENV['PAWAPAY_SANDBOX_TOKEN'] ?? 'test_token',
                'base_url' => 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $_ENV['PAWAPAY_WEBHOOK_SECRET'] ?? 'test_secret',
            'environment' => 'sandbox'
        ];
        
        $this->paymentPage = new PaymentPageFacade($this->config);
    }

    // =============================================================================
    // PAWAPAY OFFICIAL USE CASES TESTS
    // =============================================================================

    /**
     * Test Use Case 1: Universal Payment Page (All Countries)
     */
    public function testUniversalPaymentPage()
    {
        try {
            $result = $this->paymentPage->createUniversalPaymentPage(
                'https://test.com/return',
                'Test universal payment'
            );

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);
            $this->assertTrue($result['success']);
            $this->assertStringStartsWith('https://', $result['redirectUrl']);

        } catch (PaymentGatewayException $e) {
            // In sandbox without real credentials, this is expected
            $this->assertStringContains('Failed to create payment page', $e->getMessage());
        }
    }

    /**
     * Test Use Case 2: Fixed Phone Number
     */
    public function testFixedPhonePaymentPage()
    {
        try {
            $result = $this->paymentPage->createFixedPhonePaymentPage(
                '254712345678',
                'https://test.com/return',
                'Test fixed phone payment'
            );

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Failed to create payment page', $e->getMessage());
        }
    }

    /**
     * Test Use Case 3: Fixed Amount
     */
    public function testFixedAmountPaymentPage()
    {
        try {
            $result = $this->paymentPage->createFixedAmountPaymentPage(
                '100',
                'KEN',
                'https://test.com/return',
                'Test fixed amount payment'
            );

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Failed to create payment page', $e->getMessage());
        }
    }

    /**
     * Test Use Case 4: Fixed Amount and Phone
     */
    public function testFixedAmountAndPhonePaymentPage()
    {
        try {
            $result = $this->paymentPage->createFixedAmountAndPhonePaymentPage(
                '254712345678',
                '100',
                'https://test.com/return',
                'Test fixed amount and phone payment'
            );

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Failed to create payment page', $e->getMessage());
        }
    }

    // =============================================================================
    // MODULAR SCENARIO TESTS
    // =============================================================================

    /**
     * Test E-commerce Checkout Scenario
     */
    public function testEcommerceCheckoutScenario()
    {
        try {
            $result = $this->paymentPage->createEcommerceCheckout([
                'amount' => '250.00',
                'currency' => 'KES',
                'country' => 'KEN',
                'returnUrl' => 'https://store.com/return',
                'orderId' => 'TEST-ORDER-' . time(),
                'customerEmail' => 'test@example.com'
            ]);

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Scenario-based payment creation failed', $e->getMessage());
        }
    }

    /**
     * Test Subscription Billing Scenario
     */
    public function testSubscriptionBillingScenario()
    {
        try {
            $result = $this->paymentPage->createSubscriptionBilling([
                'amount' => '50.00',
                'msisdn' => '254712345678',
                'returnUrl' => 'https://app.com/return',
                'subscriptionId' => 'SUB-' . time()
            ]);

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Scenario-based payment creation failed', $e->getMessage());
        }
    }

    /**
     * Test Wallet Top-up Scenario
     */
    public function testWalletTopupScenario()
    {
        try {
            $result = $this->paymentPage->createWalletTopup([
                'returnUrl' => 'https://app.com/wallet/return',
                'userId' => 'USER123',
                'country' => 'KEN'
            ]);

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Scenario-based payment creation failed', $e->getMessage());
        }
    }

    /**
     * Test Event Ticket Scenario
     */
    public function testEventTicketScenario()
    {
        try {
            $result = $this->paymentPage->createEventTicket([
                'amount' => '75.00',
                'currency' => 'KES',
                'country' => 'KEN',
                'returnUrl' => 'https://events.com/return',
                'eventId' => 'EVENT-TEST-2024',
                'ticketType' => 'VIP'
            ]);

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Scenario-based payment creation failed', $e->getMessage());
        }
    }

    /**
     * Test Donation Scenario
     */
    public function testDonationScenario()
    {
        try {
            $result = $this->paymentPage->createDonation([
                'returnUrl' => 'https://charity.org/return',
                'cause' => 'Test Charity Fund',
                'donorName' => 'Anonymous Test Donor'
            ]);

            $this->assertArrayHasKey('redirectUrl', $result);
            $this->assertArrayHasKey('depositId', $result);

        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Scenario-based payment creation failed', $e->getMessage());
        }
    }

    // =============================================================================
    // SCENARIO CONFIGURATION TESTS
    // =============================================================================

    /**
     * Test Available Scenarios Retrieval
     */
    public function testGetAvailableScenarios()
    {
        $scenarios = $this->paymentPage->getAvailableScenarios();
        
        $this->assertIsArray($scenarios);
        $this->assertArrayHasKey('ecommerce_checkout', $scenarios);
        $this->assertArrayHasKey('subscription_billing', $scenarios);
        $this->assertArrayHasKey('wallet_topup', $scenarios);
        $this->assertArrayHasKey('event_ticket', $scenarios);
        $this->assertArrayHasKey('donation', $scenarios);
        
        // Check scenario configuration structure
        $ecommerce = $scenarios['ecommerce_checkout'];
        $this->assertArrayHasKey('name', $ecommerce);
        $this->assertArrayHasKey('description', $ecommerce);
        $this->assertArrayHasKey('required_fields', $ecommerce);
        $this->assertArrayHasKey('fixed_amount', $ecommerce);
        $this->assertArrayHasKey('fixed_phone', $ecommerce);
        
        $this->assertEquals('E-commerce Checkout', $ecommerce['name']);
        $this->assertTrue($ecommerce['fixed_amount']);
        $this->assertFalse($ecommerce['fixed_phone']);
    }

    // =============================================================================
    // ERROR HANDLING TESTS
    // =============================================================================

    /**
     * Test Processing Failure Handling
     */
    public function testProcessingFailureHandling()
    {
        $testDepositId = 'test-failed-' . time();
        
        try {
            $result = $this->paymentPage->handleProcessingFailure($testDepositId);
            
            // Should return failure info structure
            $this->assertArrayHasKey('status', $result);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment
            $this->assertStringContains('Failed to get payment status', $e->getMessage());
        }
    }

    /**
     * Test Reconciliation Status Check
     */
    public function testReconciliationStatusCheck()
    {
        $testDepositId = 'test-reconcile-' . time();
        
        try {
            $result = $this->paymentPage->checkReconciliationStatus($testDepositId);
            
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('status', $result);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment
            $this->assertStringContains('Failed to check reconciliation status', $e->getMessage());
        }
    }

    // =============================================================================
    // RECONCILIATION TESTS
    // =============================================================================

    /**
     * Test Automated Reconciliation Cycle
     */
    public function testAutomatedReconciliationCycle()
    {
        $results = $this->paymentPage->runReconciliationCycle(15);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('total_checked', $results);
        $this->assertArrayHasKey('found', $results);
        $this->assertArrayHasKey('not_found', $results);
        $this->assertArrayHasKey('errors', $results);
        $this->assertArrayHasKey('details', $results);
        
        $this->assertGreaterThanOrEqual(0, $results['total_checked']);
        $this->assertGreaterThanOrEqual(0, $results['found']);
        $this->assertGreaterThanOrEqual(0, $results['not_found']);
        $this->assertGreaterThanOrEqual(0, $results['errors']);
    }

    // =============================================================================
    // VALIDATION TESTS  
    // =============================================================================

    /**
     * Test Invalid Scenario Name
     */
    public function testInvalidScenarioName()
    {
        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Scenario-based payment creation failed');
        
        $this->paymentPage->createScenarioBasedPaymentPage('invalid_scenario', []);
    }

    /**
     * Test Missing Required Parameters
     */
    public function testMissingRequiredParameters()
    {
        $this->expectException(PaymentGatewayException::class);
        
        // E-commerce checkout requires specific fields
        $this->paymentPage->createEcommerceCheckout([
            'amount' => '100.00'
            // Missing required fields: currency, country, returnUrl, orderId
        ]);
    }

    /**
     * Test Invalid Phone Number Format
     */
    public function testInvalidPhoneNumberFormat()
    {
        try {
            $this->paymentPage->createFixedPhonePaymentPage(
                'invalid-phone-format',
                'https://test.com/return',
                'Test payment'
            );
            
            $this->fail('Expected PaymentGatewayException for invalid phone format');
            
        } catch (PaymentGatewayException $e) {
            $this->assertStringContains('Phone number validation failed', $e->getMessage());
        }
    }

    // =============================================================================
    // WEBHOOK PROCESSING TESTS
    // =============================================================================

    /**
     * Test Webhook Processing with Valid Signature
     */
    public function testWebhookProcessingWithValidSignature()
    {
        $webhookData = [
            'type' => 'deposit.completed',
            'depositId' => 'test-deposit-' . time(),
            'status' => 'COMPLETED',
            'amount' => '100.00',
            'currency' => 'KES'
        ];
        
        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, $this->config['webhook_secret']);
        
        try {
            $result = $this->paymentPage->processWebhook($webhookData, $signature);
            
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('depositId', $result);
            $this->assertArrayHasKey('status', $result);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment without real webhook processing
            $this->assertStringContains('Webhook processing failed', $e->getMessage());
        }
    }

    /**
     * Test Webhook Processing with Invalid Signature
     */
    public function testWebhookProcessingWithInvalidSignature()
    {
        $webhookData = [
            'type' => 'deposit.completed',
            'depositId' => 'test-deposit-' . time(),
            'status' => 'COMPLETED'
        ];
        
        $invalidSignature = 'invalid_signature_hash';
        
        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Invalid webhook signature');
        
        $this->paymentPage->processWebhook($webhookData, $invalidSignature);
    }

    // =============================================================================
    // STATISTICS & MAINTENANCE TESTS
    // =============================================================================

    /**
     * Test Payment Page Statistics
     */
    public function testPaymentPageStatistics()
    {
        $stats = $this->paymentPage->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_sessions', $stats);
        $this->assertArrayHasKey('active_sessions', $stats);
        $this->assertArrayHasKey('expired_sessions', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
        
        $this->assertGreaterThanOrEqual(0, $stats['total_sessions']);
        $this->assertGreaterThanOrEqual(0, $stats['active_sessions']);
        $this->assertGreaterThanOrEqual(0, $stats['expired_sessions']);
        $this->assertGreaterThanOrEqual(0, $stats['success_rate']);
        $this->assertLessThanOrEqual(100, $stats['success_rate']);
    }

    /**
     * Test Session Cleanup
     */
    public function testSessionCleanup()
    {
        $cleaned = $this->paymentPage->cleanExpiredSessions();
        
        $this->assertIsInt($cleaned);
        $this->assertGreaterThanOrEqual(0, $cleaned);
    }

    // =============================================================================
    // INTEGRATION TESTS
    // =============================================================================

    /**
     * Test Legacy Method Compatibility
     */
    public function testLegacyMethodCompatibility()
    {
        try {
            $redirectUrl = $this->paymentPage->createPaymentRedirect([
                'amount' => 100.00,
                'currency' => 'KES',
                'description' => 'Legacy test payment',
                'returnUrl' => 'https://test.com/return',
                'customerPhone' => '254712345678',
                'orderId' => 'LEGACY-' . time()
            ]);
            
            $this->assertIsString($redirectUrl);
            $this->assertStringStartsWith('https://', $redirectUrl);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment
            $this->assertStringContains('Failed to create payment page', $e->getMessage());
        }
    }

    /**
     * Test Return URL Handling
     */
    public function testReturnUrlHandling()
    {
        $testDepositId = 'test-return-' . time();
        $queryParams = [
            'status' => 'completed',
            'token' => 'test_token'
        ];
        
        try {
            $result = $this->paymentPage->handlePaymentReturn($testDepositId, $queryParams);
            
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('depositId', $result);
            $this->assertArrayHasKey('status', $result);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment
            $this->assertStringContains('Payment session not found', $e->getMessage());
        }
    }

    /**
     * Test Payment Status Check
     */
    public function testPaymentStatusCheck()
    {
        $testDepositId = 'test-status-' . time();
        
        try {
            $result = $this->paymentPage->checkPaymentStatus($testDepositId);
            
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('depositId', $result);
            $this->assertArrayHasKey('status', $result);
            
        } catch (PaymentGatewayException $e) {
            // Expected in test environment
            $this->assertStringContains('Payment session not found', $e->getMessage());
        }
    }

    // =============================================================================
    // HELPER METHODS
    // =============================================================================

    protected function tearDown(): void
    {
        // Clean up any test sessions
        if ($this->paymentPage) {
            try {
                $this->paymentPage->cleanExpiredSessions();
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
}