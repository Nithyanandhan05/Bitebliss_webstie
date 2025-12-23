<?php
// admin/pages/update_category.php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category_id'], $_POST['name'], $_POST['category_type'])) {
        $category_id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $category_type = $_POST['category_type']; // Get the new category type
        $old_image = $_POST['old_image'];
        $new_image_url = $old_image;

        if (empty($name) || !in_array($category_type, ['standard', 'cake_customizer'])) {
             header('Location: ../index.php?page=edit_category&id=' . $category_id . '&status=invalid_data');
             exit;
        }

        // Check if a new image has been uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../../img/";
            $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $new_image_url = $image_name;
                // Delete the old image if a new one is successfully uploaded
                if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }
            } else {
                header('Location: ../index.php?page=categories&status=upload_error');
                exit;
            }
        }

        // Update the database with the new data
        $stmt = $conn->prepare("UPDATE categories SET name = ?, image_url = ?, category_type = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $new_image_url, $category_type, $category_id);

        if ($stmt->execute()) {
            header('Location: ../index.php?page=categories&status=updated');
        } else {
            header('Location: ../index.php?page=categories&status=error');
        }
        $stmt->close();
        
    } else {
        header('Location: ../index.php?page=categories&status=missing_data');
    }
} else {
    header('Location: ../index.php?page=categories');
}
exit;
?>