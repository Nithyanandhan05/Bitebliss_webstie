<?php
// --- STAT CARD QUERIES (MODIFIED FOR "TODAY") ---

// Today's Sales: Shows sum of all orders placed today, regardless of status.
$today_sales_query = $conn->query("SELECT IFNULL(SUM(total_price), 0) as sum FROM orders WHERE DATE(order_date) = CURDATE()");
$today_sales = $today_sales_query->fetch_assoc()['sum'];

// Today's Orders: Shows count of all orders placed today.
$today_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()");
$today_orders = $today_orders_query->fetch_assoc()['count'];

// Preparing Orders: Unchanged, shows all orders currently in 'Preparing' status.
$preparing_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'Preparing'");
$preparing_orders = $preparing_orders_query->fetch_assoc()['count'];


// --- CHART DATA: Fetch data for the sales chart (Last 7 Days) ---
// This part is correct and remains unchanged.
$chart_labels = [];
$chart_data = [];
$sales_by_day = [];

$seven_days_ago = date('Y-m-d', strtotime('-6 days'));
$sales_query = $conn->prepare("
    SELECT DATE(order_date) as order_day, SUM(total_price) as daily_total
    FROM orders
    WHERE order_status = 'Delivered' AND order_date >= ?
    GROUP BY order_day
    ORDER BY order_day ASC
");
$sales_query->bind_param("s", $seven_days_ago);
$sales_query->execute();
$result = $sales_query->get_result();
while ($row = $result->fetch_assoc()) {
    $sales_by_day[$row['order_day']] = $row['daily_total'];
}

// Populate the last 7 days, filling in 0 for days with no sales
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($day)); // Format as "Aug 10"
    $chart_data[] = $sales_by_day[$day] ?? 0;
}


// --- RECENT ORDERS: Fetch recent orders ---
// This part is correct and remains unchanged.
$recent_orders_query = "
    SELECT o.id, o.total_price, o.order_status, o.order_date, u.username
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 5
";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Additional styles for new dashboard elements */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .dashboard-full-width {
        grid-column: 1 / -1; /* Make element span all columns */
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .dashboard-full-width h2 {
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: #333;
    }
    .recent-orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    .recent-orders-table th, .recent-orders-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .recent-orders-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #999;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: bold;
        color: #fff;
    }
    .status-delivered { background-color: #10b981; }
    .status-preparing { background-color: #f59e0b; }
    .status-out-for-delivery { background-color: #3b82f6; }
    .status-cancelled { background-color: #6b7280; }
</style>

<h1 class="page-title">Dashboard</h1>

<div class="dashboard-grid">
    <div class="stat-card">
        <i class="fas fa-shopping-cart"></i>
        <div class="stat-info">
            <p>Today's Orders</p>
            <span><?php echo $today_orders; ?></span>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-dollar-sign"></i>
        <div class="stat-info">
            <p>Today's Sales</p>
            <span>₹<?php echo number_format($today_sales, 2); ?></span>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <div class="stat-info">
            <p>Preparing Orders</p> <span><?php echo $preparing_orders; ?></span>
        </div>
    </div>

    <div class="dashboard-full-width">
        <h2>Sales (Last 7 Days)</h2>
        <canvas id="salesChart"></canvas>
    </div>

    <div class="dashboard-full-width">
        <h2>Recent Orders</h2>
        <div class="table-container" style="box-shadow: none; border: none;">
            <table class="recent-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("d M, Y", strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js configuration
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#975ab7',
                    backgroundColor: 'rgba(151, 90, 183, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>