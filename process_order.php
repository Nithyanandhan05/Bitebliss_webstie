<?php
session_start();
require_once 'db_connect.php'; // Make sure you have the database connection

// Check if user is logged in and form was submitted
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    // Redirect to login or show an error
    header('Location: login.php');
    exit;
}

// 1. GET DATA FROM THE SUBMISSION
$user_id = $_SESSION['user_id'];
$selected_address_id = $_POST['selected_address_id']; // ID from the checkout form radio button

// NOTE: You would get order details (items, total price) from the session or POST data
$total_price = 100.00; // Example total price
$payment_id = 'pay_xxxxxxxxxxxxxx'; // Example payment ID

// 2. FETCH THE FULL ADDRESS STRING
$address_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
$address_stmt->bind_param("ii", $selected_address_id, $user_id);
$address_stmt->execute();
$result = $address_stmt->get_result();
$address_data = $result->fetch_assoc();
$address_stmt->close();

if (!$address_data) {
    die("Error: Selected address not found for this user.");
}

// Create a single string for the shipping address
$shipping_address_string = $address_data['flat_house_no'] . ", " . $address_data['area_street'] . ", " .
                           $address_data['city'] . ", " . $address_data['state'] . " - " . $address_data['pincode'] .
                           ". Landmark: " . $address_data['landmark'];

// 3. INSERT THE ORDER INTO THE 'orders' TABLE
$insert_stmt = $conn->prepare(
    "INSERT INTO orders (user_id, total_price, razorpay_payment_id, shipping_address, order_status) VALUES (?, ?, ?, ?, 'Preparing')"
);
// The 's' for shipping_address is because we are now storing it as a text string
$insert_stmt->bind_param("idss", $user_id, $total_price, $payment_id, $shipping_address_string);
$insert_stmt->execute();
$order_id = $insert_stmt->insert_id;
$insert_stmt->close();

// TODO: You should also save the individual items from the cart into an 'order_items' table, linking them with the new $order_id.

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; }
        .confirmation-box { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 15px 30px rgba(0,0,0,0.15); max-width: 500px; }
        .confirmation-box h1 { color: #975ab7; }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h1>Thank You For Your Order!</h1>
        <p>Your Order ID is: <strong><?php echo $order_id; ?></strong></p>
        <p>It will be shipped to:</p>
        <p><em><?php echo htmlspecialchars($shipping_address_string); ?></em></p>
        <br>
        <a href="index.php" class='btn'>Go Back to Homepage</a>
    </div>
</body>
</html>