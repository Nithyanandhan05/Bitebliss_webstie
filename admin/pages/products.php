<?php
// Handle product deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // First, get the image path to delete the file
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        $project_root = dirname(__DIR__); // Assumes this file is in /pages/
        $image_path = $project_root . '/' . $product['image_url'];
        
        if (!empty($product['image_url']) && file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $stmt->close();
    
    // Then, delete the product from the database
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    header('Location: index.php?page=products&status=deleted');
    exit;
}

// Fetch all products and categories for display
$products = $conn->query("SELECT products.*, categories.name as category_name FROM products JOIN categories ON products.category_id = categories.id ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<h1 class="page-title">Manage Products</h1>

<div class="form-container">
    <h2>Add New Product</h2>
    <form action="pages/add_product.php" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="input-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
                <label for="price">Price (₹)</label>
                <input type="number" id="price" step="0.01" name="price" required>
            </div>
            <div class="input-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="" disabled selected>Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
        </div>
        <button type="submit">Add Product</button>
    </form>
</div>

<div class="table-container">
    <h2>Existing Products</h2>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumb"></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <a href="index.php?page=edit_product&id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                        <a href="index.php?page=products&delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product? This cannot be undone.');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 20px;">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>