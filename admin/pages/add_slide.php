<?php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $image = $_FILES['image'];

    // --- Image Upload Logic ---
    $target_dir = "../../img/slides/"; // Go up two directories, then into img/slides/
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $image_extension = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
    $unique_filename = 'slide_' . time() . '.' . $image_extension;
    $target_file = $target_dir . $unique_filename;
    
    $check = getimagesize($image["tmp_name"]);
    if($check !== false) {
        if (move_uploaded_file($image["tmp_name"], $target_file)) {
            $image_url = 'img/slides/' . $unique_filename;
            
            $stmt = $conn->prepare("INSERT INTO slider_images (title, subtitle, image_url) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $subtitle, $image_url);
            $stmt->execute();
            $stmt->close();
            
            header('Location: ../index.php?page=ads&status=added');
            exit;
        }
    }
    
    header('Location: ../index.php?page=ads&status=error');
    exit;
}
?>