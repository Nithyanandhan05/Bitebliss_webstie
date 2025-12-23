<?php
// Start a session to store status messages
session_start();

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Make sure you have uploaded the PHPMailer files to this folder
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $redirect_page = "contact.php";

    // Sanitize and retrieve form data
    $name = trim(filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $subject = trim(filter_var($_POST['subject'], FILTER_SANITIZE_STRING));
    $message = trim(filter_var($_POST['message'], FILTER_SANITIZE_STRING));

    // Validate form data
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
        $_SESSION['status_message'] = "Please fill out all fields with valid information.";
        $_SESSION['status_type'] = "error";
        header("Location: $redirect_page");
        exit();
    }

    // Create an instance of PHPMailer
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS (HOSTINGER) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';                 // Set the SMTP server to hostinger
        $mail->SMTPAuth   = true;                                 // Enable SMTP authentication
        $mail->Username   = 'support@bitebliss.shop';             // <<< YOUR HOSTINGER EMAIL ADDRESS
        $mail->Password   = 'GU@ni2004';              // <<< THE PASSWORD FOR THAT EMAIL ACCOUNT
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;          // Enable implicit SSL encryption
        $mail->Port       = 465;                                  // TCP port to connect to

        // --- RECIPIENTS ---
        // 'setFrom' should be the same as your Username
        $mail->setFrom('support@bitebliss.shop', 'Bite Bliss Website'); 
        // 'addAddress' is where the email will be sent to
        $mail->addAddress('support@bitebliss.shop', 'Bite Bliss Admin');
        // 'addReplyTo' lets you reply directly to the person who filled out the form
        $mail->addReplyTo($email, $name);

        // --- CONTENT ---
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = 'New Contact Form: ' . $subject;
        $mail->Body    = "You have received a new message from your website contact form.\n\n" .
                         "Name: " . $name . "\n" .
                         "Email: " . $email . "\n\n" .
                         "Message:\n" . $message;

        $mail->send();
        
        // Success
        $_SESSION['status_message'] = 'Thank you! Your message has been sent successfully.';
        $_SESSION['status_type'] = 'success';

    } catch (Exception $e) {
        // Failure - this will show a detailed error for debugging
        $_SESSION['status_message'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $_SESSION['status_type'] = 'error';
    }

    // Redirect back to the contact page
    header("Location: $redirect_page");
    exit();

} else {
    // If someone tries to access this file directly, redirect them away
    header("Location: contact.php");
    exit();
}
?>