<?php
session_start();
// If admin is not logged in, redirect to the main site's login
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: ../index.php'); // Go up one directory to the main site
    exit;
}
require_once '../db_connect.php'; // Go up one directory for the connection file

// Determine which page to show
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bite Bliss</title>
    <link rel="stylesheet" href="style.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../img/logo.png" alt="Logo" class="logo">
                <h2>Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php?page=dashboard" class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="index.php?page=orders" class="<?php echo ($page == 'orders') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="index.php?page=payments" class="<?php echo ($page == 'payments') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="index.php?page=products" class="<?php echo ($page == 'products') ? 'active' : ''; ?>"><i class="fas fa-box-open"></i> Products</a></li>
                <li><a href="index.php?page=categories" class="<?php echo ($page == 'categories') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="index.php?page=cake_flavors" class="<?php echo ($page == 'cake_flavors' || $page == 'edit_flavor') ? 'active' : ''; ?>"><i class="fas fa-cookie-bite"></i> Cake Flavors</a></li>
<li><a href="index.php?page=cake_sizes" class="<?php echo ($page == 'cake_sizes' || $page == 'edit_size') ? 'active' : ''; ?>"><i class="fas fa-weight-hanging"></i> Cake Sizes</a></li>
                <li><a href="index.php?page=users" class="<?php echo ($page == 'users' || $page == 'view_user') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php?page=ads" class="<?php echo ($page == 'ads') ? 'active' : ''; ?>"><i class="fas fa-images"></i> Ad Slides</a></li>
                <li><a href="index.php?page=video" class="<?php echo ($page == 'video') ? 'active' : ''; ?>"><i class="fas fa-video"></i> Promo Video</a></li>
                <li><a href="index.php?page=reviews" class="<?php echo ($page == 'reviews') ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reviews</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?php
            // Include the correct page based on the URL
            $allowed_pages = ['dashboard', 'orders', 'payments', 'products', 'categories', 'ads', 'video', 'edit_product', 'edit_category', 'users', 'view_user','reviews', 'cake_flavors', 'cake_sizes', 'edit_flavor', 'edit_size'];
            if (in_array($page, $allowed_pages)) {
                include 'pages/' . $page . '.php';
            } else {
                include 'pages/dashboard.php'; // Default to dashboard
            }
            ?>
        </main>
    </div>
</body>
</html>