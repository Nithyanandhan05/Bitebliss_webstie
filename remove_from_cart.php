<?php
session_start();
if (isset($_GET['item_key']) && isset($_SESSION['cart'][$_GET['item_key']])) {
    unset($_SESSION['cart'][$_GET['item_key']]);
}
header('Location: cart.php');
exit();
?>