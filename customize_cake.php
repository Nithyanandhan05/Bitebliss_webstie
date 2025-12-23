<?php
session_start();
require_once 'db_connect.php';

// --- DATA FETCHING ---
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if ($category_id === 0) {
    header('Location: index.php');
    exit();
}

// Fetch category details
$category = null;
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND category_type = 'cake_customizer'");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
} else {
    // If not a valid cake category, redirect
    header('Location: index.php');
    exit();
}
$stmt->close();

// Fetch available cake flavors
$flavors = [];
$flavor_sql = "SELECT * FROM cake_flavors WHERE is_available = 1 ORDER BY name ASC";
$flavor_result = $conn->query($flavor_sql);
if ($flavor_result->num_rows > 0) {
    while($row = $flavor_result->fetch_assoc()) { $flavors[] = $row; }
}

// Fetch available cake sizes
$sizes = [];
$size_sql = "SELECT * FROM cake_sizes WHERE is_available = 1 ORDER BY weight_kg ASC";
$size_result = $conn->query($size_sql);
if ($size_result->num_rows > 0) {
    while($row = $size_result->fetch_assoc()) { $sizes[] = $row; }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bite Bliss</title>
    <link rel="icon" type="image/png" href="img/logo_tag.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="product_details.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body class="product-details-page-body">
    <?php include 'header.php'; ?>

    <main class="details-container">
        <div class="product-details-grid">
            <div class="product-image-gallery">
                <img src="img/<?php echo htmlspecialchars($category['image_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
            </div>

            <div class="product-info-and-customization">
                <h1 class="product-title">Customize Your <?php echo htmlspecialchars($category['name']); ?></h1>
                
                <form id="cakeCustomizationForm" class="customization-form">
                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                    <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($category['name']); ?>">
                    
                    <div class="form-group">
                        <label for="flavor"><i class="fas fa-cookie-bite"></i> Flavor</label>
                        <select id="flavor" name="flavor" class="panel-select">
                            <?php foreach ($flavors as $flavor): ?>
                                <option value="<?php echo $flavor['id']; ?>" data-price="<?php echo $flavor['additional_price']; ?>"><?php echo htmlspecialchars($flavor['name']); ?> (+₹<?php echo number_format($flavor['additional_price'], 2); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="size"><i class="fas fa-weight-hanging"></i> Size</label>
                        <select id="size" name="size" class="panel-select">
                             <?php foreach ($sizes as $size): ?>
                                <option value="<?php echo $size['id']; ?>" data-price="<?php echo $size['base_price']; ?>"><?php echo htmlspecialchars($size['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message"><i class="fas fa-comment-dots"></i> Message on Cake (optional)</label>
                        <textarea id="message" name="message" class="panel-textarea" placeholder="e.g., Happy Birthday!" maxlength="50" rows="2"></textarea>
                    </div>

                    <div class="form-group quantity-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" class="panel-input">
                    </div>

                    <div class="form-footer">
                        <div class="price-container">
                            <span>Total Price</span>
                            <strong id="totalPrice">₹0.00</strong>
                        </div>
                        <button type="submit" class="btn-add-to-cart-details">
                            <span class="btn-text">Add to Cart</span>
                            <span class="loading-icon"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'login_panel.php'; ?>
    <?php include 'floating_cart.php'; ?>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Bite Bliss. All Rights Reserved.</p>
    </footer>

    <script src="script.js?v=2.5"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cakeForm = document.getElementById('cakeCustomizationForm');
        if (!cakeForm) return;

        const flavorSelect = document.getElementById('flavor');
        const sizeSelect = document.getElementById('size');
        const quantityInput = document.getElementById('quantity');
        const totalPriceDisplay = document.getElementById('totalPrice');

        function updateTotalPrice() {
            const selectedFlavor = flavorSelect.options[flavorSelect.selectedIndex];
            const flavorPrice = parseFloat(selectedFlavor.dataset.price) || 0;

            const selectedSize = sizeSelect.options[sizeSelect.selectedIndex];
            const sizePrice = parseFloat(selectedSize.dataset.price) || 0;
            
            const quantity = parseInt(quantityInput.value) || 1;
            
            const total = (sizePrice + flavorPrice) * quantity;
            totalPriceDisplay.textContent = `₹${total.toFixed(2)}`;
        }

        cakeForm.addEventListener('change', updateTotalPrice);
        quantityInput.addEventListener('input', updateTotalPrice);

        cakeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const addButton = this.querySelector('.btn-add-to-cart-details');
            const btnText = addButton.querySelector('.btn-text');
            const loadingIcon = addButton.querySelector('.loading-icon');

            btnText.style.display = 'none';
            loadingIcon.style.display = 'inline-block';
            addButton.disabled = true;

            const formData = new FormData(this);
            const selectedFlavor = flavorSelect.options[flavorSelect.selectedIndex].text;
            const selectedSize = sizeSelect.options[sizeSelect.selectedIndex].text;

            formData.append('product_type', 'custom_cake');
            formData.append('flavor_name', selectedFlavor);
            formData.append('size_name', selectedSize);
            formData.append('total_price', totalPriceDisplay.textContent.replace('₹', ''));
            formData.append('product_name', `Custom ${document.querySelector('input[name=category_name]').value}`);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCountInHeader(data.cart_count);
                    updateFloatingCart(data.cart_count);
                    btnText.textContent = 'Added!';
                    setTimeout(() => { btnText.textContent = 'Add to Cart'; }, 2000);
                } else {
                    alert('Error: ' + data.message);
                    btnText.textContent = 'Add to Cart';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btnText.textContent = 'Add to Cart';
            })
            .finally(() => {
                setTimeout(() => {
                    btnText.style.display = 'inline-block';
                    loadingIcon.style.display = 'none';
                    addButton.disabled = false;
                }, 200);
            });
        });

        updateTotalPrice();
    });
    </script>
</body>
</html>