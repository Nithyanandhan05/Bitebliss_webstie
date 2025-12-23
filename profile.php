<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT username, phone_number FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Fetch user addresses
$addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addr_stmt->bind_param("i", $user_id);
$addr_stmt->execute();
$user_addresses = $addr_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$addr_stmt->close();

$orders = [];
$order_stmt = $conn->prepare("SELECT id, total_price, order_status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
if ($order_result->num_rows > 0) {
    while($row = $order_result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$order_stmt->close();

// --- MODIFIED: Updated the status colors ---
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'delivered':
            return 'linear-gradient(135deg, #10b981, #059669)'; // Green
        case 'preparing':
            return 'linear-gradient(135deg, #f59e0b, #d97706)'; // Amber
        case 'out for delivery':
            return 'linear-gradient(135deg, #3b82f6, #2563eb)'; // Blue
        case 'cancelled':
            return 'linear-gradient(135deg, #6b7280, #4b5563)'; // Gray
        default:
            return 'linear-gradient(135deg, #06b6d4, #0891b2)'; // Default Cyan
    }
}
$firstName = explode(' ', $user['username'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bite Bliss</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="profile.css">
</head>
<body class="profile-page-body">
    <div class="particles"></div>
    <?php include 'header.php'; ?>

    <main class="profile-container">
        <div class="welcome-section">
            <h1 class="profile-title">Welcome Back, <?php echo htmlspecialchars($firstName); ?>!</h1>
            <p class="welcome-subtitle">Manage your profile and track your delicious orders</p>
        </div>
        <aside class="user-details">
            <div class="user-avatar"><i class="fas fa-user"></i></div>
            <h2>Your Details</h2>
            <div class="detail-group"><label>Name</label><p><?php echo htmlspecialchars($user['username']); ?></p></div>
            <div class="detail-group"><label>Phone Number</label><p><?php echo htmlspecialchars($user['phone_number']); ?></p></div>
            
            <div class="detail-group address-group">
                <div class="detail-group-header">
                    <label>Address</label>
                    <a href="#" class="manage-link">Manage</a>
                </div>
                <div class="address-list">
                    <?php if (!empty($user_addresses)): ?>
                        <?php foreach($user_addresses as $addr): ?>
                            <div class="address-card">
                                <strong><?php echo htmlspecialchars($addr['full_name']); ?><?php if($addr['is_default']) echo ' <span class="default-badge">Default</span>'; ?></strong>
                                <?php
                                    echo htmlspecialchars($addr['flat_house_no']) . ", ";
                                    echo htmlspecialchars($addr['area_street']) . "<br>";
                                    echo htmlspecialchars($addr['city']) . ", " . htmlspecialchars($addr['state']) . " - " . htmlspecialchars($addr['pincode']);
                                ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">No saved addresses found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </aside>

        <section class="order-history">
            <h2>Your Orders</h2>
            <?php if (!empty($orders)): ?>
                <div class="order-list">
                    <?php foreach ($orders as $index => $order): ?>
                        <div class="order-card" style="animation-delay: <?php echo (1 + $index * 0.2); ?>s;">
                            <div class="order-header">
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <span class="order-status" style="background: <?php echo getStatusColor($order['order_status']); ?>;">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="order-body">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date("d M, Y", strtotime($order['order_date'])); ?></span>
                                <span class="order-total">₹<?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                            <div class="order-footer">
                                <a href="#" class="view-details-btn" onclick="viewOrderDetails(event, <?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="menu.php"><i class="fas fa-utensils"></i> Browse Menu</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <div id="orderDetailsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalOrderId">Order Details</h2>
                <button id="modalCloseBtn" class="modal-close">&times;</button>
            </div>
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>

    <script src="script.js"></script>
    <script>
        const modal = document.getElementById('orderDetailsModal');
        const modalOrderId = document.getElementById('modalOrderId');
        const modalBody = document.getElementById('modalBody');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        function openModal() { modal.classList.add('active'); }
        function closeModal() { modal.classList.remove('active'); }
        modalCloseBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(event) { if (event.target === modal) { closeModal(); } });

        async function viewOrderDetails(event, orderId) {
            event.preventDefault();
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<div class="loading"></div> Loading...';
            try {
                const response = await fetch(`get_order_details.php?order_id=${orderId}`);
                if (!response.ok) { throw new Error('Network response was not ok'); }
                const data = await response.json();
                if (data.status === 'success') {
                    modalOrderId.textContent = `Details for Order #${orderId}`;
                    modalBody.innerHTML = '';
                    if (data.items.length > 0) {
                        data.items.forEach(item => {
                            const customizations = item.customizations ? JSON.parse(item.customizations) : {};
                            let customHtml = '';
                            if (Object.keys(customizations).length > 0) {
                                for (const [key, value] of Object.entries(customizations)) {
                                     if (Array.isArray(value)) {
                                        customHtml += `<p><strong>${key}:</strong> ${value.join(', ')}</p>`;
                                    } else {
                                        customHtml += `<p><strong>${key}:</strong> ${value}</p>`;
                                    }
                                }
                            }
                            const itemHtml = `<div class="modal-item-card"><div class="modal-item-image"><img src="${item.image_url}" alt="${item.product_name}"></div><div class="modal-item-info"><h4>${item.quantity} x ${item.product_name}</h4><div class="item-customizations">${customHtml || '<p>No customizations.</p>'}</div></div><div class="modal-item-price">₹${parseFloat(item.price * item.quantity).toFixed(2)}</div></div>`;
                            modalBody.innerHTML += itemHtml;
                        });
                    } else {
                        modalBody.innerHTML = '<p>No items found for this order.</p>';
                    }
                    openModal();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Failed to fetch order details. Please try again.');
            } finally {
                btn.innerHTML = originalText;
            }
        }
        document.querySelector('.btn-logout').addEventListener('click', function(e) { if (!confirm('Are you sure you want to logout?')) { e.preventDefault(); } });
    </script>
</body>
</html>