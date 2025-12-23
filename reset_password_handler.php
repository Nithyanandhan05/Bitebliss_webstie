<?php
// File: reset_password_handler.php - CORRECTED
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

$response = ['success' => false, 'message' => 'An error occurred.'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    // We get the email from the SESSION, not the POST data. This is more secure.
    $session_otp = $_SESSION['otp'] ?? '';
    $session_email = $_SESSION['otp_email'] ?? '';

    // We removed $_POST['email'] from the validation check
    if (empty($otp) || empty($new_password) || empty($session_email)) {
        $response['message'] = 'Session expired or fields are empty. Please start over.';
    } elseif ($otp != $session_otp) { // We only need to check the OTP
        $response['message'] = 'The OTP you entered is incorrect.';
    } elseif (strlen($new_password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // We use the SECURE session email to update the password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $session_email);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Password has been reset successfully. You can now log in.';
            
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_timestamp']);
        } else {
            $response['message'] = 'Database error. Could not update password.';
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>