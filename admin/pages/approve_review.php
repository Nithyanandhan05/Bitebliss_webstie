<?php
require_once '../../db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE reviews SET is_approved = 1 WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: ../index.php?page=reviews");
        exit();
    } else {
        echo "Error approving review: " . $conn->error;
    }
}
?>