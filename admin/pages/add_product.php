<?php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['admin_loggedin'])) {
    header('Location: ../../index.php');
    exit;
}

// Get form data
$name = $_POST['name'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$image_url = '';

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $image = $_FILES['image'];
    
    // Define the target directory relative to the project root
    $project_root = dirname(__DIR__, 2);
    $target_dir = $project_root . "/img/products/";
    
    // Ensure the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Create a unique filename to prevent overwriting
    $image_extension = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
    $unique_filename = uniqid('product_', true) . '.' . $image_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($image["tmp_name"], $target_file)) {
        // Set the database-friendly path
        $image_url = 'img/products/' . $unique_filename;
    }
}

// Insert into database
if (!empty($name) && !empty($price) && !empty($category_id) && !empty($image_url)) {
    $stmt = $conn->prepare("INSERT INTO products (name, price, category_id, image_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdis", $name, $price, $category_id, $image_url);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=products&status=added');
    exit;
} else {
    header('Location: ../index.php?page=products&status=error');
    exit;
}
?>