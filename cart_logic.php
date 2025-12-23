<?php // NO blank lines or characters before this line
session_start();

header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_POST['action']) || $_POST['action'] !== 'add' || !isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$item_key = $product_id;

// --- SAFE SESSION HANDLING ---
// 1. Read the current cart into a local variable.
$cart = $_SESSION['cart'] ?? [];

// 2. Modify the local variable.
if (isset($cart[$item_key])) {
    $cart[$item_key]['quantity']++;
} else {
    $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($product = $result->fetch_assoc()) {
        $cart[$item_key] = [
            'product_id'     => $product_id,
            'name'           => $product['name'],
            'quantity'       => 1,
            'price_per_unit' => $product['price'],
            'options'        => []
        ];
    }
    $stmt->close();
}

// 3. Write the updated local variable back to the session.
$_SESSION['cart'] = $cart;

// --- Calculate total cart count ---
$total_count = 0;
foreach($_SESSION['cart'] as $item) {
    if (isset($item['quantity'])) {
        $total_count += $item['quantity'];
    }
}

// 4. Explicitly save the session data and release the lock.
session_write_close();
$conn->close();

echo json_encode(['success' => true, 'cart_count' => $total_count]);
?>