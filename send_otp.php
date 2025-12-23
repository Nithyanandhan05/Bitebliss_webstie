<?php
// File: send_otp.php - Updated for PHPMailer (Email OTP)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; 
require 'vendor/autoload.php'; // Include Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => 'An error occurred.'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $purpose = $_POST['purpose'] ?? 'signup'; // 'signup' or 'forgot_password'

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }
    
    // For 'forgot_password', check if the email exists first
    if ($purpose === 'forgot_password') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $response['message'] = 'No account found with this email address.';
            echo json_encode($response);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();
    }

    $otp = rand(100000, 999999);

    // Store OTP and email in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_timestamp'] = time();

    // --- PHPMailer Integration ---
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'biteblissbrownie@gmail.com'; // Your Gmail address
        $mail->Password   = 'tpwv lsww ifwj mwbx';    // Your 16-character Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Recipients
        $mail->setFrom('your-email@gmail.com', 'Bite Bliss');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Bite Bliss';
        $mail->Body    = "Hello,<br><br>Your One-Time Password (OTP) for Bite Bliss is: <h2>{$otp}</h2><br>This OTP is valid for 5 minutes.<br><br>Thank you,<br>The Bite Bliss Team";
        $mail->AltBody = "Your One-Time Password (OTP) for Bite Bliss is: {$otp}";

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'OTP has been sent to your email address.';

    } catch (Exception $e) {
        $response['message'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

$conn->close();
echo json_encode($response);
?>