<?php
// File: get_admin_order_details.php (CORRECTED)

session_start();
require_once '../../db_connect.php'; // Correct path for admin folder structure

header('Content-Type: application/json');

if (!isset($_SESSION['admin_loggedin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
    exit;
}

// THIS QUERY IS NOW FIXED
// It uses LEFT JOIN to include all items, even those without a product_id (like custom boxes).
// It relies on `oi.product_name` to get the item's name at the time of purchase.
// It uses IFNULL to provide a fallback image for items without a match in the products table.
$stmt = $conn->prepare("
    SELECT 
        oi.quantity, 
        oi.price, 
        oi.customizations, 
        oi.product_name,
        IFNULL(p.image_url, 'img/custom_box_placeholder.png') as image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
    
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
$conn->close();
    
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'items' => $items]);
?>