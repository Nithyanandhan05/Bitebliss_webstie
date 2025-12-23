<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header('Location: index.php?page=users');
    exit;
}

// Fetch user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    header('Location: index.php?page=users');
    exit;
}

$user = $user_result->fetch_assoc();
$stmt->close();

// Initialize order statistics
$order_count = 0;
$total_spent = 0;
$recent_orders = [];
$orders_table_exists = false;

// Check if orders table exists and get statistics
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($table_check && $table_check->num_rows > 0) {
        $orders_table_exists = true;
        
        // Try to get order statistics - be flexible with column names
        $possible_queries = [
            "SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = ?",
            "SELECT COUNT(*) as order_count, COALESCE(SUM(amount), 0) as total_spent FROM orders WHERE user_id = ?",
            "SELECT COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?",
            "SELECT COUNT(*) as order_count, 0 as total_spent FROM orders WHERE user_id = ?",
            "SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE customer_id = ?",
            "SELECT COUNT(*) as order_count, COALESCE(SUM(amount), 0) as total_spent FROM orders WHERE customer_id = ?",
        ];
        
        foreach ($possible_queries as $query) {
            try {
                $orders_stmt = $conn->prepare($query);
                if ($orders_stmt) {
                    $orders_stmt->bind_param("i", $user_id);
                    $orders_stmt->execute();
                    $orders_result = $orders_stmt->get_result();
                    if ($orders_result && $orders_result->num_rows > 0) {
                        $order_data = $orders_result->fetch_assoc();
                        $order_count = $order_data['order_count'] ?? 0;
                        $total_spent = $order_data['total_spent'] ?? 0;
                        $orders_stmt->close();
                        break; // Successfully got data, exit loop
                    }
                    $orders_stmt->close();
                }
            } catch (Exception $e) {
                // Continue to next query if this one fails
                continue;
            }
        }
        
        // Try to get recent orders if we found any orders
        if ($order_count > 0) {
            $recent_queries = [
                "SELECT id, order_date, status, total_amount FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5",
                "SELECT id, created_at as order_date, status, amount as total_amount FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                "SELECT id, order_date, status, 0 as total_amount FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5",
                "SELECT id, created_at as order_date, 'completed' as status, 0 as total_amount FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                "SELECT id, order_date, status, total_amount FROM orders WHERE customer_id = ? ORDER BY order_date DESC LIMIT 5",
                "SELECT id, created_at as order_date, status, amount as total_amount FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5",
            ];
            
            foreach ($recent_queries as $query) {
                try {
                    $recent_stmt = $conn->prepare($query);
                    if ($recent_stmt) {
                        $recent_stmt->bind_param("i", $user_id);
                        $recent_stmt->execute();
                        $recent_result = $recent_stmt->get_result();
                        if ($recent_result) {
                            while ($row = $recent_result->fetch_assoc()) {
                                $recent_orders[] = $row;
                            }
                            $recent_stmt->close();
                            break; // Successfully got data, exit loop
                        }
                        $recent_stmt->close();
                    }
                } catch (Exception $e) {
                    // Continue to next query if this one fails
                    continue;
                }
            }
        }
    }
} catch (Exception $e) {
    // Orders table doesn't exist or other error - that's fine
    $orders_table_exists = false;
}
?>

<div class="page-header">
    <div class="header-content">
        <a href="index.php?page=users" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <h1><i class="fas fa-user"></i> User Details</h1>
    </div>
</div>

<div class="user-details-container">
    <div class="user-info-card">
        <div class="card-header">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-basic-info">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="user-id">User ID: #<?php echo $user['id']; ?></p>
                <div class="user-status">
                    <span class="status-badge active">Active</span>
                </div>
            </div>
        </div>

        <div class="user-details-grid">
            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-phone"></i> Phone Number
                </div>
                <div class="detail-value"><?php echo htmlspecialchars($user['phone_number']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-calendar-plus"></i> Registration Date
                </div>
                <div class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-clock"></i> Account Age
                </div>
                <div class="detail-value">
                    <?php 
                    $created = new DateTime($user['created_at']);
                    $now = new DateTime();
                    $diff = $now->diff($created);
                    
                    if ($diff->days > 365) {
                        echo floor($diff->days / 365) . ' year' . (floor($diff->days / 365) > 1 ? 's' : '');
                    } elseif ($diff->days > 30) {
                        echo floor($diff->days / 30) . ' month' . (floor($diff->days / 30) > 1 ? 's' : '');
                    } else {
                        echo $diff->days . ' day' . ($diff->days != 1 ? 's' : '');
                    }
                    ?>
                </div>
            </div>

            <?php if ($orders_table_exists): ?>
            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-shopping-bag"></i> Total Orders
                </div>
                <div class="detail-value"><?php echo $order_count; ?></div>
            </div>

            <?php if ($total_spent > 0): ?>
            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-dollar-sign"></i> Total Spent
                </div>
                <div class="detail-value">₹<?php echo number_format($total_spent, 2); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <i class="fas fa-chart-line"></i> Average Order Value
                </div>
                <div class="detail-value">
                    ₹<?php echo $order_count > 0 ? number_format($total_spent / $order_count, 2) : '0.00'; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($recent_orders)): ?>
    <div class="recent-orders-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Orders</h3>
            <!-- Modified to ensure proper filtering by user -->
            <a href="index.php?page=orders&filter_user_id=<?php echo $user_id; ?>&username=<?php echo urlencode($user['username']); ?>" class="view-all-btn">
                View All Orders for <?php echo htmlspecialchars($user['username']); ?>
            </a>
        </div>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <a href="index.php?page=order_details&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<style>
.page-header .header-content {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

.back-btn {
    background: #6c757d;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
}

.user-details-container {
    display: grid;
    gap: 30px;
}

.user-info-card,
.recent-orders-card,
.actions-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-info-card .card-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-avatar {
    font-size: 60px;
    color: #3498db;
}

.user-basic-info h2 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.user-id {
    color: #7f8c8d;
    margin: 0 0 10px 0;
    font-size: 14px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.completed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.user-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.detail-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fafafa;
}

.detail-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 8px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.view-all-btn {
    background: #3498db;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    white-space: nowrap;
}

.view-all-btn:hover {
    background: #2980b9;
    color: white;
}

.orders-table {
    overflow-x: auto;
}

.orders-table table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.orders-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.orders-table tbody tr:hover {
    background: #f8f9fa;
}

.action-buttons {
    padding: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 12px;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    color: white;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #d68910;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-view {
    background: #17a2b8;
    color: white;
}

.btn-view:hover {
    background: #138496;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    width: 80%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

.modal h3 {
    margin-top: 0;
    color: #e74c3c;
}

.warning {
    color: #e74c3c;
    font-size: 14px;
    margin: 10px 0;
}

.modal-actions {
    margin-top: 20px;
    text-align: right;
    gap: 10px;
    display: flex;
    justify-content: flex-end;
}

/* Responsive Design */
@media (max-width: 768px) {
    .user-details-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .card-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .user-info-card .card-header {
        flex-direction: row;
        text-align: left;
    }
    
    .view-all-btn {
        font-size: 12px;
        text-align: center;
    }
}
</style>

<script>
function deleteUser(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function resetPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password? They will need to contact support to regain access.')) {
        // You can implement password reset functionality here
        // For now, just show an alert
        alert('Password reset functionality would be implemented here.');
    }
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>