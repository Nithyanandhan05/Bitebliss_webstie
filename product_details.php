<?php
session_start();
require_once 'db_connect.php';

// --- DATA FETCHING ---
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header('Location: menu.php');
    exit();
}

$product = null;
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
}
$stmt->close();

if ($product === null) {
    header('Location: menu.php');
    exit();
}

$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if(is_array($item) && isset($item['quantity'])) {
            $cart_item_count += $item['quantity'];
        }
    }
}

// Customization options for pieces remain unchanged
$customization_options = [
    'brownie' => [
        ['value' => 3, 'multiplier' => 3, 'text' => '3 Pieces'],
        ['value' => 6, 'multiplier' => 6, 'text' => '6 Pieces'],
        ['value' => 12, 'multiplier' => 12, 'text' => '12 Pieces']
    ],
    'cookies' => [
        ['value' => 3, 'multiplier' => 3, 'text' => '3 Pieces'],
        ['value' => 5, 'multiplier' => 5, 'text' => '5 Pieces'],
        ['value' => 8, 'multiplier' => 8, 'text' => '8 Pieces']
    ],
    'donut' => [
        ['value' => 2, 'multiplier' => 2, 'text' => '2 Pieces'],
        ['value' => 4, 'multiplier' => 4, 'text' => '4 Pieces'],
        ['value' => 6, 'multiplier' => 6, 'text' => '6 Pieces']
    ],
    'cupcake' => [
        ['value' => 2, 'multiplier' => 2, 'text' => '2 Pieces'],
        ['value' => 3, 'multiplier' => 3, 'text' => '3 Pieces'],
        ['value' => 4, 'multiplier' => 4, 'text' => '4 Pieces'],
        ['value' => 6, 'multiplier' => 6, 'text' => '6 Pieces']
    ],
    'default' => [['value' => 1, 'multiplier' => 1, 'text' => '1 Piece']]
];

$product_category_name = strtolower(trim($product['category_name'] ?? 'default'));
$options_for_product = $customization_options[$product_category_name] ?? $customization_options['default'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Bite Bliss</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="product_details.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body class="product-details-page-body">
    <?php include 'header.php'; ?>

    <main class="details-container">
        <div class="product-details-grid">
            <div class="product-image-gallery">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>

            <div class="product-info-and-customization">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php
                $customizable_categories = ['brownie', 'cookies', 'donut', 'cupcake'];
                $is_customizable_by_category = in_array($product_category_name, $customizable_categories);

                // This logic is now simplified to show one form for all customizable products
                if ($product['is_customizable'] || $is_customizable_by_category):
                ?>
                    <form id="customizationForm" class="customization-form">
                        <input type="hidden" id="panelProductId" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" id="panelBasePrice" name="base_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" id="panelProductNameInput" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <div class="form-group">
                            <label for="pieces"><i class="fas fa-th-large"></i> Pieces</label>
                            <div class="custom-select-wrapper">
                                <select id="pieces" name="pieces" class="panel-select">
                                    <?php foreach ($options_for_product as $option): ?>
                                        <option value="<?php echo $option['value']; ?>" data-multiplier="<?php echo $option['multiplier']; ?>"><?php echo htmlspecialchars($option['text']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($product['allow_message']): ?>
                        <div class="form-group">
                            <label for="message"><i class="fas fa-comment-dots"></i> Message on Product (optional)</label>
                            <textarea id="message" name="message" class="panel-textarea" placeholder="e.g., Happy Birthday!" maxlength="30" rows="2"></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="form-group quantity-group">
                            <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" class="panel-input">
                        </div>

                        <div class="form-footer">
                            <div class="price-container">
                                <span>Total Price</span>
                                <strong id="totalPrice">₹<?php echo number_format($product['price'], 2); ?></strong>
                            </div>
                            <button type="submit" class="btn-add-to-cart-details">
                                <span class="btn-text">Add to Cart</span>
                                <span class="loading-icon"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-footer">
                        <div class="price-container">
                            <span>Price</span>
                            <strong id="totalPrice">₹<?php echo number_format($product['price'], 2); ?></strong>
                        </div>
                        <button class="btn-add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            <span class="btn-text default-text">Add to Cart</span>
                            <span class="btn-text added-text">Added!</span>
                        </button>
                    </div>
                <?php endif; ?>
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
        const customizationForm = document.getElementById('customizationForm');
        if (!customizationForm) return;

        // --- All references to egg/eggless are removed ---
        const basePriceEl = document.getElementById('panelBasePrice');
        const piecesSelect = document.getElementById('pieces');
        const quantityInput = document.getElementById('quantity');
        const totalPriceDisplay = document.getElementById('totalPrice');

        function updateTotalPrice() {
            const basePrice = parseFloat(basePriceEl.value) || 0;
            const quantity = parseInt(quantityInput.value) || 1;
            
            const selectedPiecesOption = piecesSelect.options[piecesSelect.selectedIndex];
            const multiplier = parseFloat(selectedPiecesOption.dataset.multiplier) || 1;
            
            // Price calculation is now simpler
            let pricePerUnit = basePrice * multiplier;
            const finalTotal = pricePerUnit * quantity;

            totalPriceDisplay.textContent = `₹${finalTotal.toFixed(2)}`;
        }

        customizationForm.addEventListener('change', updateTotalPrice);
        quantityInput.addEventListener('input', updateTotalPrice);

        customizationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const addButton = this.querySelector('.btn-add-to-cart-details');
            const btnText = addButton.querySelector('.btn-text');
            const loadingIcon = addButton.querySelector('.loading-icon');
            
            btnText.style.display = 'none';
            loadingIcon.style.display = 'inline-block';
            addButton.disabled = true;
            
            const formData = new FormData(this);
            // The logic to append 'egg_preference' is removed
            
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

        // Initialize price on page load
        updateTotalPrice();
    });

    // --- The custom dropdown script is preserved exactly as it was ---
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.querySelector('.custom-select-wrapper');
        if (wrapper) {
            const originalSelect = wrapper.querySelector('select');
            
            const styledSelect = document.createElement('div');
            styledSelect.classList.add('select-styled');
            styledSelect.textContent = originalSelect.options[originalSelect.selectedIndex].textContent;
            wrapper.appendChild(styledSelect);

            const optionsList = document.createElement('div');
            optionsList.classList.add('select-options');
            
            for (let i = 0; i < originalSelect.options.length; i++) {
                const option = originalSelect.options[i];
                const optionDiv = document.createElement('div');
                optionDiv.textContent = option.textContent;
                optionDiv.dataset.value = option.value;
                optionsList.appendChild(optionDiv);

                optionDiv.addEventListener('click', function(e) {
                    e.stopPropagation();
                    styledSelect.textContent = this.textContent;
                    styledSelect.classList.remove('active');
                    optionsList.classList.remove('open');
                    
                    originalSelect.value = this.dataset.value;

                    const changeEvent = new Event('change', { bubbles: true });
                    originalSelect.dispatchEvent(changeEvent);
                });
            }
            wrapper.appendChild(optionsList);

            styledSelect.addEventListener('click', function(e) {
                e.stopPropagation();
                this.classList.toggle('active');
                optionsList.classList.toggle('open');
            });

            document.addEventListener('click', function() {
                styledSelect.classList.remove('active');
                optionsList.classList.remove('open');
            });
        }
    });
    </script>
</body>
</html>