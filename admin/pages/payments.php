<?php
// Fetch payment and order data from the database
try {
    $payments = $conn->query("
        SELECT
            o.id AS order_id,
            o.order_date,
            o.total_price,
            o.razorpay_payment_id,
            u.username
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
        LIMIT 200
    ")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $payments = [];
    error_log("Payments query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        /* This uses the same clean design from orders.php */
        :root { --bg-color: #f4f7fa; --surface-color: #ffffff; --primary-color: #16a34a; --primary-light: #dcfce7; --text-color-main: #2d3748; --text-color-light: #718096; --border-color: #e2e8f0; --shadow-color: rgba(0, 0, 0, 0.05); --font-main: 'Inter', sans-serif; --font-display: 'Playfair Display', serif; }
        body { background-color: var(--bg-color); font-family: var(--font-main); color: var(--text-color-main); margin: 0; }
        .page-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-family: var(--font-display); font-size: 2rem; margin: 0; color: var(--primary-color); }
        .page-header .record-count { background-color: var(--primary-light); color: var(--primary-color); padding: 8px 16px; border-radius: 20px; font-weight: 600; }
        .table-wrapper { background-color: var(--surface-color); border-radius: 12px; box-shadow: 0 4px 12px var(--shadow-color); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 16px; text-align: left; }
        .data-table thead { display: none; }
        .data-table th { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-color-light); border-bottom: 2px solid var(--border-color); }
        .data-table tbody tr { display: block; background-color: var(--surface-color); border-radius: 10px; box-shadow: 0 2px 8px var(--shadow-color); margin-bottom: 16px; padding: 16px; }
        .data-table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before { content: attr(data-label); font-weight: 600; margin-right: 16px; color: var(--text-color-main); }
        .data-table td code { background-color: #f3f4f6; padding: 4px 8px; border-radius: 6px; font-size: 0.9em; }
        @media (min-width: 992px) {
            .data-table thead { display: table-header-group; }
            .data-table tbody tr { display: table-row; box-shadow: none; border-radius: 0; padding: 0; margin: 0; }
            .data-table tbody tr:not(:last-child) { border-bottom: 1px solid var(--border-color); }
            .data-table tbody tr:hover { background-color: #f9fafb; }
            .data-table tbody td { display: table-cell; padding: 16px; vertical-align: middle; }
            .data-table tbody td::before { display: none; }
        }
    </style>
</head>
<body>
<div class="page-container">
    <header class="page-header">
        <h1>Payment Records</h1>
        <div class="record-count">
            Total: <?php echo count($payments); ?> Records
        </div>
    </header>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Payment ID</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px;">No payment records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td data-label="Order ID">#<?php echo $payment['order_id']; ?></td>
                        <td data-label="Customer"><?php echo htmlspecialchars($payment['username']); ?></td>
                        <td data-label="Date"><?php echo date("d M Y, h:i A", strtotime($payment['order_date'])); ?></td>
                        <td data-label="Payment ID"><code><?php echo htmlspecialchars($payment['razorpay_payment_id']); ?></code></td>
                        <td data-label="Payment Method">Razorpay</td>
                        <td data-label="Amount"><strong>₹<?php echo number_format($payment['total_price'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>