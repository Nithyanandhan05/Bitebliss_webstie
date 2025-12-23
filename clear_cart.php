<?php
//  This is a temporary script to clear your cart.
//  Access it once in your browser, then you can delete this file.
session_start();

// Unset the specific cart session variable
unset($_SESSION['cart']);

// Redirect back to the menu with a confirmation message
header('Location: menu.php?cart_cleared=true');
exit();
?>