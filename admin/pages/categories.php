<?php
// admin/pages/categories.php

// Handle category deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $conn->begin_transaction();
    try {
        // Get category image to delete it from the server
        $cat_img_stmt = $conn->prepare("SELECT image_url FROM categories WHERE id = ?");
        $cat_img_stmt->bind_param("i", $delete_id);
        $cat_img_stmt->execute();
        $category_to_delete = $cat_img_stmt->get_result()->fetch_assoc();
        $cat_img_stmt->close();

        if ($category_to_delete && !empty($category_to_delete['image_url'])) {
            $file_path = '../../img/' . $category_to_delete['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Get product images from the category to delete them
        $prod_img_stmt = $conn->prepare("SELECT image_url FROM products WHERE category_id = ?");
        $prod_img_stmt->bind_param("i", $delete_id);
        $prod_img_stmt->execute();
        $products_to_delete = $prod_img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $prod_img_stmt->close();

        foreach ($products_to_delete as $product) {
            if (!empty($product['image_url'])) {
                 $file_path = '../../' . $product['image_url']; // Assumes product images are in root/img/products/
                 if(file_exists($file_path)) {
                    unlink($file_path);
                 }
            }
        }

        // Delete products in the category
        $delete_products_stmt = $conn->prepare("DELETE FROM products WHERE category_id = ?");
        $delete_products_stmt->bind_param("i", $delete_id);
        $delete_products_stmt->execute();
        $delete_products_stmt->close();

        // Delete the category itself
        $delete_cat_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $delete_cat_stmt->bind_param("i", $delete_id);
        $delete_cat_stmt->execute();
        $delete_cat_stmt->close();

        $conn->commit();
        header('Location: index.php?page=categories&status=deleted');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: index.php?page=categories&status=delete_error');
        exit;
    }
}

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="page-title">Manage Categories</h1>

<div class="form-container-flex">
    <div class="form-container">
        <h2>Add New Category</h2>
        <form action="pages/add_category.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" placeholder="e.g., Brownies, Cookies" required>
            </div>
            
            <div class="input-group">
                <label for="category_type">Category Type</label>
                <select name="category_type" id="category_type">
                    <option value="standard" selected>Standard (Shows a list of products)</option>
                    <option value="cake_customizer">Cake Customizer (Links to cake page)</option>
                </select>
            </div>

            <div class="input-group">
                <label for="image-upload-add">Upload Image</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="image" id="image-upload-add" class="file-input" required>
                    <label for="image-upload-add" class="file-upload-label">Choose File</label>
                    <span class="file-upload-filename">No file selected</span>
                </div>
            </div>
            <button type="submit">Add Category</button>
        </form>
    </div>
</div>


<div class="table-container">
    <h2>Existing Categories</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Type</th> <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="4">No categories found. Add one above.</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <?php if (!empty($cat['image_url'])): ?>
                            <img src="../img/<?php echo htmlspecialchars($cat['image_url']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                        <?php else: ?>
                            <span>No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $cat['category_type'] === 'cake_customizer' ? 'status-preparing' : 'status-delivered'; ?>">
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cat['category_type']))); ?>
                        </span>
                    </td>
                    <td>
                        <a href="index.php?page=edit_category&id=<?php echo $cat['id']; ?>" class="btn-edit">Edit</a>
                        <a href="index.php?page=categories&delete=<?php echo $cat['id']; ?>" class="btn-delete" onclick="return confirm('WARNING: Are you sure you want to delete this category and ALL products inside it? This action cannot be undone.');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    const fileInput = document.getElementById('image-upload-add');
    const fileNameSpan = document.querySelector('.file-upload-filename');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            fileNameSpan.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
        });
    }
</script>