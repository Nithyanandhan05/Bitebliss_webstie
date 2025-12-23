<?php
// File: send_signup_otp.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; 
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An error occurred.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Use session data if it's a resend request
    if (isset($_SESSION['pending_registration']) && empty($_POST)) {
        $email = $_SESSION['pending_registration']['email'];
    } else {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $password = $_POST['password'] ?? '';

        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ? OR email = ?");
        $stmt->bind_param("ss", $phone_number, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            throw new Exception('This phone number or email is already registered.');
        }
        $stmt->close();

        // Store pending registration data in session
        $_SESSION['pending_registration'] = [
            'username' => $username,
            'email' => $email,
            'phone_number' => $phone_number,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
    }
    
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_timestamp'] = time();

    // --- Send OTP via PHPMailer ---
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'biteblissbrownie@gmail.com'; // Your Gmail
    $mail->Password   = 'tpwv lsww ifwj mwbx';    // Your Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('your-email@gmail.com', 'Bite Bliss');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your Bite Bliss Registration OTP';
    $mail->Body    = "Your One-Time Password (OTP) for Bite Bliss registration is: <h2>{$otp}</h2>";
    
    $mail->send();

    $response['success'] = true;
    $response['message'] = 'OTP has been sent to your email address.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>