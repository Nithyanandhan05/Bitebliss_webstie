<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

// Validate and sanitize product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) {
    header('Location: menu.php');
    exit();
}

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch main product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $product_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: menu.php');
    exit();
}

// Fetch additional options from database
$options = [];
$option_stmt = $conn->prepare("SELECT * FROM product_options WHERE product_id = ? ORDER BY option_type, id");
if ($option_stmt) {
    $option_stmt->bind_param("i", $product_id);
    $option_stmt->execute();
    $option_result = $option_stmt->get_result();
    
    while($row = $option_result->fetch_assoc()) {
        $options[$row['option_type']][] = $row;
    }
    $option_stmt->close();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Bite Bliss</title>
    
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="login.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="product.css">
</head>
<body class="product-page-body">
    <div class="bg-elements">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
    </div>

    <?php 
    if (file_exists('header.php')) {
        include 'header.php';
    } else {
        echo '<header class="modern-header"><h1>Bite Bliss</h1></header>';
    }
    ?> 

    <div id="notification" class="notification"></div>

    <main class="product-page-container">
        <div class="product-hero">
            <div class="product-details-layout">
                <div class="product-gallery">
                    <div class="image-container">
                        <div class="image-overlay"></div>
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/500x400/975ab7/ffffff?text=<?php echo urlencode($product['name']); ?>'">
                        <div class="image-glow"></div>
                    </div>
                    
                    <div class="product-badge">
                        <i class="fas fa-star"></i>
                        <span>Premium Quality</span>
                    </div>
                </div>

                <div class="product-info-details">
                    <div class="product-header">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="product-base-price">
                            <span class="price-label">Starting from</span>
                            <span class="price-value">₹<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <form id="add-to-cart-form" method="POST" class="product-form">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="base_price" value="<?php echo $product['price']; ?>">
                        
                        <div class="options-group" data-animate="slide-up">
                            <label class="group-label">
                                <i class="fas fa-egg icon-gradient"></i>
                                <span>Egg Preference</span>
                            </label>
                            <div class="options-selector">
                                <div class="option-item">
                                    <input type="radio" name="egg_preference" id="eggless" value="eggless" data-price="0" checked>
                                    <label for="eggless" class="option-label">
                                        <i class="fas fa-leaf"></i>
                                        <span>Eggless</span>
                                        <div class="option-ripple"></div>
                                    </label>
                                </div>
                                <div class="option-item">
                                    <input type="radio" name="egg_preference" id="with_egg" value="with_egg" data-price="0">
                                    <label for="with_egg" class="option-label">
                                        <i class="fas fa-egg"></i>
                                        <span>With Egg</span>
                                        <div class="option-ripple"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($options)): ?>
                            <?php foreach ($options as $type => $option_list): ?>
                                <div class="options-group" data-animate="slide-up">
                                    <label class="group-label">
                                        <i class="fas fa-cog icon-gradient"></i>
                                        <span><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                    </label>
                                    <div class="options-selector">
                                        <?php foreach ($option_list as $index => $option): ?>
                                            <div class="option-item">
                                                <input type="radio" 
                                                       name="option_<?php echo strtolower(str_replace(' ', '_', $type)); ?>" 
                                                       id="option<?php echo $option['id']; ?>" 
                                                       value="<?php echo htmlspecialchars($option['option_name']); ?>" 
                                                       data-price="<?php echo $option['price_change'] ?? 0; ?>"
                                                       <?php echo ($index == 0) ? 'checked' : ''; ?>>
                                                <label for="option<?php echo $option['id']; ?>" class="option-label">
                                                    <span><?php echo htmlspecialchars($option['option_name']); ?></span>
                                                    <?php if (isset($option['price_change']) && $option['price_change'] > 0): ?>
                                                        <span class="price-modifier">+₹<?php echo number_format($option['price_change'], 2); ?></span>
                                                    <?php endif; ?>
                                                    <div class="option-ripple"></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="options-group message-group" data-animate="slide-up">
                            <label class="group-label" for="message">
                                <i class="fas fa-heart icon-gradient"></i>
                                <span>Message on Card <span class="optional">(Optional)</span></span>
                            </label>
                            <div class="message-container">
                                <textarea id="message" name="message" class="message-input" 
                                       placeholder="e.g., Happy Birthday! Hope your day is as sweet as this cake 🎂" 
                                       maxlength="150" rows="3"></textarea>
                                <div class="message-counter">
                                    <span id="char-count">0</span>/150 characters
                                </div>
                            </div>
                        </div>

                        <div class="quantity-group" data-animate="slide-up">
                            <label class="group-label">
                                <i class="fas fa-shopping-basket icon-gradient"></i>
                                <span>Quantity</span>
                            </label>
                            <div class="quantity-container">
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn minus-btn" onclick="changeQuantity(-1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="99" readonly>
                                    <button type="button" class="quantity-btn plus-btn" onclick="changeQuantity(1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="total-price-container" data-animate="slide-up">
                            <div class="total-price">
                                <div class="price-breakdown">
                                    <span class="price-label">Total Amount</span>
                                    <span class="price-value">₹<span id="total-price"><?php echo number_format($product['price'], 2); ?></span></span>
                                </div>
                                <div class="price-effects"></div>
                            </div>
                        </div>

                        <div class="cart-button-container" data-animate="slide-up">
                            <button type="submit" class="btn-add-to-cart">
                                <span class="btn-content">
                                    <i class="fas fa-cart-plus"></i>
                                    <span class="btn-text">Add to Cart</span>
                                </span>
                                <div class="btn-ripple"></div>
                                <div class="btn-glow"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php 
    if (file_exists('footer.php')) {
        include 'footer.php';
    }
    ?>

    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
    </div>

    <script>
        const basePrice = <?php echo $product['price']; ?>;
        let currentPrice = basePrice;
        let isProcessing = false;

        document.addEventListener('DOMContentLoaded', function() {
            initializeAnimations();
            initializeEventListeners();
            updateTotalPrice();
            
            setTimeout(() => {
                document.getElementById('loading-overlay').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loading-overlay').style.display = 'none';
                }, 300);
            }, 500);
        });

        function initializeAnimations() {
            const animatedElements = document.querySelectorAll('[data-animate]');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, { threshold: 0.1 });
            animatedElements.forEach(el => observer.observe(el));
        }

        function initializeEventListeners() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', updateTotalPrice);
                radio.nextElementSibling.addEventListener('click', function(e) {
                    createRipple(e, this);
                });
            });
            
            const messageInput = document.getElementById('message');
            if (messageInput) {
                messageInput.addEventListener('input', updateCharCounter);
            }
            
            document.getElementById('add-to-cart-form').addEventListener('submit', handleFormSubmission);
        }

        function changeQuantity(change) {
            if (isProcessing) return;
            const quantityInput = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityInput.value);
            const newQuantity = currentQuantity + change;
            if (newQuantity >= 1 && newQuantity <= 99) {
                quantityInput.value = newQuantity;
                updateTotalPrice();
                quantityInput.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    quantityInput.style.transform = 'scale(1)';
                }, 150);
            }
        }

        function updateTotalPrice() {
            let totalPrice = basePrice;
            const quantity = parseInt(document.getElementById('quantity').value);
            
            const checkedOptions = document.querySelectorAll('input[type="radio"]:checked');
            checkedOptions.forEach(option => {
                const priceModifier = parseFloat(option.dataset.price || 0);
                totalPrice += priceModifier;
            });
            
            totalPrice *= quantity;
            
            const priceElement = document.getElementById('total-price');
            priceElement.style.transform = 'scale(1.1)';
            priceElement.style.color = '#e6d7ff';
            
            setTimeout(() => {
                priceElement.textContent = totalPrice.toFixed(2);
                priceElement.style.transform = 'scale(1)';
                priceElement.style.color = '';
            }, 150);
            
            currentPrice = totalPrice;
        }

        function updateCharCounter() {
            const messageInput = document.getElementById('message');
            const charCount = document.getElementById('char-count');
            const remaining = messageInput.value.length;
            charCount.textContent = remaining;
            if (remaining > 120) {
                charCount.style.color = '#ff6b6b';
            } else if (remaining > 100) {
                charCount.style.color = '#ffa726';
            } else {
                charCount.style.color = '';
            }
        }

        function createRipple(event, element) {
            const ripple = element.querySelector('.option-ripple, .piece-ripple');
            if (!ripple) return;
            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-animate');
            setTimeout(() => {
                ripple.classList.remove('ripple-animate');
            }, 600);
        }

        function handleFormSubmission(e) {
            e.preventDefault();
            
            if (isProcessing) return;
            isProcessing = true;
            
            const formData = new FormData(this);
            formData.append('total_price', currentPrice.toFixed(2));
            formData.append('action', 'add_to_cart');
            
            const submitBtn = document.querySelector('.btn-add-to-cart');
            const btnContent = submitBtn.querySelector('.btn-content');
            const originalContent = btnContent.innerHTML;
            
            btnContent.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span class="btn-text">Adding...</span>';
            submitBtn.classList.add('loading');
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btnContent.innerHTML = '<i class="fas fa-check"></i><span class="btn-text">Added!</span>';
                    submitBtn.classList.remove('loading');
                    submitBtn.classList.add('success');
                    showNotification('Product added to cart successfully!', 'success');
                    
                    setTimeout(() => {
                        btnContent.innerHTML = originalContent;
                        submitBtn.classList.remove('success');
                        isProcessing = false;
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to add product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error: ' + error.message, 'error');
                btnContent.innerHTML = originalContent;
                submitBtn.classList.remove('loading');
                isProcessing = false;
            });
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }
    </script>
</body>
</html>