<?php
// debug_links.php - Run this to check your database and links
session_start();
require_once 'db_connect.php';

echo "<h2>Debug Information for Menu-Product Navigation</h2>";

// Check database connection
if (!$conn) {
    echo "<div style='color: red;'>❌ Database connection failed: " . mysqli_connect_error() . "</div>";
    exit();
} else {
    echo "<div style='color: green;'>✅ Database connection successful</div>";
}

// Check if products table exists
$check_table = $conn->query("SHOW TABLES LIKE 'products'");
if ($check_table->num_rows == 0) {
    echo "<div style='color: red;'>❌ Products table does not exist</div>";
    exit();
} else {
    echo "<div style='color: green;'>✅ Products table exists</div>";
}

// Fetch all products
$sql = "SELECT id, name, price, image_url FROM products ORDER BY id ASC";
$result = $conn->query($sql);

if (!$result) {
    echo "<div style='color: red;'>❌ Query failed: " . $conn->error . "</div>";
    exit();
}

echo "<div style='color: green;'>✅ Query executed successfully</div>";
echo "<p><strong>Found " . $result->num_rows . " products in database:</strong></p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Image URL</th><th>Generated Link</th><th>Test Link</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $product_link = "product.php?id=" . (int)$row['id'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>₹" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['image_url']) . "</td>";
        echo "<td><code>" . $product_link . "</code></td>";
        echo "<td><a href='" . $product_link . "' target='_blank'>Test Link</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange;'>⚠️ No products found in database</div>";
}

// Check file existence
$files_to_check = ['menu.php', 'product.php', 'add_to_cart.php', 'header.php', 'style.css'];
echo "<h3>File Check:</h3>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<div style='color: green;'>✅ $file exists</div>";
    } else {
        echo "<div style='color: red;'>❌ $file missing</div>";
    }
}

// Check URL parameters if coming from product page
if (isset($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];
    echo "<h3>Testing Product ID: $test_id</h3>";
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo "<div style='color: green;'>✅ Product found: " . htmlspecialchars($product['name']) . "</div>";
    } else {
        echo "<div style='color: red;'>❌ Product with ID $test_id not found</div>";
    }
}

// Show current URL and parameters
echo "<h3>Current Request Info:</h3>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>HTTP Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>GET Parameters:</strong> " . (empty($_GET) ? 'None' : print_r($_GET, true)) . "</p>";
echo "<p><strong>Referrer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'None') . "</p>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { text-align: left; }
code { background: #f4f4f4; padding: 2px 4px; }
</style>

<script>
console.log('Debug script loaded');
console.log('Current URL:', window.location.href);
console.log('Referrer:', document.referrer);
</script>