<?php
// admin/pages/edit_flavor.php
if (!isset($_GET['id'])) {
    header('Location: index.php?page=cake_flavors');
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM cake_flavors WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$flavor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flavor) {
    echo "Flavor not found.";
    exit;
}
?>
<h1 class="page-title">Edit Flavor</h1>
<div class="form-container">
    <form action="pages/update_flavor.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $flavor['id']; ?>">
        <div class="input-group">
            <label>Flavor Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($flavor['name']); ?>" required>
        </div>
        <div class="input-group">
            <label>Additional Price (₹)</label>
            <input type="number" name="additional_price" step="0.01" value="<?php echo $flavor['additional_price']; ?>" required>
        </div>
        <button type="submit">Update Flavor</button>
    </form>
</div>