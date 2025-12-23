<?php
// admin/pages/update_size.php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    $weight = $_POST['weight_kg'];
    $price = $_POST['base_price'];

    $stmt = $conn->prepare("UPDATE cake_sizes SET name = ?, weight_kg = ?, base_price = ? WHERE id = ?");
    $stmt->bind_param("sddi", $name, $weight, $price, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=cake_sizes&status=updated');
    exit;
}
?>