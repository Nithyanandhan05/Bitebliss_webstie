<?php
// Session is started in db_connect.php
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cart_count += $item['quantity'];
        }
    }
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header>
    <div class="logo-container">
        <a href="index.php">
            <img src="img/logo.png" alt="Bite Bliss Logo">
        </a>
    </div>
    <nav class="animated-nav">
        <div class="nav-left">
            <a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>"></a>
            <a href="menu.php" class="<?php echo $currentPage == 'menu.php' ? 'active' : ''; ?>"></a>
        </div>
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="nav-icon" title="Profile"><i class="fas fa-user"></i></a>
            <?php else: ?>
                <a href="login.php" class="nav-icon" title="Login"><i class="fas fa-user"></i></a>
            <?php endif; ?>
            
            <a href="cart.php" class="nav-icon cart-icon">
                <i class="fas fa-shopping-bag"></i>
                <span style="<?php echo ($cart_count > 0) ? 'display:flex;' : 'display:none;'; ?>"><?php echo $cart_count; ?></span>
            </a>
        </div>
    </nav>
</header>