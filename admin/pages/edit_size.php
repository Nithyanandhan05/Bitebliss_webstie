<?php
// admin/pages/edit_size.php
if (!isset($_GET['id'])) {
    header('Location: index.php?page=cake_sizes');
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM cake_sizes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$size = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$size) {
    echo "Size not found.";
    exit;
}
?>
<h1 class="page-title">Edit Size</h1>
<div class="form-container">
    <form action="pages/update_size.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $size['id']; ?>">
        <div class="input-group">
            <label>Size Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($size['name']); ?>" required>
        </div>
        <div class="input-group">
            <label>Weight (kg)</label>
            <input type="number" name="weight_kg" step="0.1" value="<?php echo $size['weight_kg']; ?>" required>
        </div>
        <div class="input-group">
            <label>Base Price (₹)</label>
            <input type="number" name="base_price" step="0.01" value="<?php echo $size['base_price']; ?>" required>
        </div>
        <button type="submit">Update Size</button>
    </form>
</div>