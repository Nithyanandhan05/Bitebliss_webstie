<?php
// admin/pages/add_flavor.php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['additional_price'];

    $stmt = $conn->prepare("INSERT INTO cake_flavors (name, additional_price) VALUES (?, ?)");
    $stmt->bind_param("sd", $name, $price);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=cake_flavors&status=added');
    exit;
}
?>