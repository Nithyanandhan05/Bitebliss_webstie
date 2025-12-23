<?php
session_start();
require_once 'db_connect.php';

// --- DATA FETCHING ---
$all_categories = [];
$cat_list_sql = "SELECT id, name, category_type FROM categories ORDER BY id ASC";
$cat_list_result = $conn->query($cat_list_sql);
if ($cat_list_result->num_rows > 0) {
    while($row = $cat_list_result->fetch_assoc()) { $all_categories[] = $row; }
}

$current_category_id = isset($_GET['category']) ? (int)$_GET['category'] : ($all_categories[0]['id'] ?? 1);

$products = [];
$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.is_visible = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_category_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { $products[] = $row; }
}
$stmt->close();

$category_name = "Our Collection";
$current_cat_key = '';
if (!empty($products)) {
    $category_name = htmlspecialchars($products[0]['category_name']);
    $current_cat_key = strtolower($products[0]['category_name']);
}

$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if(is_array($item) && isset($item['quantity'])) {
            $cart_item_count += $item['quantity'];
        }
    }
}

// --- LOGIC FOR DYNAMIC PRICING AND BOXES ---

// Define the price multipliers for minimum piece display
$multipliers = [
    2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 8 => 8, 12 => 12,
];

// Define the minimum piece count for each category
$min_pieces_map = [
    'brownie' => 3, 'cookies' => 3, 'donut' => 2, 'cupcake' => 2,
];

// Define the available box sizes for each category
$box_sizes_map = [
    'brownie' => [3, 6, 12], 'cookies' => [3, 5, 8], 'donut' => [2, 4, 6], 'cupcake' => [2, 3, 4, 6]
];

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
    <link rel="stylesheet" href="menu.css?v=1.2">
    <link rel="stylesheet" href="customize-box-page.css?v=1.0">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body class="menu-page-body">
    
    <?php include 'header.php'; ?>

    <div class="menu-page-container">
        <main class="product-content">
            <div class="categories-filter">
                <?php foreach ($all_categories as $cat): ?>
                    <?php
    $link = 'menu.php?category=' . $cat['id'];
    if (isset($cat['category_type']) && $cat['category_type'] === 'cake_customizer') {
        $link = 'customize_cake.php?category=' . $cat['id'];
    }
?>
<a href="<?php echo $link; ?>" class="<?php echo ($cat['id'] == $current_category_id) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h1 class="category-title"><?php echo $category_name; ?></h1>

            <?php
                $customizable_categories = ['brownie', 'cookies', 'donut', 'cupcake'];
                if (in_array($current_cat_key, $customizable_categories)):
                ?>
                    <div class="customize-box-launcher">
                        <a href="customize-box.php?category=<?php echo $current_cat_key; ?>" class="btn-customize-box">
                            <i class="fas fa-box-open"></i>
                            Create a Custom Box
                        </a>
                    </div>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <?php
                                    $product_cat_key = strtolower($product['category_name']);
                                    $display_price = $product['price'];
                                    $display_text = "Starts at";
                                    if (isset($min_pieces_map[$product_cat_key])) {
                                        $min_pieces = $min_pieces_map[$product_cat_key];
                                        if (isset($multipliers[$min_pieces])) {
                                            $display_price = $product['price'] * $multipliers[$min_pieces];
                                            $display_text = $min_pieces . " pieces from";
                                        }
                                    }
                                ?>
                                <p class="product-price"><?php echo $display_text; ?> ₹<?php echo number_format($display_price, 2); ?></p>

                                <?php
                                $customizable_categories = ['Brownie', 'Cookies', 'Donut', 'Cupcake'];
                                $is_customizable_by_category = in_array($product['category_name'], $customizable_categories);
                                if ($product['is_customizable'] || $is_customizable_by_category):
                                ?>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-customize">
                                        <span class="default-text">Customize</span>
                                    </a>
                                <?php else: ?>
                                    <button class="btn-add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <span class="btn-text default-text">Add to Cart</span>
                                        <span class="btn-text added-text">Added!</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-products">No products found in this category. 😔</p>
            <?php endif; ?>
        </main>
    </div>

    <div id="customize-box-modal" class="customize-box-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button id="close-customize-box-btn" class="modal-close-btn">&times;</button>
            <div class="modal-header">
                <h2>Create Your Custom Box</h2>
                <p>Select your favorite items to build the perfect box.</p>
            </div>
            <div class="modal-body">
                <div id="box-item-selection-grid" class="box-item-grid">
                    </div>
            </div>
            <div class="modal-footer">
                <div class="selection-progress">
                    <div id="progress-bar" class="progress-bar"></div>
                    <span id="progress-text" class="progress-text">0 of X selected</span>
                </div>
                <div class="footer-price">
                    <span>Total Price</span>
                    <strong>₹0.00</strong>
                </div>
                <button id="add-box-to-cart-btn" class="btn-add-box" disabled>
                    Add Box to Cart
                </button>
            </div>
        </div>
    </div>

    <?php include 'login_panel.php'; ?>
    <?php include 'floating_cart.php'; ?>

    <?php require_once 'footer.php'; ?>
    
    <script src="script.js?v=2.5"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryName = "<?php echo $current_cat_key; ?>";
        const boxSizeSelector = document.getElementById('box-size-select');
        const openBtn = document.getElementById('open-customize-box-btn');

        if (!openBtn || !boxSizeSelector) return;

        const availableItems = <?php
            $items_for_category = array_filter($products, function($p) use ($current_cat_key) {
                return strtolower($p['category_name']) === $current_cat_key;
            });
            echo json_encode(array_values($items_for_category));
        ?>;

        let selectedBoxSize = 0;
        let selectedItems = [];

        const modal = document.getElementById('customize-box-modal');
        const closeBtn = document.getElementById('close-customize-box-btn');
        const overlay = modal.querySelector('.modal-overlay');
        const grid = document.getElementById('box-item-selection-grid');
        const modalTitle = modal.querySelector('.modal-header h2');
        const progressText = document.getElementById('progress-text');
        const progressBar = modal.querySelector('.progress-bar').style;
        const addBoxBtn = document.getElementById('add-box-to-cart-btn');
        const totalPriceDisplay = modal.querySelector('.footer-price strong');

        boxSizeSelector.addEventListener('change', function() {
            selectedBoxSize = parseInt(this.value, 10);
            openBtn.disabled = !(selectedBoxSize > 0);
        });

        function openModal() {
            if (!modal || selectedBoxSize === 0) return;
            const formattedCategoryName = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
            modalTitle.textContent = `Create Your ${formattedCategoryName} Box`;
            populateGrid();
            updateProgress();
            updateBoxPrice();
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            if (!modal) return;
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            selectedItems = [];
        }

        function populateGrid() {
            grid.innerHTML = '';
            availableItems.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'box-item-card';
                card.style.animation = `cardSlideIn 0.5s ease-out ${index * 0.05}s forwards`;
                card.innerHTML = `<img src="${item.image_url}" alt="${item.name}"><p>${item.name}</p>`;
                card.addEventListener('click', () => toggleItemSelection(card, item));
                grid.appendChild(card);
            });
        }

        function toggleItemSelection(cardElement, item) {
            const itemInstanceId = cardElement.dataset.instanceId;
            const itemIndex = selectedItems.findIndex(selected => selected.instanceId === itemInstanceId);

            if (itemIndex > -1) {
                selectedItems.splice(itemIndex, 1);
                cardElement.classList.remove('selected');
                delete cardElement.dataset.instanceId;
            } else if (selectedItems.length < selectedBoxSize) {
                const uniqueInstance = { ...item, instanceId: 'instance_' + Date.now() + Math.random() };
                selectedItems.push(uniqueInstance);
                cardElement.classList.add('selected');
                cardElement.dataset.instanceId = uniqueInstance.instanceId;
            } else {
                alert(`You can only select ${selectedBoxSize} items for this box.`);
            }
            updateProgress();
            updateBoxPrice();
        }

        function updateProgress() {
            const count = selectedItems.length;
            progressText.textContent = `${count} of ${selectedBoxSize} selected`;
            progressBar.width = `${(count / selectedBoxSiz) * 100}%`;
            addBoxBtn.disabled = (count !== selectedBoxSize);
        }

        function updateBoxPrice() {
            let currentTotalPrice = 0;
            selectedItems.forEach(item => {
                currentTotalPrice += parseFloat(item.price);
            });
            totalPriceDisplay.textContent = `₹${currentTotalPrice.toFixed(2)}`;
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        addBoxBtn.addEventListener('click', function() {
            if (selectedItems.length !== selectedBoxSize) return;

            const boxDetails = {
                type: `Custom ${selectedBoxSize}-Piece ${categoryName.charAt(0).toUpperCase() + categoryName.slice(1)} Box`,
                price: totalPriceDisplay.textContent,
                items: selectedItems.map(item => item.name)
            };
            
            console.log("Custom Box Details:", boxDetails);
            alert("Your custom box has been configured! Check the console for details. The next step is to add this to the cart.");
            
            closeModal();
        });
    });
    </script>
</body>
</html>