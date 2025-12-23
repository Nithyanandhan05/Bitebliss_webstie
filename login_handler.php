<?php
session_start();
require_once 'db_connect.php';

$response = ['success' => false, 'message' => 'Invalid credentials.'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_number = $_POST['phone_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($phone_number) || empty($password)) {
        $response['message'] = 'Please fill in all fields.';
        echo json_encode($response);
        exit;
    }

    $found_user = false;

    // Check for ADMIN
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $response = ['success' => true, 'message' => 'Admin login successful!', 'user_type' => 'admin'];
            $found_user = true;
        }
    }
    $stmt->close();

    // Check for USER if not admin
    if (!$found_user) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE phone_number = ?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $response = ['success' => true, 'message' => 'Login successful!', 'user_type' => 'customer'];
            }
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>