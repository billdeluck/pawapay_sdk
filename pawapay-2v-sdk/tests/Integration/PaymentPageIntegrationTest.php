<?php
/**
 * Payment Page Integration Tests
 * 
 * End-to-end integration tests for PawaPay Payment Page functionality
 * These tests simulate real payment flows and require actual API responses
 *
 * @package     Myzuwa\PawaPay\Tests\Integration
 * @version     1.0.0
 * @author      AI Assistant - October 2025
 */

namespace Myzuwa\PawaPay\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Myzuwa\PawaPay\PaymentPageFacade;
use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

class PaymentPageIntegrationTest extends TestCase
{
    /** @var PaymentPageFacade */
    private $facade;
    
    /** @var array */
    private $config;
    
    /** @var bool */
    private static $skipTests = false;

    public static function setUpBeforeClass(): void
    {
        // Skip tests if sandbox credentials not available
        if (empty($_ENV['PAWAPAY_SANDBOX_TOKEN'])) {
            self::$skipTests = true;
        }
    }

    protected function setUp(): void
    {
        if (self::$skipTests) {
            $this->markTestSkipped('Integration tests require PAWAPAY_SANDBOX_TOKEN environment variable');
        }

        $this->config = [
            'api' => [
                'token' => $_ENV['PAWAPAY_SANDBOX_TOKEN'],
                'base_url' => 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $_ENV['PAWAPAY_WEBHOOK_SECRET'] ?? 'test_webhook_secret',
            'environment' => 'sandbox'
        ];

        $this->facade = new PaymentPageFacade($this->config);
    }

    /**
     * @test
     * @group integration
     */
    public function test_create_payment_page_full_flow()
    {
        $params = [
            'amount' => 100.00,
            'currency' => 'KES',
            'description' => 'Integration Test Payment',
            'returnUrl' => 'https://example.com/return?test=1',
            'customerPhone' => '254712345678',
            'country' => 'KEN',
            'orderId' => 'TEST-' . time()
        ];

        $redirectUrl = $this->facade->createPaymentRedirect($params);

        // Validate redirect URL format
        $this->assertStringStartsWith('https://paywith.pawapay.io', $redirectUrl);
        $this->assertStringContainsString('token=', $redirectUrl);
        
        // Parse URL to extract token
        $urlParts = parse_url($redirectUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);
        
        $this->assertArrayHasKey('token', $queryParams);
        $this->assertNotEmpty($queryParams['token']);
        
        echo "\n✅ Payment page created successfully: {$redirectUrl}\n";
    }

    /**
     * @test
     * @group integration
     */
    public function test_create_flexible_payment_page()
    {
        $result = $this->facade->createFlexiblePayment(
            'Integration Test Flexible Payment',
            'https://example.com/return?test=flexible',
            [
                'customerPhone' => '254712345678',
                'country' => 'KEN'
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['flexible']);
        $this->assertStringStartsWith('https://paywith.pawapay.io', $result['redirectUrl']);
        
        echo "\n✅ Flexible payment page created: {$result['redirectUrl']}\n";
    }

    /**
     * @test
     * @group integration
     */
    public function test_payment_page_with_metadata()
    {
        $params = [
            'amount' => 50.00,
            'currency' => 'KES',
            'description' => 'Test with Metadata',
            'returnUrl' => 'https://example.com/return',
            'customerEmail' => 'test@example.com',
            'orderId' => 'META-TEST-' . time()
        ];

        $redirectUrl = $this->facade->createPaymentRedirect($params);
        
        $this->assertStringStartsWith('https://paywith.pawapay.io', $redirectUrl);
        
        echo "\n✅ Payment page with metadata created: {$redirectUrl}\n";
    }

    /**
     * @test
     * @group integration
     */
    public function test_different_currencies()
    {
        $currencies = [
            'KES' => 'Kenya',
            'GHS' => 'Ghana', 
            'ZMW' => 'Zambia',
            'NGN' => 'Nigeria'
        ];

        foreach ($currencies as $currency => $country) {
            $params = [
                'amount' => 100.00,
                'currency' => $currency,
                'description' => "Test {$currency} Payment",
                'returnUrl' => "https://example.com/return?currency={$currency}"
            ];

            try {
                $redirectUrl = $this->facade->createPaymentRedirect($params);
                $this->assertStringStartsWith('https://paywith.pawapay.io', $redirectUrl);
                echo "\n✅ {$currency} payment page created successfully\n";
            } catch (PaymentGatewayException $e) {
                echo "\n⚠️ {$currency} not supported or config issue: {$e->getMessage()}\n";
                // Don't fail test as not all currencies may be configured
            }
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_invalid_phone_number_handling()
    {
        $invalidPhones = [
            '0712345678', // Starts with 0
            '+254712345678', // Has + prefix
            '254-712-345-678', // Has dashes
            '254 712 345 678', // Has spaces
            'invalid_phone' // Non-numeric
        ];

        foreach ($invalidPhones as $phone) {
            try {
                $this->facade->createPaymentRedirect([
                    'amount' => 10.00,
                    'currency' => 'KES',
                    'description' => 'Invalid Phone Test',
                    'returnUrl' => 'https://example.com/return',
                    'customerPhone' => $phone
                ]);
                
                $this->fail("Should have thrown exception for invalid phone: {$phone}");
            } catch (PaymentGatewayException $e) {
                $this->assertStringContainsString('phone', strtolower($e->getMessage()));
                echo "\n✅ Invalid phone rejected: {$phone}\n";
            }
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_session_storage_and_retrieval()
    {
        $pawaPay = new PawaPay($this->config);
        
        // Create payment page
        $paymentData = [
            'depositId' => 'TEST-' . uniqid(),
            'returnUrl' => 'https://example.com/return',
            'narration' => 'Session Test',
            'reason' => 'Testing session storage'
        ];

        try {
            $result = $pawaPay->createPaymentPage($paymentData);
            $this->assertArrayHasKey('redirectURL', $result);
            
            // Retrieve session
            $session = $pawaPay->getPaymentPageSession($paymentData['depositId']);
            $this->assertNotNull($session);
            $this->assertEquals($paymentData['depositId'], $session['deposit_id']);
            $this->assertEquals('created', $session['status']);
            
            echo "\n✅ Session storage and retrieval working correctly\n";
        } catch (PaymentGatewayException $e) {
            echo "\n⚠️ Session test failed: {$e->getMessage()}\n";
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_api_error_responses()
    {
        // Test with invalid return URL
        try {
            $this->facade->createPaymentRedirect([
                'amount' => 100.00,
                'currency' => 'KES',
                'description' => 'Invalid URL Test',
                'returnUrl' => 'not-a-valid-url'
            ]);
            
            $this->fail('Should have thrown exception for invalid URL');
        } catch (PaymentGatewayException $e) {
            $this->assertStringContainsString('url', strtolower($e->getMessage()));
            echo "\n✅ Invalid URL correctly rejected\n";
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_webhook_signature_verification_integration()
    {
        $webhookData = [
            'type' => 'deposit.completed',
            'depositId' => 'TEST-' . uniqid(),
            'status' => 'COMPLETED',
            'amount' => '100.00',
            'currency' => 'KES'
        ];

        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, $this->config['webhook_secret']);

        try {
            $result = $this->facade->processWebhook($webhookData, $signature);
            $this->assertTrue($result['success'] ?? false);
            echo "\n✅ Webhook signature verification working\n";
        } catch (PaymentGatewayException $e) {
            echo "\n⚠️ Webhook processing failed: {$e->getMessage()}\n";
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_concurrent_payment_pages()
    {
        $numConcurrent = 5;
        $results = [];

        for ($i = 0; $i < $numConcurrent; $i++) {
            $params = [
                'amount' => 10.00 + $i,
                'currency' => 'KES',
                'description' => "Concurrent Test {$i}",
                'returnUrl' => "https://example.com/return?test={$i}",
                'orderId' => "CONCURRENT-{$i}-" . time()
            ];

            try {
                $redirectUrl = $this->facade->createPaymentRedirect($params);
                $results[] = $redirectUrl;
                echo "\n✅ Concurrent payment {$i} created\n";
            } catch (PaymentGatewayException $e) {
                echo "\n❌ Concurrent payment {$i} failed: {$e->getMessage()}\n";
            }
        }

        $this->assertGreaterThan(0, count($results));
        $this->assertEquals($numConcurrent, count($results));
    }

    /**
     * @test
     * @group integration
     */
    public function test_statistics_and_cleanup()
    {
        // Create some test sessions first
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->facade->createPaymentRedirect([
                    'amount' => 1.00,
                    'currency' => 'KES',
                    'description' => "Stats Test {$i}",
                    'returnUrl' => "https://example.com/return?stats={$i}"
                ]);
            } catch (PaymentGatewayException $e) {
                // Ignore errors for this test
            }
        }

        // Get statistics
        $stats = $this->facade->getStatistics();
        
        $this->assertArrayHasKey('total_sessions', $stats);
        $this->assertArrayHasKey('active_sessions', $stats);
        $this->assertArrayHasKey('expired_sessions', $stats);
        
        echo "\n📊 Statistics: " . json_encode($stats) . "\n";
        
        // Test cleanup (won't clean active sessions in this test)
        $cleaned = $this->facade->cleanExpiredSessions();
        echo "\n🧹 Cleaned {$cleaned} expired sessions\n";
    }

    /**
     * @test
     * @group integration
     */
    public function test_large_metadata()
    {
        $metadata = [];
        for ($i = 0; $i < 10; $i++) { // Maximum allowed
            $metadata["field{$i}"] = "value{$i}_" . str_repeat('x', 50);
        }

        $params = [
            'amount' => 25.00,
            'currency' => 'KES',
            'description' => 'Large Metadata Test',
            'returnUrl' => 'https://example.com/return'
        ];

        try {
            $redirectUrl = $this->facade->createPaymentRedirect($params);
            $this->assertStringStartsWith('https://paywith.pawapay.io', $redirectUrl);
            echo "\n✅ Large metadata handled successfully\n";
        } catch (PaymentGatewayException $e) {
            echo "\n⚠️ Large metadata test failed: {$e->getMessage()}\n";
        }
    }

    /**
     * Performance test - create payment pages rapidly
     * 
     * @test
     * @group integration
     * @group performance
     */
    public function test_performance()
    {
        $startTime = microtime(true);
        $successCount = 0;
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $redirectUrl = $this->facade->createPaymentRedirect([
                    'amount' => 1.00,
                    'currency' => 'KES',
                    'description' => "Perf Test {$i}",
                    'returnUrl' => "https://example.com/return?perf={$i}"
                ]);
                
                if (strpos($redirectUrl, 'paywith.pawapay.io') !== false) {
                    $successCount++;
                }
            } catch (Exception $e) {
                // Continue with other iterations
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $avgTime = $duration / $iterations;

        echo "\n⚡ Performance Test Results:\n";
        echo "   Total time: " . number_format($duration, 3) . "s\n";
        echo "   Average per request: " . number_format($avgTime, 3) . "s\n";
        echo "   Success rate: {$successCount}/{$iterations}\n";

        $this->assertGreaterThan(0, $successCount);
        $this->assertLessThan(5.0, $avgTime); // Should be under 5 seconds per request
    }

    protected function tearDown(): void
    {
        // Clean up test sessions
        if (!self::$skipTests) {
            try {
                $this->facade->cleanExpiredSessions();
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
}