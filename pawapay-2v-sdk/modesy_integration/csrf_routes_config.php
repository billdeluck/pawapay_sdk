<?php

/**
 * ============================================================================
 * MODESY MARKETPLACE - PAWAPAY CSRF BYPASS AND ROUTES CONFIGURATION
 * ============================================================================
 * 
 * This file provides configuration templates for CSRF bypass and routing
 * setup required for PawaPay webhook endpoints and redirect callbacks.
 * 
 * IMPORTANT: This file contains configuration examples and should be adapted
 * to your specific Modesy installation structure and security requirements.
 * 
 * Features:
 * - CSRF bypass configuration for PawaPay webhooks
 * - Route definitions for payment processing endpoints
 * - Middleware configuration for security and logging
 * - URL pattern matching for dynamic routing
 * 
 * @author PawaPay SDK Integration Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

/**
 * ============================================================================
 * SECTION 1: CSRF BYPASS CONFIGURATION
 * ============================================================================
 * 
 * Add these configurations to your Modesy application to bypass CSRF
 * protection for PawaPay webhook endpoints and redirect callbacks.
 */

/**
 * CSRF BYPASS ROUTES - Add to your application's CSRF middleware configuration
 * 
 * For Laravel-based Modesy installations, add to App\Http\Middleware\VerifyCsrfToken.php
 */
$csrfExcludedRoutes = [
    // PawaPay webhook endpoints
    'pawapay/webhook',
    'pawapay/webhook/*',
    'payment/pawapay/webhook',
    'payment/pawapay/webhook/*',
    
    // PawaPay redirect callbacks  
    'pawapay/callback',
    'pawapay/callback/*',
    'payment/pawapay/callback',
    'payment/pawapay/callback/*',
    
    // PawaPay status check endpoints
    'pawapay/status/*',
    'payment/pawapay/status/*',
    
    // API endpoints for mobile integration
    'api/pawapay/*',
    'api/payment/pawapay/*'
];

/**
 * Laravel VerifyCsrfToken Middleware Example:
 * 
 * protected $except = [
 *     'pawapay/webhook',
 *     'pawapay/webhook/*',
 *     'payment/pawapay/webhook',
 *     'payment/pawapay/webhook/*',
 *     'pawapay/callback',
 *     'pawapay/callback/*',
 *     'payment/pawapay/callback',
 *     'payment/pawapay/callback/*',
 *     'pawapay/status/*',
 *     'payment/pawapay/status/*',
 *     'api/pawapay/*',
 *     'api/payment/pawapay/*'
 * ];
 */

/**
 * ============================================================================
 * SECTION 2: ROUTE DEFINITIONS
 * ============================================================================
 * 
 * Route definitions for PawaPay integration endpoints
 */

/**
 * Web Routes (add to web.php or equivalent)
 */
$webRoutes = [
    // Payment initiation and redirect handling
    [
        'method' => 'POST',
        'uri' => 'checkout/pawapay/initiate',
        'controller' => 'CheckoutController@initiatePawaPayPayment',
        'name' => 'pawapay.initiate',
        'middleware' => ['web', 'auth']
    ],
    
    // Payment completion callback
    [
        'method' => 'GET',
        'uri' => 'pawapay/callback/{transactionId}',
        'controller' => 'CheckoutController@completePawaPayPayment',
        'name' => 'pawapay.callback',
        'middleware' => ['web']
    ],
    
    // Payment status check
    [
        'method' => 'GET',
        'uri' => 'pawapay/status/{transactionId}',
        'controller' => 'CheckoutController@checkPawaPayStatus',
        'name' => 'pawapay.status',
        'middleware' => ['web', 'auth']
    ],
    
    // Alternative callback URLs for different scenarios
    [
        'method' => 'GET',
        'uri' => 'payment/pawapay/return/{transactionId}',
        'controller' => 'CheckoutController@completePawaPayPayment',
        'name' => 'pawapay.return',
        'middleware' => ['web']
    ]
];

/**
 * API Routes (add to api.php or equivalent)
 */
$apiRoutes = [
    // Webhook endpoint for payment notifications
    [
        'method' => 'POST',
        'uri' => 'pawapay/webhook',
        'controller' => 'CheckoutController@handlePawaPayWebhook',
        'name' => 'pawapay.webhook',
        'middleware' => ['api', 'pawapay.webhook.verify']
    ],
    
    // Alternative webhook endpoint
    [
        'method' => 'POST',
        'uri' => 'payment/pawapay/webhook',
        'controller' => 'CheckoutController@handlePawaPayWebhook',
        'name' => 'pawapay.webhook.alt',
        'middleware' => ['api', 'pawapay.webhook.verify']
    ],
    
    // API status endpoint for AJAX calls
    [
        'method' => 'GET',
        'uri' => 'pawapay/transaction/{transactionId}/status',
        'controller' => 'CheckoutController@checkPawaPayStatus',
        'name' => 'pawapay.api.status',
        'middleware' => ['api', 'auth:sanctum']
    ]
];

/**
 * ============================================================================
 * SECTION 3: LARAVEL ROUTE FILE EXAMPLES
 * ============================================================================
 */

/**
 * Example web.php content for Laravel-based Modesy:
 * 
 * Route::group(['middleware' => ['web']], function() {
 *     // PawaPay payment initiation
 *     Route::post('checkout/pawapay/initiate', 'CheckoutController@initiatePawaPayPayment')
 *          ->name('pawapay.initiate')
 *          ->middleware('auth');
 *     
 *     // PawaPay callback handling
 *     Route::get('pawapay/callback/{transactionId}', 'CheckoutController@completePawaPayPayment')
 *          ->name('pawapay.callback');
 *     
 *     Route::get('payment/pawapay/return/{transactionId}', 'CheckoutController@completePawaPayPayment')
 *          ->name('pawapay.return');
 *     
 *     // Status checking
 *     Route::get('pawapay/status/{transactionId}', 'CheckoutController@checkPawaPayStatus')
 *          ->name('pawapay.status')
 *          ->middleware('auth');
 * });
 */

/**
 * Example api.php content for Laravel-based Modesy:
 * 
 * Route::group(['middleware' => ['api']], function() {
 *     // Webhook endpoints (CSRF exempt)
 *     Route::post('pawapay/webhook', 'CheckoutController@handlePawaPayWebhook')
 *          ->name('pawapay.webhook');
 *     
 *     Route::post('payment/pawapay/webhook', 'CheckoutController@handlePawaPayWebhook')
 *          ->name('pawapay.webhook.alt');
 *     
 *     // API status endpoint
 *     Route::get('pawapay/transaction/{transactionId}/status', 'CheckoutController@checkPawaPayStatus')
 *          ->name('pawapay.api.status')
 *          ->middleware('auth:sanctum');
 * });
 */

/**
 * ============================================================================
 * SECTION 4: MIDDLEWARE CONFIGURATION
 * ============================================================================
 */

/**
 * Custom PawaPay Webhook Verification Middleware
 * 
 * Create this middleware to verify webhook signatures:
 * php artisan make:middleware PawaPayWebhookVerify
 */
$webhookMiddlewareTemplate = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PawaPay\WebhookHandler;

class PawaPayWebhookVerify
{
    public function handle(Request $request, Closure $next)
    {
        // Get webhook signature from headers
        $signature = $request->header("X-PawaPay-Signature") ?? 
                    $request->header("pawapay-signature");
        
        if (!$signature) {
            return response()->json(["error" => "Missing webhook signature"], 401);
        }
        
        try {
            // Verify webhook signature using PawaPay SDK
            $webhookHandler = new WebhookHandler();
            $isValid = $webhookHandler->verifySignature(
                $request->getContent(),
                $signature,
                config("services.pawapay.webhook_secret")
            );
            
            if (!$isValid) {
                return response()->json(["error" => "Invalid webhook signature"], 401);
            }
            
            // Add verified flag to request
            $request->attributes->set("webhook_verified", true);
            
        } catch (\Exception $e) {
            return response()->json(["error" => "Webhook verification failed"], 500);
        }
        
        return $next($request);
    }
}';

/**
 * ============================================================================
 * SECTION 5: CODEIGNITER 4 ROUTING (For Modesy using CodeIgniter)
 * ============================================================================
 */

/**
 * CodeIgniter 4 Routes Configuration (Config/Routes.php)
 */
$codeIgniterRoutes = '
// PawaPay Integration Routes
$routes->group("pawapay", ["namespace" => "App\Controllers"], function($routes) {
    // Payment initiation
    $routes->post("initiate", "Checkout::initiatePawaPayPayment");
    
    // Callback handling  
    $routes->get("callback/(:any)", "Checkout::completePawaPayPayment/$1");
    $routes->get("return/(:any)", "Checkout::completePawaPayPayment/$1");
    
    // Status checking
    $routes->get("status/(:any)", "Checkout::checkPawaPayStatus/$1");
    
    // Webhook endpoint (CSRF disabled)
    $routes->post("webhook", "Checkout::handlePawaPayWebhook", ["filter" => "cors"]);
});

// Alternative payment routes
$routes->group("payment/pawapay", ["namespace" => "App\Controllers"], function($routes) {
    $routes->post("webhook", "Checkout::handlePawaPayWebhook", ["filter" => "cors"]);
    $routes->get("return/(:any)", "Checkout::completePawaPayPayment/$1");
});

// API routes for AJAX calls
$routes->group("api/pawapay", ["namespace" => "App\Controllers"], function($routes) {
    $routes->get("transaction/(:any)/status", "Checkout::checkPawaPayStatus/$1");
});
';

/**
 * CodeIgniter 4 CSRF Configuration (Config/Security.php)
 */
$codeIgniterCSRFConfig = '
// CSRF excluded URIs for PawaPay webhooks
public $csrfExcludeURIs = [
    "pawapay/webhook",
    "payment/pawapay/webhook", 
    "api/pawapay/webhook"
];
';

/**
 * ============================================================================
 * SECTION 6: CUSTOM PHP ROUTING (For custom Modesy installations)
 * ============================================================================
 */

/**
 * Custom PHP Router Configuration
 */
function setupPawaPayRoutes($router) {
    // Payment initiation
    $router->post('/checkout/pawapay/initiate', function() {
        require_once 'controllers/CheckoutController.php';
        $controller = new CheckoutController();
        return $controller->initiatePawaPayPayment();
    });
    
    // Callback handling
    $router->get('/pawapay/callback/{transactionId}', function($transactionId) {
        require_once 'controllers/CheckoutController.php';
        $controller = new CheckoutController();
        return $controller->completePawaPayPayment($transactionId);
    });
    
    // Status checking
    $router->get('/pawapay/status/{transactionId}', function($transactionId) {
        require_once 'controllers/CheckoutController.php';
        $controller = new CheckoutController();
        return $controller->checkPawaPayStatus($transactionId);
    });
    
    // Webhook handling (CSRF bypass)
    $router->post('/pawapay/webhook', function() {
        // Skip CSRF for webhooks
        $_SESSION['csrf_exempt'] = true;
        
        require_once 'controllers/CheckoutController.php';
        $controller = new CheckoutController();
        return $controller->handlePawaPayWebhook();
    });
}

/**
 * ============================================================================
 * SECTION 7: HTACCESS CONFIGURATION
 * ============================================================================
 */

/**
 * Apache .htaccess rules for PawaPay URLs
 */
$htaccessRules = '
# PawaPay Integration URL Rewriting
RewriteEngine On

# PawaPay webhook endpoints
RewriteRule ^pawapay/webhook/?$ payment/pawapay_webhook.php [L,QSA]
RewriteRule ^payment/pawapay/webhook/?$ payment/pawapay_webhook.php [L,QSA]

# PawaPay callback URLs
RewriteRule ^pawapay/callback/([^/]+)/?$ payment/pawapay_callback.php?transaction_id=$1 [L,QSA]
RewriteRule ^payment/pawapay/return/([^/]+)/?$ payment/pawapay_callback.php?transaction_id=$1 [L,QSA]

# PawaPay status URLs
RewriteRule ^pawapay/status/([^/]+)/?$ payment/pawapay_status.php?transaction_id=$1 [L,QSA]

# API endpoints
RewriteRule ^api/pawapay/transaction/([^/]+)/status/?$ api/pawapay_status.php?transaction_id=$1 [L,QSA]
';

/**
 * ============================================================================
 * SECTION 8: NGINX CONFIGURATION
 * ============================================================================
 */

/**
 * Nginx server configuration for PawaPay URLs
 */
$nginxConfig = '
# PawaPay Integration Location Blocks
location ~ ^/pawapay/webhook/?$ {
    try_files $uri /payment/pawapay_webhook.php?$query_string;
}

location ~ ^/payment/pawapay/webhook/?$ {
    try_files $uri /payment/pawapay_webhook.php?$query_string;
}

location ~ ^/pawapay/callback/(.+)$ {
    try_files $uri /payment/pawapay_callback.php?transaction_id=$1&$query_string;
}

location ~ ^/payment/pawapay/return/(.+)$ {
    try_files $uri /payment/pawapay_callback.php?transaction_id=$1&$query_string;
}

location ~ ^/pawapay/status/(.+)$ {
    try_files $uri /payment/pawapay_status.php?transaction_id=$1&$query_string;
}

location ~ ^/api/pawapay/transaction/(.+)/status$ {
    try_files $uri /api/pawapay_status.php?transaction_id=$1&$query_string;
}
';

/**
 * ============================================================================
 * SECTION 9: CONFIGURATION VALIDATION FUNCTIONS
 * ============================================================================
 */

/**
 * Validate CSRF configuration setup
 * 
 * @return array Validation results
 */
function validateCSRFConfiguration(): array {
    $results = [
        'valid' => true,
        'checks' => [],
        'errors' => []
    ];
    
    // Check if CSRF is properly configured for webhooks
    $testUrl = '/pawapay/webhook';
    
    // This would need to be adapted based on your framework
    // Example for Laravel:
    if (function_exists('csrf_token')) {
        $results['checks']['csrf_middleware'] = 'Laravel CSRF detected';
        
        // Check if route is in excluded list
        // This would need framework-specific implementation
    }
    
    return $results;
}

/**
 * Test route accessibility
 * 
 * @param array $routes Routes to test
 * @return array Test results
 */
function testRouteAccessibility(array $routes): array {
    $results = [];
    
    foreach ($routes as $route) {
        $url = $route['uri'];
        $method = strtoupper($route['method']);
        
        $results[$url] = [
            'method' => $method,
            'accessible' => false,
            'error' => null
        ];
        
        // This would need actual HTTP testing implementation
        // For now, just mark as needs testing
        $results[$url]['needs_testing'] = true;
    }
    
    return $results;
}

/**
 * ============================================================================
 * INSTALLATION INSTRUCTIONS
 * ============================================================================
 */

return [
    'csrf_excluded_routes' => $csrfExcludedRoutes,
    'web_routes' => $webRoutes,
    'api_routes' => $apiRoutes,
    'webhook_middleware_template' => $webhookMiddlewareTemplate,
    'codeigniter_routes' => $codeIgniterRoutes,
    'codeigniter_csrf_config' => $codeIgniterCSRFConfig,
    'htaccess_rules' => $htaccessRules,
    'nginx_config' => $nginxConfig,
    
    'installation_steps' => [
        '1. Choose your framework configuration from the sections above',
        '2. Add CSRF exclusions for PawaPay webhook URLs', 
        '3. Configure routing based on your Modesy installation type',
        '4. Set up webhook signature verification middleware',
        '5. Update web server configuration if using custom routing',
        '6. Test webhook endpoints are accessible without CSRF errors',
        '7. Verify callback URLs work correctly',
        '8. Test API endpoints for status checking'
    ]
];';