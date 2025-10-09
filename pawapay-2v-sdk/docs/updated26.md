Adding a New Payment Gateway
Important: This documentation has been prepared to guide you on how to add a payment system to our script. We have explained all the essential details you need to know about our code structure. However, integrating a payment system can be challenging, as almost every provider works differently and requires different implementation steps. If you are unable to integrate your payment system by following these instructions, or if you encounter errors during the process, we strongly recommend working with a developer. Please note that we do not provide support or customization services, and therefore we cannot assist you with adding your payment system or resolving related issues.
 
AI-Assisted Integration: Using AI models in such integrations can be very helpful. If you encounter difficulties while adding your payment system, we recommend using an AI tool (such as ChatGPT, Gemini, etc.). By providing this documentation to the AI tool and asking it to integrate your payment system based on these guidelines, it can guide you through the necessary steps.

Modesy supports various payment gateways to accept online payments. Payments made through these gateways are typically completed by retrieving the payment details from the gateway using the received payment ID. This process involves verifying whether the payment was successful, as well as confirming the payment amount and currency.

If the existing payment options are not available in your country, or if you want to add a local payment method, you can also integrate a payment option using a similar approach. Essentially, all you need to do is integrate your payment API and verify the incoming payment. Once the payment is verified, you can create a $transaction object and call the handlePayment() function. Modesy will then automatically handle order creation and other related processes.

To demonstrate how to add a payment gateway, we will explain how the existing Stripe payment option was integrated. You can follow similar steps to add your own payment method using the same approach.

1. Create a record for the Payment Gateway in the database

Open your database via phpMyAdmin and add a new record to the payment_gateways table using the "Insert" command.

Field	Description
id	Leave this field blank because the id is automatically generated.
name	Enter your payment system name.
Example: Stripe
name_key	Enter an unique name that does not contain special characters for your payment system.
Example: stripe
public_key	Enter the public_key value you created in your payment account.
Example: Adt9g4b-sz6NaQ_dsXGZQ76ah...
secret_key	Enter the secret_key value you created in your payment account.
Example: EL6tE3pLtJ13JebQI5n3Fy57xL7...
webhook_secret	Enter the webhook_secret value you received from your payment provider (if you will set a webhook for your payment gateway).
Example: whsec_9f8d7a6b5c4e3d2f1a0b...
environment	This is the payment mode option offered by your payment system. If you want to add test API keys for your payment system and perform payment tests, you must enter "sandbox" here. If you want to enter your live API keys and receive payments after tests, you must enter "production".
Example: sandbox
status	1 or 0. If you do not want the payment system to appear on your site, you need to enter 0. Otherwise, you need to enter 1.
Example: 1
logos	Our script displays logos for each payment system on the payment methods page. These logos are located in the "assets /img /payment" folder. For the payment system you add, you can add the logo names you want to show by putting commas between them. You can upload your own SVG logos to this folder.
Example: visa,mastercard,amex,discover
After adding the necessary information, you can click the "Go" button and finish the adding process.


2. Create a view to display the payment form

To allow users to see your payment option and make a payment through the form provided by the payment gateway, you need to create a view for your payment method. To do this, open the app/Views/cart/payment_methods/ folder in your application files and create the required view there. The view name must exactly match the name_key value you added to your database, and it should start with an underscore (_). For example, if your name_key is my_payment, your view file should be named _my_payment.php. For Stripe, this view was created as _stripe.php.

In this step, you need to read your payment gateway's documentation to understand how to initiate a payment and how to display the payment form, then add the necessary code to this view file. Since the code differs for each payment gateway, the implementation for your payment option will be different from Stripe's code. If you need to install a PHP library for your payment gateway and use it to initiate the payment or generate a redirect URL, we recommend handling all of these operations within this view file for simplicity.

In the view file you created, you can use the $checkout object to access the necessary parameters for the payment. This object contains information such as the total amount to be paid and the currency. You can use these parameter to create your payment:

$checkout->grand_total: The total amount that the user needs to pay.
$checkout->currency_code: The currency in which the payment should be made (e.g., USD, EUR).
$checkout->checkout_token: A unique token identifying the current checkout session, used to verify and process the payment. Sending this token to the payment gateway is required to confirm the payment after it has been completed.
 


3. Accept and verify the payment

After the user makes a payment with the form you created in the previous step, the payment result must be reported to your server through either a POST or GET request. Whether this request is sent as POST or GET depends on your payment gateway.

To handle this request, you need to add a function to the app/Controllers/CheckoutController.php file.

If your payment system also requires a webhook, you should add another function to process it. In other words, one function will run immediately when the user is redirected back to your site after making the payment, while the other will handle incoming requests from the webhook if you set one up.

For Stripe, we added two functions: completeStripePayment() and handleStripeWebhook().

completeStripePayment(): This function is executed when the user is redirected back to the site after making a payment. This function verifies the payment ID provided by the payment gateway (according to the gateway's documentation) and, if the payment was successfully completed, creates the order in the database.

handleStripeWebhook(): This function works in a similar way. However, the requests sent to this function are typically made using the POST method by a payment gateway. After a payment is made, the payment gateway sends a notification about whether the payment was successfully completed (if you have enabled webhooks in your payment gateway settings). When a webhook is received, the function first checks whether the order has already been added to the database. If it has not, the function saves the order to the database, thereby completing the payment process. If your payment gateway provides a payment ID immediately after the transaction (such as Stripe or PayPal), setting up a webhook may not be strictly necessary. However, it is still recommended, since webhooks ensure payment confirmation in cases where the redirect fails or the user's device encounters an issue.

Creating the necessary routes

After adding the necessary functions to the CheckoutController.php file, you need to define the routes that will allow access to these functions. To do this, open the app/Config/Routes.php file and add the required route definitions. You can refer to the following example when creating these routes:

$routes->get('checkout/complete-stripe-payment', 'CheckoutController::completeStripePayment');
$routes->post('payment/webhook/stripe', 'CheckoutController::handleStripeWebhook');
By reviewing this example, you can replace the name Stripe with the name of your own payment gateway and similarly add the required GET or POST routes. After creating these routes, you can access the functions using the following URLs:
https://yourdomain.com/checkout/complete-stripe-payment
https://yourdomain.com/payment/webhook/stripe (If you set up a webhook, this URL will be the URL to which the payment gateway will send the request.)

Warning! If you add a POST method route and a request is made to this route from an external source, the request will not work due to CSRF protection. To allow access to the route you created, open the app/Config/Filters.php file and add your route to the "csrf['except']" array.

Example:

'except' => [
'payment/webhook/stripe',
'payment/webhook/razorpay',
...
];
 

4. Understanding the Core Payment Handler: handlePayment()

After your controller function successfully verifies a payment with the payment gateway, the final step is to hand off the verified data to Modesy's core payment handler: handlePayment(). This function is the central engine that finalizes the checkout process. You do not need to modify this function; you only need to call it with the correct parameters.

What does handlePayment() do?

It orchestrates all the necessary actions to convert a successful payment into a completed order in the system. Its primary responsibilities include:

Creating the final order record in the database.
Saving the transaction details (payment ID, method, status, etc.).
Clearing the user's cart and checkout session.
Parameters

The function expects two main objects as arguments:

$checkout: This is the checkout object retrieved from the database using the checkout_token. Your function should have already fetched this object to verify the payment amount and currency.
$transaction: This is an object that you must create. It summarizes the results of the payment verification. It acts as a standardized data package that tells handlePayment() everything it needs to know about the transaction.
Crucial Point: You must construct the $transaction object accurately. It must contain the following properties:

Property	Type	Description
payment_id	string	The unique transaction ID returned by the payment gateway (e.g., Stripe's Payment Intent ID pi_... or a custom transaction hash).
status_text	string	The status text returned by the payment gateway. Examples: "succeeded", "paid", "completed". This is stored for logging and reference.
status	int	The internal status code for the payment. For any successful payment, this value must be 1.
payment_method	string	The name_key of your payment gateway as you defined it in the payment_gateways database table (e.g., "stripe", "my_payment").
Example of creating the transaction object:

// Assuming $paymentDetails contains verified data from your gateway
$transaction = (object)[
    'payment_id'     => $paymentDetails->transaction_id,
    'status_text'    => $paymentDetails->status_message,
    'status'         => 1, // Hardcode to 1 for success
    'payment_method' => 'my_payment', // Your gateway's name_key
];

// Call the handler
$result = $this->handlePayment($checkout, $transaction);
return $this->handleCheckoutResponse($result);
 

5. Template for Your Payment Completion Function

You can use the following code as a template for your own payment verification function in app/Controllers/CheckoutController.php. 


/**
 * Handles the return from "My Payment Gateway" after user payment.
 * This function is called when the user is redirected back to your site.
 */
public function completeMyPayment()
{
    // 1. Get payment details from the request (sent back by the gateway).
    // It's best practice to use the specific HTTP method (GET or POST).
    // Check your gateway's documentation and use the appropriate method below.

    // SCENARIO A: If the gateway returns data in the URL (e.g., ...?payment_id=123&token=abc)
    $paymentId = $this->request->getGet('payment_id');
    $checkoutToken = $this->request->getGet('checkout_token');

    // SCENARIO B: If the gateway sends data back via a POST request.
    // $paymentId = $this->request->getPost('payment_id');
    // $checkoutToken = $this->request->getPost('checkout_token');

    // 2. Perform basic validation.
    if (empty($paymentId) || empty($checkoutToken)) {
        log_message('error', 'MyPayment: A request was made to the completion URL without required parameters.');
        return $this->paymentErrorResponse();
    }

    // 3. Load your payment gateway's configuration from the database.
    // Replace 'my_payment' with your gateway's name_key.
    $config = getPaymentGateway('my_payment');
    if (empty($config)) {
        log_message('error', 'MyPayment: Payment gateway configuration not found.');
        return $this->paymentErrorResponse();
    }

    // 4. Find the local checkout data using the token.
    $checkout = $this->checkoutModel->getCheckoutByToken($checkoutToken);
    if (empty($checkout)) {
        log_message('error', "MyPayment: Checkout not found for token: {$checkoutToken}");
        return $this->paymentErrorResponse();
    }

    // 5. Check if the order has already been processed (e.g., by a webhook).
    if ($checkout->status === 'paid') {
        // If already paid, just redirect the user to their success page.
        $redirectUrl = $this->checkoutModel->createOrderRedirectUrl($checkout);
        return redirect()->to($redirectUrl ?? base_url());
    }

    try {
        // ...
        // Dummy verification result for template purposes.
        $paymentDetails = (object)[
            'is_successful'  => true,
            // IMPORTANT: This value must match the format returned by YOUR gateway.
            // Scenario A: Gateway returns amount in subunit (10.99 becomes 1099)
            'amount_paid'    => $checkout->grand_total * 100,
            // Scenario B: Gateway returns amount as a decimal (10.99)
            // 'amount_paid'    => $checkout->grand_total,
            'currency'       => $checkout->currency_code,
            'transaction_id' => $paymentId,
            'status_message' => 'completed'
        ];

        if ($paymentDetails->is_successful) {
            
            // 8. Security Check: Verify that the paid amount and currency match the checkout data.
            // THIS IS A CRITICAL STEP. CHOOSE THE SCENARIO THAT FITS YOUR GATEWAY.

            // --- SCENARIO A: Your gateway returns the amount in subunits (e.g., 1099 for $10.99) ---
            // This is the most common and recommended approach.
            $paidAmountInSubunit = (int)$paymentDetails->amount_paid;
            $expectedAmountInSubunit = (int)round($checkout->grand_total * 100);

            if ($paidAmountInSubunit < $expectedAmountInSubunit || strtolower($paymentDetails->currency) != strtolower($checkout->currency_code)) {
                $logMessage = "MyPayment: Amount/currency mismatch for token: {$checkoutToken}.";
                throw new \Exception($logMessage);
            }
            
            /*
            // --- SCENARIO B: Your gateway returns a decimal amount (e.g., 10.99) ---
            // Use this if your gateway does not use subunits.
            // We convert both to subunits here to avoid floating point comparison issues.
            $paidAmountInSubunit = (int)round((float)$paymentDetails->amount_paid * 100);
            $expectedAmountInSubunit = (int)round($checkout->grand_total * 100);

            if ($paidAmountInSubunit < $expectedAmountInSubunit || strtolower($paymentDetails->currency) != strtolower($checkout->currency_code)) {
                $logMessage = "MyPayment: Amount/currency mismatch for token: {$checkoutToken}.";
                throw new \Exception($logMessage);
            }
            */


            // 9. Create the standardized transaction object.
            $transaction = (object)[
                'payment_id'     => $paymentDetails->transaction_id,
                'status_text'    => $paymentDetails->status_message,
                'status'         => 1, // 1 means success
                'payment_method' => 'my_payment', // Your gateway's name_key
            ];

            // 10. Pass the data to the core handler to finalize the order.
            $result = $this->handlePayment($checkout, $transaction);
            return $this->handleCheckoutResponse($result);

        } else {
            // The payment gateway reported that the payment was not successful.
            throw new \Exception("Payment verification failed or payment was not completed.");
        }

    } catch (\Exception $e) {
        // If anything goes wrong, log the error and show a generic payment error page.
        log_message('error', "MyPayment processing failed: " . $e->getMessage());
        return $this->paymentErrorResponse(trans("msg_payment_error"));
    }
}
 

6. Detailed explanations of the Stripe payment functions

Here you can find the Stripe payment code written according to the template above, along with detailed explanations. Since both the completeStripePayment() and handleStripeWebhook() functions use similar validation logic, a shared processStripeOrder() function was created to avoid code duplication.
Depending on the complexity of your payment integration, you may choose to implement a similar shared function, or alternatively, handle all processes directly within a single function.

/**
 * Verify the Stripe payment and finalize the order.
 *
 * @method GET
 * @return \CodeIgniter\HTTP\RedirectResponse|void
 */
public function completeStripePayment()
{
    // Get the "session_id" from the request URL (sent back from Stripe after checkout).
    $sessionId = $this->request->getGet('session_id');

    // If no session_id is provided, log an error and show a payment error response.
    if (empty($sessionId)) {
        log_message('error', 'Stripe: A request was made to the completion URL without a session_id.');
        return $this->paymentErrorResponse();
    }

    // Load Stripe configuration from the system (API keys, etc.).
    $config = getPaymentGateway('stripe');
    if (empty($config)) {
        log_message('error', 'Stripe: Payment gateway configuration not found.');
        return $this->paymentErrorResponse();
    }

    try {
        // Create a Stripe instance with the configuration.
        $stripe = new Stripe($config, $this->baseVars->appName);

        // Verify the payment using the session_id (contact Stripe servers).
        $session = $stripe->verifyPayment($sessionId);

        if ($session) {
            // If verification works, process the order with the session data.
            $result = $this->processStripeOrder($session, false);

            // Handle the checkout response (redirect to success/failure page).
            return $this->handleCheckoutResponse($result);
        } else {
            // If session verification failed, throw an error.
            throw new \Exception("Stripe payment verification failed or payment was not completed.");
        }

    } catch (\Exception $e) {
        // If something goes wrong, log the error and show a payment error page.
        log_message('error', "Stripe payment processing failed after user return: " . $e->getMessage());
        return $this->paymentErrorResponse(trans("msg_payment_error"));
    }
}


/**
 * Handles incoming webhook notifications from Stripe.
 *
 * @method POST
 * @return \CodeIgniter\HTTP\ResponseInterface The HTTP response object.
 */
public function handleStripeWebhook()
{
    try {
        // Get Stripe config (API keys, status, etc.)
        $config = getPaymentGateway('stripe');
        if (empty($config) || !$config->status) {
            throw new \Exception('Stripe webhook failed: Configuration missing or gateway disabled.');
        }

        // Create a Stripe instance.
        $stripe = new Stripe($config, $this->baseVars->appName);

        // Handle webhook payload (Stripe sends JSON with payment details).
        $session = $stripe->handleWebhook();

        if (!empty($session)) {
            // Try to process the order with the received session data.
            if (!$this->processStripeOrder($session, true)) {
                // If order processing fails, log a CRITICAL error (manual check needed).
                $paymentIntentId = $session->payment_intent ?? ($session->id ?? 'N/A');
                log_message('critical', "Stripe webhook: Order processing FAILED");
            }
        }

        // Always return 200 OK to Stripe, so it knows webhook was received.
        return $this->response->setStatusCode(200, 'OK');

    } catch (\Throwable $e) {
        // If any error happens, log it and respond with 500 error.
        log_message('error', 'Stripe Webhook Controller Exception: ' . $e->getMessage());
        return $this->response->setStatusCode(500, 'Internal Server Error');
    }
}


/**
 * Processes Stripe order
 *
 * @param object $session The Stripe Checkout Session object.
 * @param bool $isWebhook Indicates if the call comes from a server notification.
 */
private function processStripeOrder(object $session, bool $isWebhook = false)
{
    // Get the checkout token stored in session metadata (used to find local checkout data).
    $token = $session->metadata->checkout_token ?? null;
    if (empty($token)) {
        throw new \Exception("Stripe: Empty checkout token.");
    }

    // Look up checkout details in database using the token.
    $checkout = $this->checkoutModel->getCheckoutByToken($token);
    if (empty($checkout)) {
        throw new \Exception("Stripe: Checkout not found for token: {$token}");
    }

    // If checkout is already marked as paid:
    if ($checkout->status === 'paid') {
        // If webhook triggered it, just return success silently.
        if ($isWebhook) {
            return true;
        }
        // If user just returned, redirect them to their order success page.
        return [
            'status' => 1,
            'redirectUrl' => $this->checkoutModel->createOrderRedirectUrl($checkout)
        ];
    }

    // Verify the amount and currency from Stripe against local checkout data.
    $paidAmountInSubunit = $session->amount_total; // Stripe gives amount in cents (e.g., 1099 = $10.99)
    $paidCurrency = $session->currency;

    $expectedAmount = numToDecimal($checkout->grand_total);
    $expectedAmountInSubunit = (int)round($checkout->grand_total * 100);
    $expectedCurrency = $checkout->currency_code;

    // If mismatch, throw error (prevents fraud).
    if ($paidAmountInSubunit != $expectedAmountInSubunit || strtolower($paidCurrency) != strtolower($expectedCurrency)) {
        $logMessage = "Stripe: Amount/currency mismatch for token: {$token}.";
        throw new \Exception($logMessage);
    }

    // If everything matches, create a transaction object to record payment.
    $transaction = (object)[
        'payment_id' => $session->payment_intent ?? $session->id, // Unique Stripe payment ID
        'status_text' => $session->payment_status, // e.g., "paid"
        'status' => 1, // success
        'payment_method' => 'stripe',
    ];

    // Pass the transaction to final payment handler (saves order, marks as paid, etc.)
    return $this->handlePayment($checkout, $transaction, $isWebhook);
}
    
About Updates: We are constantly making new updates for our script. If we upload a major update, you will need to update all your files to update your site. For this reason, you may need to migrate the payment system you added to the new version.