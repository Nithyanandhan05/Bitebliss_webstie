<?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php'; // <-- ADDED: For PHPMailer

// --- ADDED: PHPMailer classes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Security Check: Ensure payment was verified
if (!isset($_SESSION['payment_verified']) || $_SESSION['payment_verified'] !== true) {
    header('Location: checkout.php?error=InvalidAccess');
    exit();
}
unset($_SESSION['payment_verified']);

// Security Check: Ensure user and cart exist
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $total_price = $_SESSION['order_total_amount'];
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

    if ($address_id <= 0) {
        header('Location: checkout.php?error=NoAddressSelected');
        exit();
    }

    // Fetch the correct address from the database
    $addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $addr_stmt->bind_param("ii", $address_id, $user_id);
    $addr_stmt->execute();
    $address_result = $addr_stmt->get_result();

    if ($address_result->num_rows === 0) {
        header('Location: checkout.php?error=InvalidAddress');
        exit();
    }
    $chosen_address = $address_result->fetch_assoc();
    $addr_stmt->close();

    // Construct the address string from the fetched data
    $phone_number = $chosen_address['phone_number'];
    $delivery_address = "{$chosen_address['full_name']}, {$chosen_address['flat_house_no']}, {$chosen_address['area_street']}";
    if (!empty($chosen_address['landmark'])) {
        $delivery_address .= ", {$chosen_address['landmark']}";
    }
    $delivery_address .= ", {$chosen_address['city']}, {$chosen_address['state']} - {$chosen_address['pincode']}";

    // Create the order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, delivery_address, phone_number, total_price, razorpay_payment_id, order_status) VALUES (?, ?, ?, ?, ?, ?, 'Preparing')");
    $stmt->bind_param("iissds", $user_id, $address_id, $delivery_address, $phone_number, $total_price, $razorpay_payment_id);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, customizations) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($_SESSION['cart'] as $item) {
            $customizations_json = !empty($item['options']) ? json_encode($item['options']) : NULL;
            $product_id_to_save = is_numeric($item['product_id']) ? $item['product_id'] : NULL;
            $item_stmt->bind_param("iisids", $order_id, $product_id_to_save, $item['name'], $item['quantity'], $item['price_per_unit'], $customizations_json);
            $item_stmt->execute();
        }
        $item_stmt->close();

        // ===================================================================
        // START: SEND ORDER NOTIFICATION VIA EMAIL
        // ===================================================================
        
        // --- Build the Order Items List as an HTML string ---
        $items_html = "<table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
        $items_html .= "<thead><tr style='background-color: #f2f2f2;'><th>Item</th><th>Quantity</th><th>Price</th><th>Customizations</th></tr></thead><tbody>";
        foreach ($_SESSION['cart'] as $item) {
            $items_html .= "<tr>";
            $items_html .= "<td>" . htmlspecialchars($item['name']) . "</td>";
            $items_html .= "<td>" . htmlspecialchars($item['quantity']) . "</td>";
            $items_html .= "<td>₹" . number_format($item['price_per_unit'], 2) . "</td>";
            
            $customizations_text = "";
            if (!empty($item['options'])) {
                foreach($item['options'] as $option_name => $option_value) {
                    $value = is_array($option_value) ? implode(', ', $option_value) : $option_value;
                    $customizations_text .= "<strong>" . htmlspecialchars($option_name) . ":</strong> " . htmlspecialchars($value) . "<br>";
                }
            }
            $items_html .= "<td>" . ($customizations_text ?: 'N/A') . "</td>";
            $items_html .= "</tr>";
        }
        $items_html .= "</tbody></table>";

        // --- Construct the main email body ---
        $email_subject = "🎉 New Order Received! (#{$order_id})";
        $email_body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>New Order (#{$order_id})</h2>
                <p>A new order has been placed on your website.</p>
                <h3>Total Amount: ₹" . number_format($total_price, 2) . "</h3>
                <hr>
                <h3>Order Details:</h3>
                {$items_html}
                <hr>
                <h3>Delivery Information:</h3>
                <p>
                    <strong>Address:</strong> " . htmlspecialchars($delivery_address) . "<br>
                    <strong>Contact:</strong> " . htmlspecialchars($phone_number) . "
                </p>
            </body>
            </html>";

        // --- Send Email via PHPMailer ---
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            // --- IMPORTANT: FILL IN YOUR DETAILS BELOW ---
            $mail->Username   = 'biteblissbrownie@gmail.com'; // Your Gmail address
            $mail->Password   = 'tpwv lsww ifwj mwbx';      // Your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('your.sending.email@gmail.com', 'Bite Bliss Orders');
            $mail->addAddress('biteblissbronwnie@gmail.com'); // <-- The email that receives the order notifications

            $mail->isHTML(true);
            $mail->Subject = $email_subject;
            $mail->Body    = $email_body;
            
            $mail->send();
        } catch (Exception $e) {
            // Log the error, but don't stop the user's flow
            error_log("Order notification email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
        // ===================================================================
        // END: SEND ORDER NOTIFICATION VIA EMAIL
        // ===================================================================
                
        // Clear session data after successful order
        unset($_SESSION['cart']);
        unset($_SESSION['razorpay_order_id']);
        unset($_SESSION['order_total_amount']);

        header("Location: thank_you.php?order_id=" . $order_id);
        exit();
    } else {
        error_log("Order placement failed: " . $stmt->error);
        echo "Error: Could not place the order.";
    }
    $stmt->close();
}
$conn->close();
?>