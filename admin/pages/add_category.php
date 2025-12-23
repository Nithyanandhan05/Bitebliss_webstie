<?php
// admin/pages/add_category.php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_type = $_POST['category_type']; // Get the new category type
    $image_url = null;

    if (empty($name) || !in_array($category_type, ['standard', 'cake_customizer'])) {
        header('Location: ../index.php?page=categories&status=invalid_data');
        exit;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../../img/";
        $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        // Basic validation for image type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            header('Location: ../index.php?page=categories&status=invalid_image_type');
            exit;
        }

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $image_name;
        } else {
            header('Location: ../index.php?page=categories&status=upload_error');
            exit;
        }
    }

    // Updated SQL statement to include 'category_type'
    $stmt = $conn->prepare("INSERT INTO categories (name, image_url, category_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $image_url, $category_type);
    $stmt->execute();
    $stmt->close();

    header('Location: ../index.php?page=categories&status=added');
    exit;
}
?>