<?php
// admin/pages/edit_category.php
if (!isset($_GET['id'])) {
    header('Location: index.php?page=categories');
    exit;
}
$category_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$category) {
    echo "Category not found.";
    exit;
}
?>
<h1 class="page-title">Edit Category</h1>

<div class="form-container">
    <h2>Update "<?php echo htmlspecialchars($category['name']); ?>"</h2>
    <form action="pages/update_category.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
        <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($category['image_url']); ?>">

        <div class="input-group">
            <label for="name">Category Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
        </div>

        <div class="input-group">
            <label for="category_type">Category Type</label>
            <select name="category_type" id="category_type">
                <option value="standard" <?php echo ($category['category_type'] === 'standard') ? 'selected' : ''; ?>>
                    Standard (Shows a list of products)
                </option>
                <option value="cake_customizer" <?php echo ($category['category_type'] === 'cake_customizer') ? 'selected' : ''; ?>>
                    Cake Customizer (Links to cake page)
                </option>
            </select>
        </div>
        
        <?php if (!empty($category['image_url'])): ?>
            <div class="current-image-preview">
                <p>Current Image:</p>
                <img src="../img/<?php echo htmlspecialchars($category['image_url']); ?>" alt="Category Image">
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
            fileNameSpan.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
        });
    }
</script>