<?php
// admin/pages/update_flavor.php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['additional_price'];

    $stmt = $conn->prepare("UPDATE cake_flavors SET name = ?, additional_price = ? WHERE id = ?");
    $stmt->bind_param("sdi", $name, $price, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=cake_flavors&status=updated');
    exit;
}
?>