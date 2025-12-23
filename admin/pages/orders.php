<?php
$orders = $conn->query("SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.id ORDER BY order_date DESC")->fetch_all(MYSQLI_ASSOC);

// ==================================
// FETCH ORDERS FROM DATABASE
// ==================================
$orders = []; // Initialize an empty array to store orders

// This SQL query joins the orders, users, and user_addresses tables
// to get all the required information for the display.
$sql = "SELECT
            o.id,
            o.user_id,
            o.address_id,
            o.total_price,
            o.order_status,
            o.order_date,
            o.delivery_address,
            o.phone_number,
            o.razorpay_payment_id,
            u.username,
            ua.full_name AS shipping_name,
            ua.phone_number AS shipping_phone,
            ua.flat_house_no,
            ua.area_street,
            ua.landmark,
            ua.city,
            ua.state,
            ua.pincode
        FROM
            orders AS o
        JOIN
            users AS u ON o.user_id = u.id
        LEFT JOIN
            user_addresses AS ua ON o.address_id = ua.id
        ORDER BY
            o.order_date DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Fetch all results into the $orders array
    while($row = $result->fetch_assoc()) {
        // Determine payment method based on razorpay_payment_id
        $row['payment_method'] = !empty($row['razorpay_payment_id']) ? 'Razorpay' : 'Cash on Delivery';
        $orders[] = $row;
    }
}

// Close the database connection
$conn->close();

// ==================================
// HELPER FUNCTIONS
// ==================================
function getStatusClass($status) {
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}

function formatAddress($order) {
    // This function prioritizes the detailed address from user_addresses
    if (!empty($order['flat_house_no'])) {
        $address = '<div class="address-name">' . htmlspecialchars($order['shipping_name']) . '</div>';
        $address .= '<div class="address-line">' . htmlspecialchars($order['flat_house_no']) . ', ' . htmlspecialchars($order['area_street']);
        if (!empty($order['landmark'])) {
            $address .= ', ' . htmlspecialchars($order['landmark']);
        }
        $address .= '</div>';
        $address .= '<div class="address-location">' . htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ' - ' . htmlspecialchars($order['pincode']) . '</div>';
        return $address;
    }
    // Falls back to the delivery_address text block if no saved address was used
    if (!empty($order['delivery_address'])) {
        // Clean up the address and format it better
        $lines = explode("\n", $order['delivery_address']);
        $formatted = '';
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (!empty($line)) {
                if ($index === 0) {
                    $formatted .= '<div class="address-name">' . htmlspecialchars($line) . '</div>';
                } else {
                    $formatted .= '<div class="address-line">' . htmlspecialchars($line) . '</div>';
                }
            }
        }
        return $formatted ?: 'No address provided';
    }
    return 'No address provided';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Manage Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ================================== */
        /* MOBILE-FIRST RESPONSIVE DESIGN     */
        /* ================================== */
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #e0e7ff;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-weight-normal: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ================================== */
        /* CONTAINER & LAYOUT                 */
        /* ================================== */
        .orders-container {
            padding: 16px;
            max-width: 100%;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: var(--font-weight-bold);
            color: var(--primary-color);
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .page-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .orders-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-medium);
            font-size: 0.875rem;
        }

        /* ================================== */
        /* MOBILE CARD LAYOUT (DEFAULT)      */
        /* ================================== */
        .orders-list {
            display: block;
        }

        .order-card {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .order-card-header {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary-light) 0%, #f1f5f9 100%);
        }

        .order-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .order-id {
            font-size: 1.1rem;
            font-weight: var(--font-weight-bold);
            color: var(--primary-color);
        }

        .order-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
        }

        .order-total {
            font-size: 1.25rem;
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            text-align: right;
        }

        .payment-method {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .order-card-body {
            padding: 16px;
        }

        .order-section {
            margin-bottom: 16px;
        }

        .order-section:last-child {
            margin-bottom: 0;
        }

        .section-label {
            font-size: 0.75rem;
            font-weight: var(--font-weight-semibold);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-name {
            font-weight: var(--font-weight-medium);
            color: var(--text-primary);
            flex: 1;
        }

        .customer-phone {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--bg-color);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: var(--font-weight-medium);
            transition: all 0.2s ease;
        }

        .customer-phone:hover {
            background: var(--primary-light);
        }

        .address-content {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .address-name {
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .address-line {
            color: var(--text-secondary);
            margin-bottom: 1px;
        }

        .address-location {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .order-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        /* ================================== */
        /* STATUS SELECT                      */
        /* ================================== */
        .status-select {
            flex: 1;
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-color);
            color: var(--text-primary);
            font-weight: var(--font-weight-medium);
            font-size: 16px; /* Prevent iOS zoom */
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .status-preparing {
            border-color: var(--warning-color);
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-out-for-delivery {
            border-color: var(--info-color);
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            border-color: var(--success-color);
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            border-color: var(--danger-color);
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* ================================== */
        /* VIEW ITEMS BUTTON                  */
        /* ================================== */
        .view-items-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            font-weight: var(--font-weight-medium);
            font-size: 16px; /* Prevent iOS zoom */
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 44px; /* Touch target */
            justify-content: center;
        }

        .view-items-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .view-items-btn:active {
            transform: translateY(0);
        }

        /* ================================== */
        /* DESKTOP TABLE (HIDDEN ON MOBILE)  */
        /* ================================== */
        .desktop-table {
            display: none;
        }

        /* ================================== */
        /* MODAL STYLES                       */
        /* ================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--surface-color);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-color);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.25rem;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--text-muted);
            color: white;
        }

        .modal-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .item-card {
            background: var(--bg-color);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .item-name {
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .item-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .item-customizations {
            margin-top: 12px;
            font-size: 0.875rem;
        }

        .item-customizations ul {
            list-style: none;
            margin-top: 8px;
        }

        .item-customizations li {
            padding: 2px 0;
            color: var(--text-secondary);
        }

        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .loading-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ================================== */
        /* TOAST NOTIFICATIONS               */
        /* ================================== */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            color: white;
            font-weight: var(--font-weight-medium);
            font-size: 0.875rem;
            max-width: 300px;
            word-wrap: break-word;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .toast-success {
            background: var(--success-color);
        }

        .toast-error {
            background: var(--danger-color);
        }

        .toast.show {
            transform: translateX(0);
        }

        /* ================================== */
        /* EMPTY STATE                       */
        /* ================================== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: var(--font-weight-semibold);
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .empty-description {
            font-size: 0.875rem;
        }

        /* ================================== */
        /* TABLET RESPONSIVE (768px+)        */
        /* ================================== */
        @media (min-width: 768px) {
            .orders-container {
                padding: 24px;
                max-width: 1200px;
            }

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: left;
                margin-bottom: 32px;
            }

            .page-title {
                font-size: 2.25rem;
                margin-bottom: 4px;
            }

            .page-subtitle {
                font-size: 1rem;
                margin-bottom: 0;
            }

            .orders-count {
                font-size: 1rem;
                padding: 10px 20px;
            }

            .order-card {
                margin-bottom: 20px;
            }

            .order-card-header {
                padding: 20px;
            }

            .order-card-body {
                padding: 20px;
            }

            .order-section {
                margin-bottom: 20px;
            }

            .order-actions {
                flex-direction: row;
                gap: 16px;
            }

            .view-items-btn {
                flex: 0 0 auto;
                min-width: 140px;
            }

            /* Modal adjustments for tablet */
            .modal-overlay {
                align-items: center;
            }

            .modal-content {
                border-radius: var(--radius-xl);
                max-width: 600px;
                max-height: 80vh;
                transform: scale(0.9) translateY(20px);
            }

            .modal-overlay.active .modal-content {
                transform: scale(1) translateY(0);
            }
        }

        /* ================================== */
        /* DESKTOP RESPONSIVE (1024px+)      */
        /* ================================== */
        @media (min-width: 1024px) {
            .orders-container {
                padding: 32px;
            }

            /* Hide mobile cards, show desktop table */
            .orders-list {
                display: none;
            }

            .desktop-table {
                display: block;
                background: var(--surface-color);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-md);
                overflow: hidden;
            }

            .orders-table {
                width: 100%;
                border-collapse: collapse;
            }

            .orders-table th {
                background: var(--bg-color);
                padding: 16px;
                text-align: left;
                font-weight: var(--font-weight-semibold);
                font-size: 0.875rem;
                color: var(--text-secondary);
                border-bottom: 1px solid var(--border-color);
            }

            .orders-table td {
                padding: 16px;
                border-bottom: 1px solid var(--border-color);
                vertical-align: top;
            }

            .orders-table tbody tr:hover {
                background: var(--bg-color);
            }

            .orders-table tbody tr:last-child td {
                border-bottom: none;
            }

            /* Column widths */
            .orders-table th:nth-child(1) { width: 12%; } /* Order */
            .orders-table th:nth-child(2) { width: 18%; } /* Customer */
            .orders-table th:nth-child(3) { width: 35%; } /* Address */
            .orders-table th:nth-child(4) { width: 15%; } /* Total */
            .orders-table th:nth-child(5) { width: 12%; } /* Status */
            .orders-table th:nth-child(6) { width: 8%; }  /* Actions */

            .desktop-order-id {
                font-weight: var(--font-weight-bold);
                color: var(--primary-color);
                margin-bottom: 4px;
            }

            .desktop-order-date {
                font-size: 0.8rem;
                color: var(--text-muted);
            }

            .desktop-customer {
                margin-bottom: 8px;
            }

            .desktop-customer-name {
                font-weight: var(--font-weight-medium);
                margin-bottom: 4px;
            }

            .desktop-total {
                font-weight: var(--font-weight-bold);
                font-size: 1.1rem;
                margin-bottom: 4px;
            }

            .desktop-payment {
                font-size: 0.8rem;
                color: var(--text-secondary);
            }

            .desktop-status-select {
                width: 100%;
                padding: 8px 10px;
                font-size: 14px;
                padding-right: 32px;
            }

            .desktop-view-btn {
                padding: 8px 16px;
                font-size: 14px;
                min-height: 36px;
            }

            /* Modal adjustments for desktop */
            .modal-content {
                max-width: 700px;
            }
        }

        /* ================================== */
        /* LARGE DESKTOP (1440px+)           */
        /* ================================== */
        @media (min-width: 1440px) {
            .orders-container {
                max-width: 1400px;
                padding: 40px;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .desktop-table {
                border-radius: var(--radius-xl);
            }

            .orders-table th,
            .orders-table td {
                padding: 20px;
            }
        }

        /* ================================== */
        /* MOBILE LANDSCAPE OPTIMIZATION     */
        /* ================================== */
        @media (max-height: 500px) and (orientation: landscape) {
            .orders-container {
                padding: 12px;
            }

            .page-header {
                margin-bottom: 16px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .order-card {
                margin-bottom: 12px;
            }

            .order-card-header,
            .order-card-body {
                padding: 12px;
            }

            .modal-content {
                max-height: 90vh;
            }

            .modal-header {
                padding: 16px 20px;
            }

            .modal-body {
                padding: 16px 20px;
            }
        }

        /* ================================== */
        /* PRINT STYLES                      */
        /* ================================== */
        @media print {
            .view-items-btn,
            .modal-overlay,
            .toast {
                display: none !important;
            }

            .orders-list {
                display: none;
            }

            .desktop-table {
                display: block;
            }

            .status-select {
                border: none;
                background: transparent;
                color: inherit;
            }
        }

        /* ================================== */
        /* ACCESSIBILITY IMPROVEMENTS        */
        /* ================================== */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --border-color: #000000;
                --text-muted: #666666;
            }
        }

        /* Focus visible for better accessibility */
        .view-items-btn:focus-visible,
        .status-select:focus-visible,
        .modal-close:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>

<div class="orders-container">
    <header class="page-header">
        <div class="header-content">
            <h1 class="page-title">Manage Orders</h1>
            <p class="page-subtitle">View and update customer orders</p>
        </div>
        <div class="orders-count">
            <i class="fas fa-shopping-bag"></i>
            <?php echo count($orders); ?> Orders
        </div>
    </header>

    <!-- Mobile Card Layout -->
    <div class="orders-list">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="empty-title">No Orders Found</h3>
                <p class="empty-description">Orders will appear here once customers place them.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                <div class="order-card-header">
                    <div class="order-header-top">
                        <div>
                            <div class="order-id">#<?php echo htmlspecialchars($order['id']); ?></div>
                            <div class="order-date">
                                <i class="far fa-clock"></i>
                                <?php echo date("d M Y, h:i A", strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        <div>
                            <div class="order-total">₹<?php echo number_format($order['total_price'], 2); ?></div>
                            <div class="payment-method"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="order-card-body">
                    <div class="order-section">
                        <div class="section-label">Customer</div>
                        <div class="customer-info">
                            <span class="customer-name"><?php echo htmlspecialchars($order['username']); ?></span>
                            <a href="tel:<?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone_number']); ?>" class="customer-phone">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone_number'] ?? 'N/A'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="order-section">
                        <div class="section-label">Delivery Address</div>
                        <div class="address-content">
                            <?php echo formatAddress($order); ?>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <select class="status-select <?php echo getStatusClass($order['order_status']); ?>" 
                                data-order-id="<?php echo $order['id']; ?>"
                                aria-label="Order status for order <?php echo $order['id']; ?>">
                            <option value="Preparing" <?php echo ($order['order_status'] == 'Preparing') ? 'selected' : ''; ?>>Preparing</option>
                            <option value="Out for Delivery" <?php echo ($order['order_status'] == 'Out for Delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="Delivered" <?php echo ($order['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo ($order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        
                        <button class="view-items-btn" 
                                data-order-id="<?php echo $order['id']; ?>"
                                aria-label="View items for order <?php echo $order['id']; ?>">
                            <i class="fas fa-eye"></i>
                            View Items
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table Layout -->
    <div class="desktop-table">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order Details</th>
                    <th>Customer</th>
                    <th>Delivery Address</th>
                    <th>Total & Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <h3 class="empty-title">No Orders Found</h3>
                            <p class="empty-description">Orders will appear here once customers place them.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr data-order-id="<?php echo $order['id']; ?>">
                        <td>
                            <div class="desktop-order-id">#<?php echo htmlspecialchars($order['id']); ?></div>
                            <div class="desktop-order-date">
                                <i class="far fa-clock"></i>
                                <?php echo date("d M Y, h:i A", strtotime($order['order_date'])); ?>
                            </div>
                        </td>
                        <td>
                            <div class="desktop-customer">
                                <div class="desktop-customer-name"><?php echo htmlspecialchars($order['username']); ?></div>
                                <a href="tel:<?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone_number']); ?>" class="customer-phone">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone_number'] ?? 'N/A'); ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="address-content">
                                <?php echo formatAddress($order); ?>
                            </div>
                        </td>
                        <td>
                            <div class="desktop-total">₹<?php echo number_format($order['total_price'], 2); ?></div>
                            <div class="desktop-payment"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                        </td>
                        <td>
                            <select class="status-select desktop-status-select <?php echo getStatusClass($order['order_status']); ?>" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    aria-label="Order status for order <?php echo $order['id']; ?>">
                                <option value="Preparing" <?php echo ($order['order_status'] == 'Preparing') ? 'selected' : ''; ?>>Preparing</option>
                                <option value="Out for Delivery" <?php echo ($order['order_status'] == 'Out for Delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="Delivered" <?php echo ($order['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo ($order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                        <td>
                            <button class="view-items-btn desktop-view-btn" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    aria-label="View items for order <?php echo $order['id']; ?>">
                                <i class="fas fa-eye"></i>
                                View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Order Items -->
<div id="itemsModal" class="modal-overlay" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Order Items</h2>
            <button id="modalClose" class="modal-close" aria-label="Close modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading order items...</p>
            </div>
        </div>
    </div>
</div>

<script>
// ==================================
// MOBILE-OPTIMIZED JAVASCRIPT
// ==================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing mobile-optimized order management...');

    // ==================================
    // MODAL MANAGER
    // ==================================
    const ModalManager = {
        modal: document.getElementById('itemsModal'),
        modalTitle: document.getElementById('modalTitle'),
        modalBody: document.getElementById('modalBody'),
        modalClose: document.getElementById('modalClose'),
        
        init() {
            console.log('Initializing ModalManager...');
            
            // Close button event
            this.modalClose?.addEventListener('click', (e) => {
                e.preventDefault();
                this.close();
            });
            
            // Backdrop click to close
            this.modal?.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.close();
                }
            });
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modal?.classList.contains('active')) {
                    this.close();
                }
            });
            
            // Initialize view item buttons
            this.initViewItemButtons();
        },
        
        initViewItemButtons() {
            const buttons = document.querySelectorAll('.view-items-btn');
            console.log(`Found ${buttons.length} view item buttons`);
            
            buttons.forEach((button, index) => {
                console.log(`Initializing button ${index + 1}`);
                
                // Remove existing listeners to prevent duplicates
                button.replaceWith(button.cloneNode(true));
                const newButton = document.querySelectorAll('.view-items-btn')[index];
                
                newButton.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const orderId = newButton.getAttribute('data-order-id');
                    console.log(`View items clicked for order: ${orderId}`);
                    
                    if (orderId) {
                        await this.open(orderId);
                    } else {
                        console.error('No order ID found on button');
                        this.showToast('Error: No order ID found', 'error');
                    }
                });
                
                // Touch feedback
                newButton.addEventListener('touchstart', (e) => {
                    newButton.style.transform = 'translateY(0) scale(0.98)';
                }, { passive: true });
                
                newButton.addEventListener('touchend', (e) => {
                    setTimeout(() => {
                        newButton.style.transform = '';
                    }, 150);
                }, { passive: true });
            });
        },
        
        async open(orderId) {
            console.log(`Opening modal for order: ${orderId}`);
            
            if (!orderId) {
                console.error('No order ID provided');
                return;
            }
            
            // Set modal content
            this.modalTitle.textContent = `Items for Order #${orderId}`;
            this.modalBody.innerHTML = `
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading order items...</p>
                </div>
            `;
            
            // Show modal
            this.modal.setAttribute('aria-hidden', 'false');
            this.modal.classList.add('active');
            
            // Prevent background scroll
            document.body.style.overflow = 'hidden';
            
            // Focus management
            setTimeout(() => {
                this.modalClose?.focus();
            }, 300);
            
            try {
                await this.loadOrderItems(orderId);
            } catch (error) {
                console.error('Failed to load order items:', error);
                this.modalBody.innerHTML = `
                    <div class="loading-state">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--danger-color); margin-bottom: 16px;"></i>
                        <p>Error: Could not fetch order items.</p>
                        <p style="font-size: 0.8rem; margin-top: 8px; opacity: 0.7;">Please check your connection and try again.</p>
                    </div>
                `;
            }
        },
        
        async loadOrderItems(orderId) {
            console.log(`Loading items for order: ${orderId}`);
            
            try {
                const response = await fetch(`pages/get_admin_order_details.php?order_id=${encodeURIComponent(orderId)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType?.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }

                const data = await response.json();
                console.log('Received data:', data);
                
                if (data.status === 'success' && data.items?.length > 0) {
                    this.renderItems(data.items);
                } else {
                    this.modalBody.innerHTML = `
                        <div class="loading-state">
                            <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--info-color); margin-bottom: 16px;"></i>
                            <p>${data.message || 'No items found for this order.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Failed to load order items:', error);
                throw error;
            }
        },
        
        close() {
            console.log('Closing modal');
            
            this.modal?.classList.remove('active');
            this.modal?.setAttribute('aria-hidden', 'true');
            
            // Restore background scroll
            document.body.style.overflow = '';
            
            // Return focus to the button that opened the modal
            const activeButton = document.querySelector('.view-items-btn:focus');
            if (activeButton) {
                activeButton.focus();
            }
        },
        
        renderItems(items) {
            console.log(`Rendering ${items.length} items`);
            
            let html = '';
            items.forEach((item, index) => {
                let customizationsHtml = '';
                if (item.customizations) {
                    try {
                        const customs = JSON.parse(item.customizations);
                        customizationsHtml += `
                            <div class="item-customizations">
                                <strong>Customizations:</strong>
                                <ul>
                        `;
                        for (const [key, value] of Object.entries(customs)) {
                            const displayValue = Array.isArray(value) ? value.join(', ') : value;
                            customizationsHtml += `<li><strong>${this.escapeHtml(key)}:</strong> ${this.escapeHtml(displayValue)}</li>`;
                        }
                        customizationsHtml += `
                                </ul>
                            </div>
                        `;
                    } catch (e) { 
                        customizationsHtml = `
                            <div class="item-customizations">
                                <strong>Customizations:</strong> ${this.escapeHtml(item.customizations)}
                            </div>
                        `;
                    }
                }

                html += `
                    <div class="item-card">
                        <div class="item-name">${this.escapeHtml(item.product_name)}</div>
                        <div class="item-details"><strong>Quantity:</strong> ${this.escapeHtml(item.quantity)}</div>
                        <div class="item-details"><strong>Price:</strong> ₹${parseFloat(item.price).toFixed(2)}</div>
                        ${customizationsHtml}
                    </div>
                `;
            });
            
            this.modalBody.innerHTML = html;
        },
        
        escapeHtml(str) {
            if (typeof str !== 'string') return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.toast').forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };

    // ==================================
    // ORDER STATUS MANAGER
    // ==================================
    const OrderStatusManager = {
        init() {
            console.log('Initializing OrderStatusManager...');
            
            const selects = document.querySelectorAll('.status-select');
            console.log(`Found ${selects.length} status select elements`);
            
            selects.forEach((select, index) => {
                console.log(`Initializing status select ${index + 1}`);
                
                // Store original value for rollback
                select.setAttribute('data-original-value', select.value);
                
                // Remove existing listeners
                select.replaceWith(select.cloneNode(true));
                const newSelect = document.querySelectorAll('.status-select')[index];
                
                newSelect.addEventListener('change', async (e) => {
                    console.log('Status change detected:', e.target.value);
                    await this.handleStatusChange(e);
                });
                
                // Prevent iOS zoom
                newSelect.addEventListener('focus', (e) => {
                    if (window.innerWidth < 768) {
                        e.target.style.fontSize = '16px';
                    }
                }, { passive: true });
                
                newSelect.addEventListener('blur', (e) => {
                    e.target.style.fontSize = '';
                }, { passive: true });
            });
        },

        async handleStatusChange(event) {
            const select = event.target;
            const orderId = select.getAttribute('data-order-id');
            const newStatus = select.value;
            const originalValue = select.getAttribute('data-original-value');
            
            console.log(`Updating order ${orderId} status to: ${newStatus}`);
            
            if (!orderId) {
                console.error('No order ID found on select element');
                ModalManager.showToast('Error: No order ID found', 'error');
                return;
            }
            
            // Visual feedback - disable select and show loading
            select.disabled = true;
            select.style.opacity = '0.6';
            select.style.cursor = 'wait';
            
            try {
                await this.updateOrderStatus(orderId, newStatus);
                
                // Success - update the stored original value and visual state
                select.setAttribute('data-original-value', newStatus);
                select.className = select.className.replace(/status-\S+/g, '') + ' ' + this.getStatusClass(newStatus);
                
                ModalManager.showToast('Status updated successfully!', 'success');
                console.log(`Order ${orderId} status updated to: ${newStatus}`);
                
            } catch (error) {
                console.error('Status update failed:', error);
                
                // Rollback on error
                select.value = originalValue;
                select.className = select.className.replace(/status-\S+/g, '') + ' ' + this.getStatusClass(originalValue);
                
                ModalManager.showToast(`Failed to update status: ${error.message}`, 'error');
            } finally {
                // Always restore select state
                select.disabled = false;
                select.style.opacity = '';
                select.style.cursor = '';
            }
        },
        
        async updateOrderStatus(orderId, newStatus) {
            console.log(`Updating order ${orderId} to status: ${newStatus}`);
            
            const response = await fetch('pages/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ 
                    order_id: orderId, 
                    status: newStatus 
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType?.includes('application/json')) {
                throw new Error('Server returned invalid response format');
            }

            const data = await response.json();
            
            if (data.status === 'success') {
                return data;
            } else {
                throw new Error(data.message || 'Unknown server error');
            }
        },
        
        getStatusClass(status) {
            return 'status-' + status.toLowerCase().replace(/\s+/g, '-');
        }
    };

    // ==================================
    // INITIALIZATION
    // ==================================
    try {
        ModalManager.init();
        OrderStatusManager.init();
        console.log('All managers initialized successfully');
    } catch (error) {
        console.error('Failed to initialize managers:', error);
    }

    // ==================================
    // RESPONSIVE HANDLERS
    // ==================================
    let resizeTimeout;
    
    window.addEventListener('orientationchange', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            console.log('Orientation changed, reinitializing...');
            ModalManager.initViewItemButtons();
            OrderStatusManager.init();
        }, 500);
    });

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            console.log('Window resized');
            // Handle any resize-related updates
        }, 250);
    });

    // ==================================
    // ERROR HANDLING
    // ==================================
    window.addEventListener('error', (e) => {
        console.error('Global error:', e.error);
    });

    window.addEventListener('unhandledrejection', (e) => {
        console.error('Unhandled promise rejection:', e.reason);
    });

    // ==================================
    // PERFORMANCE OPTIMIZATION
    // ==================================
    // Preload critical resources
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            console.log('Performing idle optimizations...');
            // Preload any heavy resources here
        });
    }

    console.log('Mobile-optimized order management fully initialized');
});
</script>

</body>
</html>