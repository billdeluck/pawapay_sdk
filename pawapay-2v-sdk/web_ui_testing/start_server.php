<?php
/**
 * ============================================================================
 * PAWAPAY TESTING WEB SERVER STARTER
 * ============================================================================
 * 
 * This script starts a PHP built-in web server for testing the PawaPay SDK
 * with the simulated Modesy marketplace interface.
 * 
 * @version 2.0.0
 * @since 2024-10-17
 * ============================================================================
 */

// Configuration
$host = '0.0.0.0';
$port = 8080;
$documentRoot = __DIR__ . '/public';

// Color codes for terminal output
function colorOutput($text, $color = 'white') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

// Display startup information
echo colorOutput("\n" . str_repeat("=", 80), 'cyan') . "\n";
echo colorOutput("🚀 PAWAPAY SDK TESTING ENVIRONMENT", 'green') . "\n";
echo colorOutput(str_repeat("=", 80), 'cyan') . "\n\n";

echo colorOutput("📋 Starting PawaPay Testing Web Server...", 'yellow') . "\n\n";

// Check if port is available
$socket = @fsockopen($host, $port, $errno, $errstr, 1);
if ($socket) {
    fclose($socket);
    echo colorOutput("❌ Error: Port {$port} is already in use!", 'red') . "\n";
    echo colorOutput("Please choose a different port or stop the existing service.", 'yellow') . "\n\n";
    exit(1);
}

// Check document root
if (!is_dir($documentRoot)) {
    echo colorOutput("❌ Error: Document root directory not found: {$documentRoot}", 'red') . "\n\n";
    exit(1);
}

// Display server information
echo colorOutput("📊 Server Configuration:", 'blue') . "\n";
echo "   Host: {$host}\n";
echo "   Port: {$port}\n";
echo "   Document Root: {$documentRoot}\n";
echo "   Environment: " . ($_ENV['PAWAPAY_ENVIRONMENT'] ?? 'sandbox') . "\n";
echo "   Fee Calculation: " . ($_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] ?? 'true') . "\n\n";

echo colorOutput("🌐 Access URLs:", 'green') . "\n";
echo "   Main Interface: " . colorOutput("http://localhost:{$port}/", 'cyan') . "\n";
echo "   Webhook Handler: " . colorOutput("http://localhost:{$port}/webhook.php", 'cyan') . "\n\n";

echo colorOutput("📱 Testing Features Available:", 'magenta') . "\n";
echo "   ✅ Membership Plan Payments\n";
echo "   ✅ Product Purchase Testing\n";
echo "   ✅ Wallet Deposit Functionality\n";
echo "   ✅ Real-time Fee Calculations\n";
echo "   ✅ Zambia Mobile Money Integration\n";
echo "   ✅ Real PawaPay API Calls\n\n";

echo colorOutput("💡 Testing Tips:", 'yellow') . "\n";
echo "   • Use provided test phone numbers for different scenarios\n";
echo "   • Fees are calculated based on real Zambian operator structures\n";
echo "   • All payments use actual PawaPay sandbox environment\n";
echo "   • Check webhook logs in web_ui_testing/logs/ directory\n\n";

echo colorOutput("⚠️  Important Notes:", 'red') . "\n";
echo "   • This is a TESTING environment only\n";
echo "   • Do not use real payment credentials\n";
echo "   • All transactions are in sandbox mode\n";
echo "   • Press Ctrl+C to stop the server\n\n";

echo colorOutput(str_repeat("-", 80), 'cyan') . "\n";

// Start the server
$command = "php -S {$host}:{$port} -t {$documentRoot}";

echo colorOutput("🔥 Starting server with command: {$command}", 'green') . "\n\n";

// Change to the document root directory
chdir($documentRoot);

// Execute the server command
passthru($command);
?>