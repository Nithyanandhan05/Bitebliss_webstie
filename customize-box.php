<?php
session_start();
require_once 'db_connect.php';

// --- GET CATEGORY AND ITS PRODUCTS ---
$category_slug = isset($_GET['category']) ? $_GET['category'] : 'brownie'; // Default to brownie

// Fetch all products for the selected category
$stmt = $conn->prepare("SELECT p.* FROM products p JOIN categories c ON p.category_id = c.id WHERE LOWER(c.name) = ? AND p.is_visible = 1");
$stmt->bind_param("s", $category_slug);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($products)) {
    // Handle case where category has no products or doesn't exist
    header('Location: menu.php');
    exit();
}
$category_name = ucfirst($category_slug);

// --- DEFINE BOX SIZES ---
$box_sizes_map = [
    'brownie' => [3, 6, 12],
    'cookies' => [3, 5, 8],
    'donut'   => [2, 4, 6],
    'cupcake' => [2, 3, 4, 6]
];
$available_sizes = $box_sizes_map[$category_slug] ?? [];

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
    <link rel="stylesheet" href="customize-box-page.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<?php include 'header.php'; ?>
<body class="customize-page-body">
    

    <div class="custom-box-container"style="/* user-select: auto;*/margin-top: 70px;">
        <div class="controls-panel">
            <div class="controls-sticky-content">
                <a href="menu.php" class="back-to-menu"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                <h1>Create Your <?php echo $category_name; ?> Box</h1>
                
                <div class="control-section">
                    <h2>1. Choose Your Box Size</h2>
                    <div id="size-selector" class="size-selector">
                        <?php foreach ($available_sizes as $size): ?>
                            <button class="size-btn" data-size="<?php echo $size; ?>"><?php echo $size; ?> Pieces</button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="control-section">
                    <h2>2. Your Selections</h2>
                    <p id="progress-text">Please select a box size first.</p>
                    <div id="box-contents" class="box-contents">
                        </div>
                </div>

                <div class="summary-section">
                    <div class="price-display">
                        <span>Total Price</span>
                        <strong id="total-price">₹0.00</strong>
                    </div>
                    <button id="add-box-to-cart-btn" class="btn-add-box" disabled>
                        Select All Items to Continue
                    </button>
                </div>
            </div>
        </div>

        <div class="product-selection-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" data-image="<?php echo $product['image_url']; ?>">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p>₹<?php echo number_format($product['price'], 2); ?></p>
                    </div>
                    <div class="product-overlay">
                        <i class="fas fa-plus"></i> Add to Box
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="script.js"></script>

    <script scr="login.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedBoxSize = 0;
        let selectedItems = [];

        const sizeButtons = document.querySelectorAll('.size-btn');
        const productCards = document.querySelectorAll('.product-card');
        const progressText = document.getElementById('progress-text');
        const boxContentsDiv = document.getElementById('box-contents');
        const totalPriceDisplay = document.getElementById('total-price');
        const addBoxBtn = document.getElementById('add-box-to-cart-btn');

        // 1. Handle Size Selection
        sizeButtons.forEach(button => {
            button.addEventListener('click', () => {
                sizeButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectedBoxSize = parseInt(button.dataset.size, 10);
                
                selectedItems = []; 
                productCards.forEach(c => c.classList.remove('selected', 'disabled'));
                updateAllUI();
            });
        });

        // 2. Handle Product Selection
        productCards.forEach(card => {
            card.addEventListener('click', () => {
                if (card.classList.contains('disabled')) return;
                if (selectedBoxSize === 0) {
                    alert("Please choose a box size first!");
                    return;
                }
                const product = {
                    id: card.dataset.id, name: card.dataset.name,
                    price: parseFloat(card.dataset.price), image: card.dataset.image,
                    instanceId: 'instance_' + Date.now() + Math.random()
                };
                if (selectedItems.length < selectedBoxSize) {
                    selectedItems.push(product);
                    updateAllUI();
                }
            });
        });

        // 3. Central UI Update Function
        function updateAllUI() {
            updateProgressText();
            updateBoxContentsUI();
            updateTotalPrice();
            updateProductCardStates();
            updateAddToCartButton();
        }

        function updateProgressText() {
            if (selectedBoxSize === 0) {
                progressText.textContent = "Please select a box size first.";
            } else {
                progressText.textContent = `${selectedItems.length} of ${selectedBoxSize} selected`;
            }
        }

        function updateBoxContentsUI() {
            boxContentsDiv.innerHTML = '';
            selectedItems.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'selected-item';
                itemDiv.innerHTML = `
                    <img src="${item.image}" alt="">
                    <span class="item-name">${item.name}</span>
                    <button class="remove-item-btn" data-instance-id="${item.instanceId}">&times;</button>
                `;
                boxContentsDiv.appendChild(itemDiv);
                
                itemDiv.querySelector('.remove-item-btn').addEventListener('click', (e) => {
                    const idToRemove = e.target.dataset.instanceId;
                    itemDiv.classList.add('removing');
                    setTimeout(() => {
                        selectedItems = selectedItems.filter(i => i.instanceId !== idToRemove);
                        updateAllUI();
                    }, 500);
                });
            });
        }

        function updateTotalPrice() {
            let currentTotalPrice = 0;
            selectedItems.forEach(item => { currentTotalPrice += item.price; });
            totalPriceDisplay.textContent = `₹${currentTotalPrice.toFixed(2)}`;
            if (selectedItems.length > 0) {
                totalPriceDisplay.classList.add('flash');
                setTimeout(() => totalPriceDisplay.classList.remove('flash'), 500);
            }
        }

        function updateProductCardStates() {
            const isFull = selectedItems.length >= selectedBoxSize;
            const selectedInstanceIds = new Set(selectedItems.map(item => item.instanceId));
            
            productCards.forEach(card => {
                card.classList.remove('disabled');
                // This logic needs to be tied to the items in the box, not just a generic class.
                // For simplicity, we manage state via selectedItems array.
                
                if (isFull) {
                    card.classList.add('disabled');
                }
            });
             // Re-enable selected cards so they can be deselected
            document.querySelectorAll('.selected-item').forEach(selectedDiv => {
                // This part of the logic can be complex. The current add/remove flow is sufficient.
            });
        }

        function updateAddToCartButton() {
            const isReady = selectedItems.length === selectedBoxSize && selectedBoxSize > 0;
            addBoxBtn.disabled = !isReady;
            addBoxBtn.textContent = isReady ? 'Add Box to Cart' : 'Select All Items to Continue';
            addBoxBtn.classList.toggle('glowing', isReady);
        }

        // 4. Scroll Animation for Product Cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        productCards.forEach(card => observer.observe(card));

        // =========================================================
        // ### MODIFIED: ADD TO CART BUTTON LOGIC ###
        // =========================================================
        addBoxBtn.addEventListener('click', () => {
            if (addBoxBtn.disabled) return;

            const categoryName = "<?php echo $category_name; ?>";
            const boxDetails = {
                name: `Custom ${selectedBoxSize}-Piece ${categoryName} Box`,
                price: totalPriceDisplay.textContent,
                items: selectedItems.map(item => item.name) // Create a simple list of names
            };

            // Show loading state
            addBoxBtn.textContent = 'Adding...';
            addBoxBtn.disabled = true;

            fetch('add_box_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(boxDetails)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the cart counts in the header and floating bar
                    // These functions should exist in your main script.js
                    if (typeof updateCartCountInHeader === 'function') {
                        updateCartCountInHeader(data.cart_count);
                    }
                    if (typeof updateFloatingCart === 'function') {
                        updateFloatingCart(data.cart_count);
                    }
                    
                    // Redirect to menu page after a short delay
                    alert('Your custom box has been added to the cart!');
                    window.location.href = 'menu.php';

                } else {
                    alert('Error: ' + data.message);
                    updateAddToCartButton(); // Re-enable the button
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                updateAddToCartButton(); // Re-enable the button
            });
        });
    });
    </script>
</body>
</html>