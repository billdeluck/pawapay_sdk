<?php
/**
 * ============================================================================
 * MYZUWA.COM SIMULATION - PAWAPAY TESTING INTERFACE
 * ============================================================================
 * 
 * This web interface simulates the Modesy marketplace (like myzuwa.com)
 * for comprehensive PawaPay SDK testing with real API calls.
 * 
 * Features:
 * - Membership plan purchases
 * - Product payments with vendor commissions
 * - Wallet deposits
 * - Real-time Zambia fee calculations
 * - Live PawaPay API integration
 * 
 * @version 2.0.0
 * @since 2024-10-17
 * ============================================================================
 */

// Load dependencies and configurations
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../src/ZambiaFeeCalculator.php';

use PawaPay\Service\PaymentPageService;
use PawaPay\Config\PawaPayConfig;

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Initialize services
$zambiaFeeCalculator = new ZambiaFeeCalculator();
$pawaPayConfig = new PawaPayConfig();
$paymentService = new PaymentPageService($pawaPayConfig);

// Load configurations
$zambiaConfig = include __DIR__ . '/../config/zambia_config.php';
$operators = $zambiaFeeCalculator->getAvailableOperators();
$membershipPlans = $zambiaConfig['membership_plans'];
$testProducts = $zambiaConfig['test_products'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'calculate_fees':
                $amount = floatval($_POST['amount']);
                $operator = $_POST['operator'] ?? 'MTN_MOMO_ZMB';
                $result = $zambiaFeeCalculator->calculateZambiaFees($amount, $operator);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'compare_operators':
                $amount = floatval($_POST['amount']);
                $result = $zambiaFeeCalculator->compareOperatorFees($amount);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'initiate_membership_payment':
                $planId = $_POST['plan_id'];
                $operator = $_POST['operator'];
                $phoneNumber = $_POST['phone_number'];
                
                $membershipPricing = $zambiaFeeCalculator->calculateMembershipPricing($planId, $operator);
                $plan = $membershipPricing['plan'];
                $pricing = $membershipPricing['pricing'];
                
                // Create PawaPay payment page
                $paymentData = [
                    'amount' => $pricing['amount_with_fees'],
                    'currency' => $plan['currency'],
                    'correspondent' => $operator,
                    'payer' => [
                        'type' => 'MSISDN',
                        'address' => [
                            'value' => $phoneNumber
                        ]
                    ],
                    'customerTimestamp' => date('c'),
                    'statementDescription' => "Membership: " . $plan['name'],
                    'preAuthorisationCode' => uniqid('membership_'),
                    'metadata' => [
                        'plan_id' => $planId,
                        'plan_name' => $plan['name'],
                        'original_amount' => $plan['price'],
                        'fees' => $pricing['total_fee'],
                        'operator' => $operator
                    ]
                ];
                
                $paymentResult = $paymentService->createPaymentPage($paymentData);
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'payment_url' => $paymentResult['redirectUrl'],
                        'payment_id' => $paymentResult['depositId'],
                        'pricing' => $membershipPricing
                    ]
                ]);
                break;
                
            case 'initiate_product_payment':
                $productId = $_POST['product_id'];
                $operator = $_POST['operator'];
                $phoneNumber = $_POST['phone_number'];
                $quantity = intval($_POST['quantity'] ?? 1);
                
                $product = $testProducts[$productId] ?? null;
                if (!$product) {
                    throw new \Exception("Product not found");
                }
                
                $totalAmount = $product['price'] * $quantity;
                $feeCalculation = $zambiaFeeCalculator->calculateZambiaFees($totalAmount, $operator);
                
                $paymentData = [
                    'amount' => $feeCalculation['amount_with_fees'],
                    'currency' => 'ZMW',
                    'correspondent' => $operator,
                    'payer' => [
                        'type' => 'MSISDN',
                        'address' => [
                            'value' => $phoneNumber
                        ]
                    ],
                    'customerTimestamp' => date('c'),
                    'statementDescription' => "Product: " . $product['name'],
                    'preAuthorisationCode' => uniqid('product_'),
                    'metadata' => [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'quantity' => $quantity,
                        'unit_price' => $product['price'],
                        'original_total' => $totalAmount,
                        'fees' => $feeCalculation['total_fee'],
                        'operator' => $operator,
                        'vendor_commission' => $product['vendor_commission']
                    ]
                ];
                
                $paymentResult = $paymentService->createPaymentPage($paymentData);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'payment_url' => $paymentResult['redirectUrl'],
                        'payment_id' => $paymentResult['depositId'],
                        'pricing' => $feeCalculation,
                        'product_info' => [
                            'name' => $product['name'],
                            'quantity' => $quantity,
                            'unit_price' => $product['price'],
                            'total_before_fees' => $totalAmount
                        ]
                    ]
                ]);
                break;
                
            case 'initiate_wallet_deposit':
                $amount = floatval($_POST['amount']);
                $operator = $_POST['operator'];
                $phoneNumber = $_POST['phone_number'];
                
                $feeCalculation = $zambiaFeeCalculator->calculateZambiaFees($amount, $operator);
                
                $paymentData = [
                    'amount' => $feeCalculation['amount_with_fees'],
                    'currency' => 'ZMW',
                    'correspondent' => $operator,
                    'payer' => [
                        'type' => 'MSISDN',
                        'address' => [
                            'value' => $phoneNumber
                        ]
                    ],
                    'customerTimestamp' => date('c'),
                    'statementDescription' => "Wallet Deposit",
                    'preAuthorisationCode' => uniqid('wallet_'),
                    'metadata' => [
                        'type' => 'wallet_deposit',
                        'deposit_amount' => $amount,
                        'fees' => $feeCalculation['total_fee'],
                        'operator' => $operator
                    ]
                ];
                
                $paymentResult = $paymentService->createPaymentPage($paymentData);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'payment_url' => $paymentResult['redirectUrl'],
                        'payment_id' => $paymentResult['depositId'],
                        'pricing' => $feeCalculation
                    ]
                ]);
                break;
                
            default:
                throw new \Exception("Unknown action: " . $_POST['action']);
        }
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get current page
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyZuwa.com - PawaPay Testing Simulation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #2c5530 !important;
        }
        .btn-zambia {
            background-color: #2c5530;
            border-color: #2c5530;
            color: white;
        }
        .btn-zambia:hover {
            background-color: #1a3220;
            border-color: #1a3220;
        }
        .operator-card {
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
        }
        .operator-card:hover, .operator-card.selected {
            border-color: #2c5530;
            box-shadow: 0 0 10px rgba(44, 85, 48, 0.3);
        }
        .fee-breakdown {
            background-color: #f8f9fa;
            border-left: 4px solid #2c5530;
        }
        .testing-badge {
            background-color: #ff6b6b;
            color: white;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand" href="?page=dashboard">
                <i class="fas fa-store"></i> MyZuwa.com
                <span class="badge testing-badge ms-2">TESTING ENVIRONMENT</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link <?= $page === 'membership' ? 'active' : '' ?>" href="?page=membership">
                    <i class="fas fa-crown"></i> Membership Plans
                </a>
                <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>" href="?page=products">
                    <i class="fas fa-shopping-cart"></i> Products
                </a>
                <a class="nav-link <?= $page === 'wallet' ? 'active' : '' ?>" href="?page=wallet">
                    <i class="fas fa-wallet"></i> Wallet
                </a>
                <a class="nav-link <?= $page === 'fees' ? 'active' : '' ?>" href="?page=fees">
                    <i class="fas fa-calculator"></i> Fee Calculator
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php
        switch ($page) {
            case 'membership':
                include __DIR__ . '/../templates/membership.php';
                break;
            case 'products':
                include __DIR__ . '/../templates/products.php';
                break;
            case 'wallet':
                include __DIR__ . '/../templates/wallet.php';
                break;
            case 'fees':
                include __DIR__ . '/../templates/fees.php';
                break;
            default:
                include __DIR__ . '/../templates/dashboard.php';
        }
        ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0 text-muted">
                        <strong>PawaPay SDK Testing Environment</strong> | 
                        Simulating Modesy Marketplace with Zambia Mobile Money Integration
                    </p>
                    <small class="text-muted">
                        Environment: <strong><?= $_ENV['PAWAPAY_ENVIRONMENT'] ?? 'sandbox' ?></strong> | 
                        Fees Enabled: <strong><?= $_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] ?? 'true' ?></strong> |
                        Default Country: <strong><?= $_ENV['PAWAPAY_DEFAULT_COUNTRY'] ?? 'ZM' ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Global JavaScript for the testing interface
        
        // Fee calculation function
        function calculateFees(amount, operator, callback) {
            $.post('', {
                action: 'calculate_fees',
                amount: amount,
                operator: operator
            }, callback, 'json');
        }
        
        // Operator comparison
        function compareOperators(amount, callback) {
            $.post('', {
                action: 'compare_operators',
                amount: amount
            }, callback, 'json');
        }
        
        // Format currency
        function formatZMW(amount) {
            return 'ZMW ' + parseFloat(amount).toFixed(2);
        }
        
        // Show loading state
        function showLoading(element, text = 'Processing...') {
            $(element).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + text).prop('disabled', true);
        }
        
        // Hide loading state
        function hideLoading(element, originalText) {
            $(element).html(originalText).prop('disabled', false);
        }
        
        // Show alert
        function showAlert(type, message, container = '.container') {
            const alert = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            $(container).prepend(alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        // Initialize operator selection
        $(document).on('click', '.operator-card', function() {
            $('.operator-card').removeClass('selected');
            $(this).addClass('selected');
            $('input[name="operator"]').val($(this).data('operator'));
        });
        
        // Phone number formatting for Zambia
        $(document).on('input', 'input[name="phone_number"]', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.startsWith('260')) {
                value = value.substring(0, 12);
            } else if (value.startsWith('0')) {
                value = '260' + value.substring(1);
            } else if (!value.startsWith('260')) {
                value = '260' + value;
            }
            $(this).val(value);
        });
    </script>
</body>
</html>