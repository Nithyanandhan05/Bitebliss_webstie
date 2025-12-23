<?php
// File: add_to_cart.php (Modified and Corrected)
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

function send_json_error($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_error('Invalid request method.');
    }

    // --- Check if the product is a custom cake ---
    if (isset($_POST['product_type']) && $_POST['product_type'] === 'custom_cake') {
        
        // --- Handle Custom Cake Addition ---
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $flavor_id = filter_input(INPUT_POST, 'flavor', FILTER_VALIDATE_INT);
        $size_id = filter_input(INPUT_POST, 'size', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

        if (!$category_id || !$flavor_id || !$size_id || !$quantity || $quantity < 1) {
            send_json_error('Missing or invalid cake details.');
        }

        // --- Server-Side Price Verification for Security ---
        $size_stmt = $conn->prepare("SELECT base_price, name FROM cake_sizes WHERE id = ? AND is_available = 1");
        $size_stmt->bind_param("i", $size_id);
        $size_stmt->execute();
        $size_result = $size_stmt->get_result()->fetch_assoc();
        $size_stmt->close();

        $flavor_stmt = $conn->prepare("SELECT additional_price, name FROM cake_flavors WHERE id = ? AND is_available = 1");
        $flavor_stmt->bind_param("i", $flavor_id);
        $flavor_stmt->execute();
        $flavor_result = $flavor_stmt->get_result()->fetch_assoc();
        $flavor_stmt->close();
        
        $category_stmt = $conn->prepare("SELECT name, image_url FROM categories WHERE id = ?");
        $category_stmt->bind_param("i", $category_id);
        $category_stmt->execute();
        $category_result = $category_stmt->get_result()->fetch_assoc();
        $category_stmt->close();

        if (!$size_result || !$flavor_result || !$category_result) {
            send_json_error('Invalid cake options selected. Please try again.');
        }

        // Calculate price on the server to prevent manipulation
        $price_per_unit = (float)$size_result['base_price'] + (float)$flavor_result['additional_price'];

        // --- Prepare Cake Item for Cart ---
        $options_data = [
            'Flavor' => htmlspecialchars($flavor_result['name']),
            'Size' => htmlspecialchars($size_result['name']),
        ];
        if (!empty($message)) {
            $options_data['Message'] = $message;
        }

        // Create a unique key for each combination of cake options
        $item_key = 'cake_' . $category_id . '_' . $flavor_id . '_' . $size_id . '_' . md5($message);
        
        $cart = $_SESSION['cart'] ?? [];

        if (isset($cart[$item_key])) {
            $cart[$item_key]['quantity'] += $quantity;
        } else {
            $cart[$item_key] = [
                'product_id'     => $item_key, // Unique identifier for this custom cake
                'name'           => 'Custom ' . htmlspecialchars($category_result['name']),
                'quantity'       => $quantity,
                'price_per_unit' => $price_per_unit,
                'image_url'      => 'img/' . $category_result['image_url'],
                'options'        => $options_data,
            ];
        }
        $_SESSION['cart'] = $cart;

    } else {
        
        // --- Handle Standard Product Addition (Your Original Logic) ---
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $pieces = isset($_POST['pieces']) ? (int)$_POST['pieces'] : 1;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        if ($product_id <= 0 || $quantity <= 0) {
            send_json_error('Invalid product data.');
        }

        $stmt = $conn->prepare("SELECT p.name, p.price, p.image_url, p.allow_message, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if (!$product) {
            send_json_error('Product not found.');
        }

        $customization_options = [
            'brownie' => [3 => 3, 6 => 6, 12 => 12],
            'cookies' => [3 => 3, 5 => 5, 8 => 8],
            'donut'   => [2 => 2, 4 => 4, 6 => 6],
            'cupcake' => [2 => 2, 3 => 3, 4 => 4, 6 => 6],
            'default' => [1 => 1]
        ];
        
        $product_category_name = strtolower(trim($product['category_name']));
        $base_price = (float)$product['price'];
        
        $multiplier = $customization_options[$product_category_name][$pieces] ?? 1;
        $price_per_unit = $base_price * $multiplier;

        $options_data = [];
        if ($multiplier > 1) {
             $options_data['Pieces'] = $pieces . ' Pieces';
        }
        if ($product['allow_message'] && !empty($message)) {
            $options_data['Message'] = htmlspecialchars($message);
        }

        $item_key = $product_id . '_' . md5(serialize($options_data));
        $cart = $_SESSION['cart'] ?? [];

        if (isset($cart[$item_key])) {
            $cart[$item_key]['quantity'] += $quantity;
        } else {
            $cart[$item_key] = [
                'product_id'     => $product_id,
                'name'           => $product['name'],
                'quantity'       => $quantity,
                'price_per_unit' => $price_per_unit,
                'image_url'      => $product['image_url'],
                'options'        => $options_data,
            ];
        }
        $_SESSION['cart'] = $cart;
    }

    // --- Recalculate Total Cart Count and Send Response ---
    $total_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $total_count += $item['quantity'];
        }
    }
    
    session_write_close();

    echo json_encode([
        'success'    => true,
        'message'    => 'Product added to cart!',
        'cart_count' => $total_count
    ]);

} catch (Exception $e) {
    // Log the error message to a file for debugging
    // error_log($e->getMessage());
    send_json_error('A server error occurred. Please try again later.');
}
?>