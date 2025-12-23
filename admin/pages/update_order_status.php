<?php
// CRITICAL: Prevent ANY output before JSON response
ob_start();

// CRITICAL: Disable ALL error output immediately
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// CRITICAL: Set JSON content type header first
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Function to send clean JSON response and terminate
function sendCleanJsonResponse($data, $httpCode = 200) {
    // Clear ALL previous output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start fresh output buffer
    ob_start();
    
    // Set status code
    http_response_code($httpCode);
    
    // Output ONLY JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // Flush and terminate
    ob_end_flush();
    exit();
}

// Start session quietly
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (Exception $e) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Session error occurred.'
    ], 500);
}

// Include database connection with error handling
$conn = null;
try {
    // Check if file exists first
    $db_file = '../../db_connect.php';
    if (!file_exists($db_file)) {
        sendCleanJsonResponse([
            'status' => 'error', 
            'message' => 'Database configuration file not found.'
        ], 500);
    }
    
    // Capture any output from db_connect.php
    ob_start();
    require_once $db_file;
    $db_output = ob_get_contents();
    ob_end_clean();
    
    // Check if there was any unwanted output
    if (!empty(trim($db_output))) {
        error_log("Database include produced output: " . $db_output);
    }
    
    // Verify connection exists
    if (!isset($conn) || !$conn) {
        sendCleanJsonResponse([
            'status' => 'error', 
            'message' => 'Database connection not established.'
        ], 500);
    }
    
    // Test the connection
    if (!$conn->ping()) {
        sendCleanJsonResponse([
            'status' => 'error', 
            'message' => 'Database connection lost.'
        ], 500);
    }
    
} catch (Exception $e) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Database connection failed: ' . $e->getMessage()
    ], 500);
} catch (Error $e) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Database configuration error.'
    ], 500);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Only POST requests are allowed.'
    ], 405);
}

// Validate admin session
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Unauthorized access. Please login as admin.'
    ], 403);
}

// Get and validate input data
$input = file_get_contents('php://input');
if (empty($input)) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'No data received.'
    ], 400);
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Invalid JSON format: ' . json_last_error_msg()
    ], 400);
}

// Validate required fields
if (!isset($data['order_id']) || !isset($data['status'])) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Missing required fields: order_id and status.'
    ], 400);
}

// Validate and sanitize order_id
$order_id = filter_var($data['order_id'], FILTER_VALIDATE_INT);
if ($order_id === false || $order_id <= 0) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Invalid order ID format.'
    ], 400);
}

// Validate status value
$status = trim($data['status']);
$allowed_statuses = ['Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];

if (!in_array($status, $allowed_statuses)) {
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Invalid status value. Allowed values: ' . implode(', ', $allowed_statuses)
    ], 400);
}

try {
    // First, check if the order exists
    $check_stmt = $conn->prepare("SELECT id, order_status FROM orders WHERE id = ?");
    if (!$check_stmt) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $order_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Failed to execute check statement: " . $check_stmt->error);
    }
    
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        sendCleanJsonResponse([
            'status' => 'error', 
            'message' => 'Order not found.'
        ], 404);
    }
    
    $order = $result->fetch_assoc();
    $current_status = $order['order_status'];
    $check_stmt->close();
    
    // Check if status is actually changing
    if ($current_status === $status) {
        sendCleanJsonResponse([
            'status' => 'success', 
            'message' => 'Order status is already set to ' . $status,
            'order_id' => $order_id,
            'status' => $status
        ]);
    }
    
    // Update the order status
    $update_stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    if (!$update_stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $update_stmt->bind_param("si", $status, $order_id);
    
    if (!$update_stmt->execute()) {
        $error_msg = $update_stmt->error;
        $update_stmt->close();
        throw new Exception("Failed to execute update: " . $error_msg);
    }
    
    // Get affected rows count before closing
    $affected_rows = $update_stmt->affected_rows;
    $update_stmt->close();
    
    // Check if any rows were affected
    if ($affected_rows === 0) {
        sendCleanJsonResponse([
            'status' => 'error', 
            'message' => 'No changes made. Order may not exist or status is already set.'
        ], 400);
    }
    
    // Success response
    sendCleanJsonResponse([
        'status' => 'success', 
        'message' => 'Order status updated successfully from "' . $current_status . '" to "' . $status . '"',
        'order_id' => $order_id,
        'old_status' => $current_status,
        'new_status' => $status,
        'affected_rows' => $affected_rows
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Order status update error for Order ID {$order_id}: " . $e->getMessage());
    
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'Database error occurred while updating order status.'
    ], 500);
} catch (Error $e) {
    // Log fatal errors
    error_log("Fatal error in order status update: " . $e->getMessage());
    
    sendCleanJsonResponse([
        'status' => 'error', 
        'message' => 'A system error occurred.'
    ], 500);
}

// Close database connection if it exists
if (isset($conn) && $conn) {
    try {
        $conn->close();
    } catch (Exception $e) {
        // Log but don't stop execution
        error_log("Failed to close database connection: " . $e->getMessage());
    }
}

// This should never be reached due to sendCleanJsonResponse() calls above
sendCleanJsonResponse([
    'status' => 'error', 
    'message' => 'Unexpected error occurred.'
], 500);
?>