<?php
// File: get_order_details.php (CORRECTED)

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
    exit;
}

// THIS QUERY IS NOW FIXED
// The IFNULL function now provides a valid, real path to a placeholder image.
$stmt = $conn->prepare("
    SELECT
        oi.quantity,
        oi.price,
        oi.customizations,
        oi.product_name,
        IFNULL(p.image_url, 'img/custom_box_placeholder.png') as image_url
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");

$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'items' => $items]);
?>