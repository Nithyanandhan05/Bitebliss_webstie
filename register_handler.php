<?php
// register_handler.php - MODIFIED FOR 2-STEP VERIFICATION
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $submitted_otp = $_POST['otp'] ?? '';
    $session_otp = $_SESSION['otp'] ?? '';
    $pending_data = $_SESSION['pending_registration'] ?? null;

    if (empty($submitted_otp) || empty($session_otp)) {
        throw new Exception('OTP not found. Please start over.');
    }
    if ($submitted_otp != $session_otp) {
        throw new Exception('The OTP you entered is incorrect.');
    }
    if (time() - ($_SESSION['otp_timestamp'] ?? 0) > 300) { // 5 minute expiry
        throw new Exception('OTP has expired. Please request a new one.');
    }
    if (!$pending_data) {
        throw new Exception('Registration data not found in session. Please start over.');
    }

    // OTP is correct, proceed with registration
    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, phone_number, password) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", 
        $pending_data['username'], 
        $pending_data['email'], 
        $pending_data['phone_number'], 
        $pending_data['password']
    );

    if ($insert_stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Log the user in automatically
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $pending_data['username'];
        
        // Clean up session
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_timestamp'], $_SESSION['pending_registration']);
        
        $response['success'] = true;
        $response['message'] = 'Registration successful! Welcome!';
        $response['user_type'] = 'customer';
    } else {
        throw new Exception('Database error. Could not create account.');
    }
    $insert_stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>