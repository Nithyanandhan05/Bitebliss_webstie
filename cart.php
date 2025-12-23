<?php
// File: cart.php (REVISED AND CORRECTED)
session_start();
require_once 'db_connect.php';

// --- AJAX HANDLER for REMOVE and UPDATE actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid action.'];
    $item_key = $_POST['item_key'] ?? null;

    if ($item_key && isset($_SESSION['cart'][$item_key])) {
        if ($_POST['action'] === 'remove') {
            unset($_SESSION['cart'][$item_key]);
            $response = ['status' => 'success', 'message' => 'Item removed.'];
        } elseif ($_POST['action'] === 'update_qty') {
            $new_qty = (int)($_POST['quantity'] ?? 1);
            if ($new_qty > 0) {
                $_SESSION['cart'][$item_key]['quantity'] = $new_qty;
                $response = ['status' => 'success', 'message' => 'Quantity updated.'];
            } else {
                // If quantity becomes 0 or less, remove it
                unset($_SESSION['cart'][$item_key]);
                $response = ['status' => 'success', 'message' => 'Item removed.'];
            }
        }
    }

    // After any action, recalculate totals and send back
    $subtotal = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['price_per_unit']) && isset($item['quantity'])) {
                 $subtotal += $item['price_per_unit'] * $item['quantity'];
            }
        }
    }
    $delivery_charge = empty($_SESSION['cart']) ? 0 : 50.00;
    $total = $subtotal + $delivery_charge;
    
    $response['subtotal'] = number_format($subtotal, 2);
    $response['total'] = number_format($total, 2);
    $response['cart_empty'] = empty($_SESSION['cart']);

    echo json_encode($response);
    exit();
}


// --- REGULAR PAGE LOAD LOGIC ---
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    // This loop processes the session cart for display
    foreach ($_SESSION['cart'] as $item_key => $item) {
        if (!is_array($item) || !isset($item['product_id'])) continue;

        $item_total_price = $item['price_per_unit'] * $item['quantity'];
        $image_url = 'img/placeholder.png'; // Default image

        // For custom boxes, the image is fixed
        if ($item['product_id'] === 'custom_box') {
            $image_url = 'img/custom_box_placeholder.png';
        } else {
            // For regular products, try to fetch the image from the DB
            $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if($prod_img = $result->fetch_assoc()) {
                $image_url = $prod_img['image_url'];
            }
            $stmt->close();
        }

        $cart_items[] = [
            'key' => $item_key,
            'id' => $item['product_id'],
            'name' => $item['name'],
            'price_per_unit' => $item['price_per_unit'],
            'image_url' => $image_url,
            'quantity' => $item['quantity'],
            'options' => $item['options'] ?? [],
            'total_price' => $item_total_price,
        ];
        $subtotal += $item_total_price;
    }
}

$total = $subtotal;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Bite Bliss</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="cart.css?v=1.2"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    <style>
        .item-options { font-size: 0.85rem; color: #d2b48c; margin-top: 8px; padding-left: 10px; border-left: 2px solid rgba(210, 180, 140, 0.3); }
        .item-options p { margin: 2px 0; }
        .item-options ul { list-style-type: none; padding-left: 5px; margin-top: 5px; }
        .item-options li { margin-bottom: 3px; }
        #empty-cart-message { display: <?php echo empty($cart_items) ? 'flex' : 'none'; ?>; flex-direction: column; align-items: center; justify-content: center; padding: 50px; text-align: center; }
        .empty-icon { font-size: 5rem; color: #d2b48c; margin-bottom: 20px; }
    </style>
</head>
<body class="cart-page-body">
    <?php include 'header.php'; ?> 

    <main class="cart-page-container">
        <h1 class="cart-title">Your Shopping Cart</h1>
        
        <div class="cart-layout" id="cart-layout" style="display: <?php echo empty($cart_items) ? 'none' : 'flex'; ?>;">
            <div class="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-key="<?php echo htmlspecialchars($item['key']); ?>" data-price-per-unit="<?php echo $item['price_per_unit']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            
                            <?php if (!empty($item['options'])): ?>
                                <div class="item-options">
                                    <?php foreach ($item['options'] as $option_name => $option_value): ?>
                                        <p><strong><?php echo htmlspecialchars($option_name); ?>:</strong>
                                            <?php if (is_array($option_value)): ?>
                                                <ul>
                                                <?php foreach($option_value as $sub_item): ?>
                                                    <li>- <?php echo htmlspecialchars($sub_item); ?></li>
                                                <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($option_value); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <p class="item-price">₹<?php echo number_format($item['price_per_unit'], 2); ?></p>
                        </div>
                        
                        <div class="cart-item-controls">
                            <div class="item-quantity">
                                <button class="quantity-btn minus-btn" data-action="decrease">-</button>
                                <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                                <button class="quantity-btn plus-btn" data-action="increase">+</button>
                            </div>
                            <p class="item-total">₹<?php echo number_format($item['total_price'], 2); ?></p>
                            <button class="remove-btn"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="continue-shopping-btn-container">
                    <a href="menu.php" class="btn-continue-shopping">← Continue Shopping</a>
                </div>
            </div>

            <aside class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span id="total">₹<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="checkout-btn-container">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                    <?php else: ?>
                        <button type="button" id="prompt-login-btn" class="btn-checkout">Login to Continue</button>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
        
        <div class="empty-cart" id="empty-cart-message">
            <i class="fas fa-shopping-cart empty-icon"></i>
            <h2>Your cart is empty!</h2>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="menu.php" class="btn-shop-now">Continue Shopping</a>
        </div>
    </main>

    <?php include 'login_panel.php'; ?>
    <?php require_once 'footer.php'; ?>

    <script src="script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartLayout = document.getElementById('cart-layout');
        if (!cartLayout) return;

        // Function to update the summary totals
        function updateSummary(subtotal, total, delivery, cartEmpty) {
            document.getElementById('subtotal').textContent = '₹' + subtotal;
            document.getElementById('total').textContent = '₹' + total;
            if (cartEmpty) {
                document.getElementById('cart-layout').style.display = 'none';
                document.getElementById('empty-cart-message').style.display = 'flex';
            }
        }

        // --- Main event handler for all cart actions ---
        cartLayout.addEventListener('click', function(event) {
            const target = event.target;
            const cartItem = target.closest('.cart-item');
            if (!cartItem) return;

            const itemKey = cartItem.dataset.key;
            let action = null;
            let newQty = 0;

            if (target.closest('.remove-btn')) {
                action = 'remove';
            } else if (target.matches('.quantity-btn')) {
                action = 'update_qty';
                const qtyInput = cartItem.querySelector('.quantity-input');
                let currentQty = parseInt(qtyInput.value, 10);
                newQty = target.dataset.action === 'increase' ? currentQty + 1 : currentQty - 1;
            }

            if (action) {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('item_key', itemKey);
                if (action === 'update_qty') {
                    formData.append('quantity', newQty);
                }

                // Animate and send request
                cartItem.style.opacity = '0.5';

                fetch('cart.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (action === 'remove' || newQty <= 0) {
                             cartItem.remove();
                        } else {
                            // Update quantity and item total on the page
                            const qtyInput = cartItem.querySelector('.quantity-input');
                            const itemTotalEl = cartItem.querySelector('.item-total');
                            const pricePerUnit = parseFloat(cartItem.dataset.pricePerUnit);
                            qtyInput.value = newQty;
                            itemTotalEl.textContent = '₹' + (newQty * pricePerUnit).toFixed(2);
                            cartItem.style.opacity = '1';
                        }
                        // Update the order summary
                        updateSummary(data.subtotal, data.total, data.delivery_charge, data.cart_empty);
                    } else {
                        alert('Error: ' + data.message);
                        cartItem.style.opacity = '1';
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    cartItem.style.opacity = '1';
                });
            }
        });
        
        // Logic for the login button if it exists
        const promptLoginBtn = document.getElementById('prompt-login-btn');
        if (promptLoginBtn) {
            promptLoginBtn.addEventListener('click', function() {
                // Redirects the browser to the login page
                window.location.href = 'login.php';
            });
        }
    });
    </script>
</body>
</html>