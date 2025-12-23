<?php
// Handle slide deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // First, delete the image file from the server
    $stmt = $conn->prepare("SELECT image_url FROM slider_images WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($slide = $result->fetch_assoc()) {
        if (file_exists('../' . $slide['image_url'])) {
            unlink('../' . $slide['image_url']);
        }
    }
    $stmt->close();
    
    // Then, delete the slide from the database
    $delete_stmt = $conn->prepare("DELETE FROM slider_images WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header('Location: index.php?page=ads&status=deleted');
    exit;
}

// Fetch all existing slides
$slides = $conn->query("SELECT * FROM slider_images ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1 class="page-title">Manage Ad Slides</h1>

<div class="form-container">
    <h2>Add New Slide</h2>
    <form action="pages/add_slide.php" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="input-group">
                <label for="title">Title (e.g., "Gourmet Brownies")</label>
                <input type="text" name="title" required>
            </div>
            <div class="input-group">
                <label for="subtitle">Subtitle (e.g., "Handcrafted daily")</label>
                <input type="text" name="subtitle" required>
            </div>
        </div>
        <div class="input-group">
            <label for="image">Slide Image (Recommended size: 1920x1080px)</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <button type="submit">Add Slide</button>
    </form>
</div>

<div class="table-container">
    <h2>Existing Slides</h2>
    <div class="slides-grid">
        <?php foreach ($slides as $slide): ?>
            <div class="slide-card">
                <img src="../<?php echo htmlspecialchars($slide['image_url']); ?>" alt="Slide Image">
                <div class="slide-info">
                    <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                    <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                    <a href="index.php?page=ads&delete=<?php echo $slide['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this slide?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>