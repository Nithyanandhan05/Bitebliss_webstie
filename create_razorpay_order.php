<?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php';
use Razorpay\Api\Api;

header('Content-Type: application/json');

// Basic security checks
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session.']);
    exit;
}

// Recalculate subtotal from session cart to ensure integrity
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price_per_unit'] * $item['quantity'];
}

// Get delivery charge from the client-side calculation
$input = json_decode(file_get_contents('php://input'), true);
$delivery_charge = (float)($input['delivery_charge'] ?? 50.00);

$tax_amount = $subtotal * 0.05;
$total = $subtotal + $delivery_charge + $tax_amount;

// ** START OF THE FIX **
// To prevent floating point errors, round the total to the nearest whole number
// after converting to paise, and then cast it to an integer.
$amount_in_paise = (int)round($total * 100);
// ** END OF THE FIX **

try {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $orderData = [
        'receipt'         => uniqid(),
        'amount'          => $amount_in_paise, // Use the corrected integer amount
        'currency'        => 'INR',
        'payment_capture' => 1
    ];
    $razorpayOrder = $api->order->create($orderData);

    // Store the original total in rupees for your own database records
    $_SESSION['order_total_amount'] = $total;
    $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
    
    // Send the success response back to the client with the corrected integer amount
    echo json_encode(['status' => 'success', 'order_id' => $razorpayOrder['id'], 'amount' => $amount_in_paise]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Razorpay Error: ' . $e->getMessage()]);
}
?>