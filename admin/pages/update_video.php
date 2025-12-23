<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $video = $_FILES['video'];
    $upload_dir = '../../videos/';
    $target_file = $upload_dir . 'brownie-ad.mp4'; // We always overwrite this specific file

    // Check if it's an mp4 file
    $video_type = strtolower(pathinfo($video["name"], PATHINFO_EXTENSION));
    if ($video_type != "mp4") {
        header('Location: ../index.php?page=video&status=not_mp4');
        exit;
    }

    // Attempt to move the uploaded file, overwriting the old one
    if (move_uploaded_file($video["tmp_name"], $target_file)) {
        header('Location: ../index.php?page=video&status=updated');
        exit;
    }
}

header('Location: ../index.php?page=video&status=error');
exit;
?>