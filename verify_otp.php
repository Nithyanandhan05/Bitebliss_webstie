<?php
// File: verify_otp.php - CORRECTED FOR EMAIL
session_start();

$response = ['success' => false, 'message' => 'Invalid OTP.'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_otp = $_POST['otp'] ?? '';
    $session_otp = $_SESSION['otp'] ?? '';
    // --- FIX: Read the email from the session, not the phone number ---
    $session_email = $_SESSION['otp_email'] ?? ''; 

    $otp_timestamp = $_SESSION['otp_timestamp'] ?? 0;
    if (time() - $otp_timestamp > 300) { // 5 minute expiry
        $response['message'] = 'OTP has expired. Please request a new one.';
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_timestamp']);
    } elseif (!empty($submitted_otp) && $submitted_otp == $session_otp) {
        
        // --- FIX: Store the verified EMAIL in the session ---
        $_SESSION['otp_verified_email'] = $session_email; 
        
        // Clean up OTP data from session
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_timestamp']);

        $response['success'] = true;
        $response['message'] = 'Email verified successfully!';
    }
}

echo json_encode($response);
?>