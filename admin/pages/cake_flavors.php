<?php
// admin/pages/cake_flavors.php

// Handle flavor deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM cake_flavors WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?page=cake_flavors&status=deleted');
    exit;
}

// Fetch all flavors
$flavors = $conn->query("SELECT * FROM cake_flavors ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="page-title">Manage Cake Flavors</h1>

<div class="form-container-flex">
    <div class="form-container">
        <h2>Add New Flavor</h2>
        <form action="pages/add_flavor.php" method="POST">
            <div class="input-group">
                <label for="name">Flavor Name</label>
                <input type="text" name="name" placeholder="e.g., Classic Chocolate Fudge" required>
            </div>
            <div class="input-group">
                <label for="additional_price">Additional Price (₹)</label>
                <input type="number" name="additional_price" step="0.01" placeholder="e.g., 50.00" required>
            </div>
            <button type="submit">Add Flavor</button>
        </form>
    </div>
</div>

<div class="table-container">
    <h2>Existing Flavors</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Additional Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($flavors)): ?>
                <tr><td colspan="3">No flavors found. Add one above.</td></tr>
            <?php else: ?>
                <?php foreach ($flavors as $flavor): ?>
                <tr>
                    <td><?php echo htmlspecialchars($flavor['name']); ?></td>
                    <td>₹<?php echo number_format($flavor['additional_price'], 2); ?></td>
                    <td>
                        <a href="index.php?page=edit_flavor&id=<?php echo $flavor['id']; ?>" class="btn-edit">Edit</a>
                        <a href="index.php?page=cake_flavors&delete=<?php echo $flavor['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this flavor?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>