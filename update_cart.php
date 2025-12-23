<?php
session_start();
require_once 'db_connect.php'; // Needed for database access if recalculating totals

header('Content-Type: application/json'); // Set header to return JSON data
$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'remove' && isset($_POST['item_key'])) {
        $item_key = $_POST['item_key'];

        if (isset($_SESSION['cart'][$item_key])) {
            // Remove the item from the session cart
            unset($_SESSION['cart'][$item_key]);

            // Recalculate the subtotal and total after removal
            $subtotal = 0;
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    if (is_array($item) && isset($item['price_per_unit']) && isset($item['quantity'])) {
                         $subtotal += $item['price_per_unit'] * $item['quantity'];
                    }
                }
            }

            $delivery_charge = 50.00;
            $total = $subtotal + $delivery_charge;
            
            // If the cart is now empty, ensure total is just the delivery charge, or 0 if you prefer.
            if (empty($_SESSION['cart'])) {
                $total = 0; // Or keep it at 50 if delivery is always an option
                $delivery_charge = 0;
            }


            $response = [
                'status' => 'success',
                'message' => 'Item removed.',
                'subtotal' => number_format($subtotal, 2),
                'total' => number_format($total, 2),
                'cart_empty' => empty($_SESSION['cart'])
            ];
        } else {
            $response['message'] = 'Item key not found in cart.';
        }
    }
}

echo json_encode($response);
exit();
?>