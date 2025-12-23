<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // --- ADD NEW ADDRESS ---
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];
    $pincode = $_POST['pincode'];
    $flat_house_no = $_POST['flat_house_no'];
    $area_street = $_POST['area_street'];
    $landmark = $_POST['landmark'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // If setting as default, unset other defaults first
    if ($is_default) {
        $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
    }

    $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, phone_number, pincode, flat_house_no, area_street, landmark, city, state, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssi", $user_id, $full_name, $phone_number, $pincode, $flat_house_no, $area_street, $landmark, $city, $state, $is_default);
    
    if ($stmt->execute()) {
        $new_address_id = $stmt->insert_id;
        // Fetch the newly added address to return to the frontend
        $result = $conn->query("SELECT * FROM user_addresses WHERE id = $new_address_id");
        $new_address = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'message' => 'Address added successfully!', 'address' => $new_address]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save address.']);
    }
    $stmt->close();
}
// Add 'delete' and 'set_default' actions here if needed in the future
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}

$conn->close();
?>