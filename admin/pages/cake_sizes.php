<?php
// admin/pages/cake_sizes.php

// Handle size deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM cake_sizes WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?page=cake_sizes&status=deleted');
    exit;
}

// Fetch all sizes
$sizes = $conn->query("SELECT * FROM cake_sizes ORDER BY weight_kg ASC")->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="page-title">Manage Cake Sizes</h1>

<div class="form-container-flex">
    <div class="form-container">
        <h2>Add New Size</h2>
        <form action="pages/add_size.php" method="POST">
            <div class="input-group">
                <label for="name">Size Name</label>
                <input type="text" name="name" placeholder="e.g., 1/2 kg" required>
            </div>
            <div class="input-group">
                <label for="weight_kg">Weight (in kg)</label>
                <input type="number" name="weight_kg" step="0.1" placeholder="e.g., 0.5" required>
            </div>
            <div class="input-group">
                <label for="base_price">Base Price (₹)</label>
                <input type="number" name="base_price" step="0.01" placeholder="e.g., 500.00" required>
            </div>
            <button type="submit">Add Size</button>
        </form>
    </div>
</div>

<div class="table-container">
    <h2>Existing Sizes</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Weight</th>
                <th>Base Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sizes)): ?>
                <tr><td colspan="4">No sizes found. Add one above.</td></tr>
            <?php else: ?>
                <?php foreach ($sizes as $size): ?>
                <tr>
                    <td><?php echo htmlspecialchars($size['name']); ?></td>
                    <td><?php echo htmlspecialchars($size['weight_kg']); ?> kg</td>
                    <td>₹<?php echo number_format($size['base_price'], 2); ?></td>
                    <td>
                        <a href="index.php?page=edit_size&id=<?php echo $size['id']; ?>" class="btn-edit">Edit</a>
                        <a href="index.php?page=cake_sizes&delete=<?php echo $size['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this size?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>