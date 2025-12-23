<a href="cart.php" class="floating-cart-bar <?php echo ($cart_item_count > 0) ? 'visible' : ''; ?>">
    <div class="cart-info">
        <span class="cart-badge"><?php echo $cart_item_count; ?> Item<?php echo ($cart_item_count !== 1) ? 's' : ''; ?></span>
        <span class="view-cart-text">View Cart <i class="fas fa-arrow-right"></i></span>
    </div>
</a>