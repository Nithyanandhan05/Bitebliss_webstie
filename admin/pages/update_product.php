<?php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['admin_loggedin'])) {
    header('Location: ../../index.php');
    exit;
}

$product_id = (int)$_POST['product_id'];
$name = $_POST['name'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$stmt = null; 

if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $image = $_FILES['image'];
    
    $project_root = dirname(__DIR__, 2);
    $target_dir = $project_root . "/img/products/";

    $image_extension = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
    $unique_filename = uniqid('product_', true) . '.' . $image_extension;
    $target_file = $target_dir . $unique_filename;

    $old_image_stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $old_image_stmt->bind_param("i", $product_id);
    $old_image_stmt->execute();
    $result = $old_image_stmt->get_result();
    $old_product = $result->fetch_assoc();
    $old_image_path = $project_root . '/' . $old_product['image_url'];
    $old_image_stmt->close();

    if (move_uploaded_file($image["tmp_name"], $target_file)) {
        if (!empty($old_product['image_url']) && file_exists($old_image_path)) {
            unlink($old_image_path);
        }

        $new_image_url = 'img/products/' . $unique_filename;
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, category_id = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("sdisi", $name, $price, $category_id, $new_image_url, $product_id);
    }

} else {
    // --- NO NEW IMAGE UPLOADED ---
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, category_id = ? WHERE id = ?");
    $stmt->bind_param("sdii", $name, $price, $category_id, $product_id);
}

if ($stmt) {
    $stmt->execute();
    $stmt->close();
    header('Location: ../index.php?page=products&status=updated');
    exit;
} else {
    header('Location: ../index.php?page=edit_product&id=' . $product_id . '&status=upload_error');
    exit;
}
?>