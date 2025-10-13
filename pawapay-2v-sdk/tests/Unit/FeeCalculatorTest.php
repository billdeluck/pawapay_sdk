<?php

/**
 * ============================================================================
 * PAWAPAY SDK - FEE CALCULATOR UNIT TESTS
 * ============================================================================
 * 
 * Comprehensive tests for the PawaPay fee calculation system using real
 * fee structures from https://www.pawapay.io/fees
 * 
 * These tests validate:
 * - Correct fee calculations for all supported countries/operators
 * - Tiered fee structures (fixed, percentage, mixed)
 * - Environment configuration overrides
 * - Edge cases and error handling
 * - Fee display formatting
 * 
 * @author PawaPay SDK Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

namespace PawaPay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PawaPay\Service\FeeCalculator;
use PawaPay\Exception\FeeCalculationException;

class FeeCalculatorTest extends TestCase
{
    private FeeCalculator $calculator;
    
    protected function setUp(): void
    {
        $this->calculator = new FeeCalculator();
    }
    
    /**
     * Test Kenya M-PESA tiered fixed fee structure
     */
    public function testKenyaMpesaCollectionFees()
    {
        // Test small amount (KSh 50 - should be 0 + 1% = 0.50)
        $result = $this->calculator->calculateFee(50, 'KSH', 'KE', 'MPESA', 'collections');
        
        $this->assertEquals(0, $result['mmo_fee_amount']);
        $this->assertEquals(0.50, $result['pawapay_fee_amount']);
        $this->assertEquals(0.50, $result['total_fee_amount']);
        $this->assertEquals('tiered_fixed', $result['fee_type']);
        
        // Test medium amount (KSh 1000 - should be 10 + 1% = 10 + 10 = 20)
        $result = $this->calculator->calculateFee(1000, 'KSH', 'KE', 'MPESA', 'collections');
        
        $this->assertEquals(10, $result['mmo_fee_amount']);
        $this->assertEquals(10.00, $result['pawapay_fee_amount']);
        $this->assertEquals(20.00, $result['total_fee_amount']);
        
        // Test large amount (KSh 50000 - should be 108 + 1% = 108 + 500 = 608)
        $result = $this->calculator->calculateFee(50000, 'KSH', 'KE', 'MPESA', 'collections');
        
        $this->assertEquals(108, $result['mmo_fee_amount']);
        $this->assertEquals(500.00, $result['pawapay_fee_amount']);
        $this->assertEquals(608.00, $result['total_fee_amount']);
    }
    
    /**
     * Test Uganda MTN disbursement fees
     */
    public function testUgandaMtnDisbursementFees()
    {
        // Test small amount (UGX 300 - should be 0 + 1% = 3)
        $result = $this->calculator->calculateFee(300, 'UGX', 'UG', 'MTN', 'disbursements');
        
        $this->assertEquals(0, $result['mmo_fee_amount']);
        $this->assertEquals(3.00, $result['pawapay_fee_amount']);
        $this->assertEquals(3.00, $result['total_fee_amount']);
        
        // Test medium amount (UGX 100000 - should be 600 + 1% = 600 + 1000 = 1600)
        $result = $this->calculator->calculateFee(100000, 'UGX', 'UG', 'MTN', 'disbursements');
        
        $this->assertEquals(600, $result['mmo_fee_amount']);
        $this->assertEquals(1000.00, $result['pawapay_fee_amount']);
        $this->assertEquals(1600.00, $result['total_fee_amount']);
    }
    
    /**
     * Test Uganda Airtel percentage-based collection fees
     */
    public function testUgandaAirtelCollectionFees()
    {
        // Test UGX 10000 - should be 2.5% total = 250
        $result = $this->calculator->calculateFee(10000, 'UGX', 'UG', 'AIRTEL', 'collections');
        
        $this->assertEquals(150.00, $result['mmo_fee_amount']); // 1.5%
        $this->assertEquals(100.00, $result['pawapay_fee_amount']); // 1%
        $this->assertEquals(250.00, $result['total_fee_amount']); // 2.5%
        $this->assertEquals('percentage', $result['fee_type']);
    }
    
    /**
     * Test Zambia MTN mixed fee structure (percentage + fixed)
     */
    public function testZambiaMtnDisbursementMixedFees()
    {
        // Test ZMW 500 - should be 2% + 0.40 + 1% PawaPay = 10 + 0.40 + 5 = 15.40
        $result = $this->calculator->calculateFee(500, 'ZMW', 'ZM', 'MTN', 'disbursements');
        
        $this->assertEquals(10.40, $result['mmo_fee_amount']); // 2% + 0.40 fixed
        $this->assertEquals(5.00, $result['pawapay_fee_amount']); // 1%
        $this->assertEquals(15.40, $result['total_fee_amount']);
        $this->assertEquals('tiered_mixed', $result['fee_type']);
    }
    
    /**
     * Test fee override configuration
     */
    public function testFeeOverrideConfiguration()
    {
        // Test with 0% override (no fees)
        $calculator = new FeeCalculator(['fee_override_percentage' => 0.0]);
        $result = $calculator->calculateFee(1000, 'USD');
        
        $this->assertEquals(0, $result['total_fee_amount']);
        $this->assertEquals('override_zero', $result['fee_type']);
        
        // Test with 1.5% override
        $calculator = new FeeCalculator(['fee_override_percentage' => 0.015]);
        $result = $calculator->calculateFee(1000, 'USD');
        
        $this->assertEquals(15.00, $result['total_fee_amount']);
        $this->assertEquals('override', $result['fee_type']);
    }
    
    /**
     * Test calculateTotalWithFees method
     */
    public function testCalculateTotalWithFees()
    {
        $result = $this->calculator->calculateTotalWithFees(1000, 'KSH', 'KE', 'MPESA', 'collections');
        
        $this->assertEquals(1000, $result['original_amount']);
        $this->assertEquals(20.00, $result['fee_amount']); // 10 fixed + 10 PawaPay
        $this->assertEquals(1020.00, $result['total_amount']);
        $this->assertArrayHasKey('display_info', $result);
        $this->assertEquals('1,000.00', $result['display_info']['subtotal']);
        $this->assertEquals('20.00', $result['display_info']['fees']);
        $this->assertEquals('1,020.00', $result['display_info']['total']);
    }
    
    /**
     * Test supported countries and operators
     */
    public function testGetSupportedCountriesAndOperators()
    {
        $supported = $this->calculator->getSupportedCountriesAndOperators();
        
        $this->assertIsArray($supported);
        $this->assertArrayHasKey('KE', $supported);
        $this->assertArrayHasKey('UG', $supported);
        $this->assertArrayHasKey('ZM', $supported);
        
        $this->assertContains('MPESA', $supported['KE']);
        $this->assertContains('MTN', $supported['UG']);
        $this->assertContains('AIRTEL', $supported['UG']);
    }
    
    /**
     * Test fee preview functionality
     */
    public function testGetFeePreview()
    {
        $preview = $this->calculator->getFeePreview(1000, 'KSH', 'KE', 'MPESA');
        
        $this->assertEquals(1000, $preview['amount']);
        $this->assertEquals('KSH', $preview['currency']);
        $this->assertEquals('KE', $preview['country']);
        $this->assertEquals('MPESA', $preview['operator']);
        
        $this->assertTrue($preview['collections']['available']);
        $this->assertEquals(20.00, $preview['collections']['fee']);
        $this->assertEquals(1020.00, $preview['collections']['total']);
        
        $this->assertTrue($preview['disbursements']['available']);
        $this->assertIsFloat($preview['disbursements']['fee']);
    }
    
    /**
     * Test input validation
     */
    public function testInputValidation()
    {
        // Test negative amount
        $this->expectException(FeeCalculationException::class);
        $this->expectExceptionMessage('Amount must be greater than 0');
        $this->calculator->calculateFee(-100, 'USD');
    }
    
    /**
     * Test invalid currency code
     */
    public function testInvalidCurrency()
    {
        $this->expectException(FeeCalculationException::class);
        $this->expectExceptionMessage('Currency must be a valid 3-letter code');
        $this->calculator->calculateFee(100, 'INVALID');
    }
    
    /**
     * Test invalid country code
     */
    public function testInvalidCountry()
    {
        $this->expectException(FeeCalculationException::class);
        $this->expectExceptionMessage('Country must be a valid 2-letter code');
        $this->calculator->calculateFee(100, 'USD', 'INVALID');
    }
    
    /**
     * Test invalid transaction type
     */
    public function testInvalidTransactionType()
    {
        $this->expectException(FeeCalculationException::class);
        $this->expectExceptionMessage("Type must be 'collections' or 'disbursements'");
        $this->calculator->calculateFee(100, 'USD', 'KE', 'MPESA', 'invalid');
    }
    
    /**
     * Test fallback to default fee structure
     */
    public function testFallbackToDefaultFeeStructure()
    {
        // Test with unsupported country/operator combination
        $result = $this->calculator->calculateFee(100, 'USD', 'US', 'UNKNOWN', 'collections');
        
        $this->assertEquals(1.00, $result['pawapay_fee_amount']); // 1% PawaPay fee
        $this->assertEquals(1.00, $result['total_fee_amount']); // Only PawaPay fee
        $this->assertEquals('percentage', $result['fee_type']);
    }
    
    /**
     * Test minimum and maximum fee constraints
     */
    public function testFeeConstraints()
    {
        // Test minimum fee
        $calculator = new FeeCalculator(['minimum_fee' => 5.0]);
        $result = $calculator->calculateFee(10, 'USD'); // Would normally be 0.20
        
        $this->assertEquals(5.0, $result['total_fee_amount']);
        
        // Test maximum fee
        $calculator = new FeeCalculator(['maximum_fee' => 50.0]);
        $result = $calculator->calculateFee(10000, 'USD'); // Would normally be 200
        
        $this->assertEquals(50.0, $result['total_fee_amount']);
    }
    
    /**
     * Test fee precision rounding
     */
    public function testFeePrecisionRounding()
    {
        $calculator = new FeeCalculator(['round_precision' => 0]);
        $result = $calculator->calculateFee(123.45, 'USD');
        
        // Should round to nearest whole number
        $this->assertEquals(1.0, $result['pawapay_fee_amount']);
        $this->assertEquals(1.0, $result['total_fee_amount']);
    }
    
    /**
     * Test metadata in calculation results
     */
    public function testCalculationMetadata()
    {
        $result = $this->calculator->calculateFee(1000, 'KSH', 'KE', 'MPESA', 'collections');
        
        $this->assertArrayHasKey('calculation_metadata', $result);
        $this->assertEquals('KE', $result['calculation_metadata']['country']);
        $this->assertEquals('MPESA', $result['calculation_metadata']['operator']);
        $this->assertEquals('collections', $result['calculation_metadata']['type']);
        $this->assertEquals('KSH', $result['calculation_metadata']['currency']);
        $this->assertEquals(1000, $result['calculation_metadata']['original_amount']);
        $this->assertEquals('tiered_fixed', $result['calculation_metadata']['fee_structure_type']);
        $this->assertArrayHasKey('timestamp', $result['calculation_metadata']);
    }
    
    /**
     * Test real-world fee calculations for multiple countries
     */
    public function testRealWorldFeeCalculations()
    {
        $testCases = [
            // Kenya M-PESA
            ['amount' => 500, 'currency' => 'KSH', 'country' => 'KE', 'operator' => 'MPESA', 'expected_total' => 10.00], // 5 + 5
            
            // Uganda MTN collections (percentage fallback)
            ['amount' => 1000, 'currency' => 'UGX', 'country' => 'UG', 'operator' => 'MTN', 'expected_total' => 20.00], // 2%
            
            // Rwanda MTN collections
            ['amount' => 1000, 'currency' => 'RWF', 'country' => 'RW', 'operator' => 'MTN', 'expected_total' => 31.00], // 3.1%
            
            // Tanzania Airtel collections
            ['amount' => 1000, 'currency' => 'TZS', 'country' => 'TZ', 'operator' => 'AIRTEL', 'expected_total' => 21.80], // 2.18%
        ];
        
        foreach ($testCases as $case) {
            $result = $this->calculator->calculateFee(
                $case['amount'],
                $case['currency'],
                $case['country'],
                $case['operator'],
                'collections'
            );
            
            $this->assertEquals(
                $case['expected_total'],
                $result['total_fee_amount'],
                "Fee calculation failed for {$case['country']} {$case['operator']} with amount {$case['amount']}"
            );
        }
    }
}