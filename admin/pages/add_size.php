<?php
// admin/pages/add_size.php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $weight = $_POST['weight_kg'];
    $price = $_POST['base_price'];

    $stmt = $conn->prepare("INSERT INTO cake_sizes (name, weight_kg, base_price) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $name, $weight, $price);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=cake_sizes&status=added');
    exit;
}
?>