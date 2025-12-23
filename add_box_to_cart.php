<?php
// File: add_box_to_cart.php (NEW FILE)
session_start();
header('Content-Type: application/json');

// Get the raw POST data
$json_str = file_get_contents('php://input');
$data = json_decode($json_str, true);

if (!$data || !isset($data['name']) || !isset($data['price']) || !isset($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit;
}

// Extract and sanitize data
$box_name = filter_var($data['name'], FILTER_SANITIZE_STRING);
// Extract the number from the price string "₹1,234.56"
$price_number = (float)filter_var($data['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$item_names = $data['items']; // This is an array of names

if (empty($box_name) || $price_number <= 0 || empty($item_names)) {
    echo json_encode(['success' => false, 'message' => 'Missing required box details.']);
    exit;
}

// --- ADD TO CART LOGIC ---
$cart = $_SESSION['cart'] ?? [];

// Create a unique key for this specific custom box configuration
$item_key = 'custom_box_' . md5(serialize($item_names));

$item_details = [
    'product_id'     => 'custom_box', // Special identifier for custom boxes
    'name'           => $box_name,
    'quantity'       => 1, // Custom boxes are added one at a time
    'price_per_unit' => $price_number,
    'options'        => ['Contents' => $item_names], // Store the selected items in the options
];

if (isset($cart[$item_key])) {
    // If the exact same box already exists, increase its quantity
    $cart[$item_key]['quantity']++;
} else {
    // Add it as a new item
    $cart[$item_key] = $item_details;
}

$_SESSION['cart'] = $cart;

// --- CALCULATE TOTAL CART COUNT & SEND RESPONSE ---
$total_count = 0;
foreach ($_SESSION['cart'] as $item) {
    if (is_array($item) && isset($item['quantity'])) {
        $total_count += $item['quantity'];
    }
}

session_write_close();

echo json_encode([
    'success'    => true,
    'message'    => 'Custom box added to cart!',
    'cart_count' => $total_count
]);
?>