<?php
session_start();
// config.php should be included if RAZORPAY constants are there
// require_once 'config.php'; 
require_once 'db_connect.php';
require 'vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$success = false;
$error = "Payment Failed";

// Check if Razorpay data is received
if (!empty($_POST['razorpay_payment_id']) && !empty($_POST['razorpay_signature'])) {
    // Assuming RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET are defined in a config file
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    try {
        $attributes = [
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];

        $api->utility->verifyPaymentSignature($attributes);
        $success = true;
    } catch(SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true) {
    $_SESSION['payment_verified'] = true;

    // --- MODIFIED PART: Echo a styled loader page ---
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verifying Payment...</title>
        <style>
            /* Basic page styling */
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background-color: #975ab7;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                text-align: center;
            }
            /* The container for our loader */
            .loader-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            /* The spinner animation */
            .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid rgba(255, 255, 255, 0.2);
                border-top-color: #975ab7; /* Accent color */
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            /* The text below the spinner */
            .loader-text {
                font-size: 1.1rem;
                color: rgba(255, 255, 255, 0.8);
                letter-spacing: 0.5px;
            }
            /* Keyframe animation for the spinning effect */
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
    </head>
    <body>
        <div class="loader-container">
            <div class="spinner"></div>
            <p class="loader-text">Payment successful. Finalizing your order...</p>
        </div>

        <form id="redirectForm" method="post" action="order_place.php" style="display:none;">';
    
    foreach ($_POST as $key => $value) {
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
    }
    
    echo '</form>
        <script type="text/javascript">
            // Automatically submit the form to the next page
            document.getElementById("redirectForm").submit();
        </script>
    </body>
    </html>';
    exit();

} else {
    header('Location: checkout.php?error=' . urlencode($error));
    exit();
}
?>