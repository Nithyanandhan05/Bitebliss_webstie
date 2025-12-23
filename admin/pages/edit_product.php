<?php
// Ensure you have a database connection here
// require_once 'db_connect.php';

// Get the ID of the category to edit
if (!isset($_GET['id'])) {
    header('Location: index.php?page=categories');
    exit;
}
$category_id = (int)$_GET['id'];

// Fetch the category's current details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    echo "Category not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="stylesheet" href="path/to/your/css/admin_style.css">
</head>
<body>

    <h1 class="page-title">Edit Category</h1>

    <div class="form-container">
        <h2>Update "<?php echo htmlspecialchars($category['name']); ?>"</h2>
        <form action="pages/update_category.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
            <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($category['image_url']); ?>">

            <div class="input-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>

            <?php if (!empty($category['image_url'])): ?>
                <div class="current-image-preview">
                    <p>Current Image:</p>
                    <img src="img/<?php echo htmlspecialchars($category['image_url']); ?>" alt="Category Image">
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label for="image-upload-edit">Upload New Image (Optional)</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="image" id="image-upload-edit" class="file-input">
                    <label for="image-upload-edit" class="file-upload-label">Choose File</label>
                    <span class="file-upload-filename">No file selected</span>
                </div>
            </div>
            
            <button type="submit">Update Category</button>
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('image-upload-edit');
        const fileNameSpan = document.querySelector('.file-upload-filename');
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    fileNameSpan.textContent = fileInput.files[0].name;
                } else {
                    fileNameSpan.textContent = 'No file selected';
                }
            });
        }
    </script>
</body>
</html>