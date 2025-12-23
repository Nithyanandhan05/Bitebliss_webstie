<?php
session_start();
require_once 'db_connect.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_data = null;

if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_data = $result->fetch_assoc();
    $stmt->close();
}

if (!$order_data) {
    header('Location: index.php');
    exit();
}

$order_time = strtotime($order_data['order_date']);
$estimated_delivery = date('g:i A', $order_time + (45 * 60));

// Display success/error messages for review form
$showReviewMessage = false;
$reviewMessageType = '';
$reviewMessage = '';

if (isset($_GET['review_status'])) {
    $showReviewMessage = true;
    $reviewMessageType = $_GET['review_status'] === 'success' ? 'success' : 'error';
    $reviewMessage = $_GET['review_status'] === 'success' 
        ? 'Thank you for your review!' 
        : (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bite Bliss</title>
    <link rel="icon" type="image/png" href="img/logo_tag.png">
    <link rel="stylesheet" href="style.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="menu-page-body">
    <?php include 'header.php'; ?>

    <?php if ($showReviewMessage): ?>
    <div id="message-popup" class="message-popup <?php echo $reviewMessageType; ?>">
        <div class="message-icon"><?php echo $reviewMessageType === 'success' ? '✓' : '✗'; ?></div>
        <h3><?php echo $reviewMessageType === 'success' ? 'Review Submitted!' : 'Submission Failed'; ?></h3>
        <p><?php echo $reviewMessage; ?></p>
    </div>
    <?php endif; ?>

    <main class="thank-you-container">
        <!-- Order Confirmation Section -->
        <div class="thank-you-section">
            <div class="success-checkmark">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been placed successfully. We'll start preparing your delicious meal right away!</p>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="order-details">
                    <div class="detail-item">
                        <span class="label">Order ID:</span>
                        <span class="value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    
                    <?php if ($order_data && isset($order_data['total_price'])): ?>
                    <div class="detail-item">
                        <span class="label">Total Amount:</span>
                        <span class="value">₹<?php echo number_format($order_data['total_price'], 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="detail-item">
                        <span class="label">Payment Method:</span>
                        <span class="value">
                            <?php echo ($order_data && $order_data['razorpay_payment_id']) ? 'Online Payment' : 'Cash on Delivery'; ?>
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="label">Estimated Delivery:</span>
                        <span class="value delivery-time">1-2 Days</span>
                    </div>
                </div>

                <?php if ($order_data && $order_data['razorpay_payment_id']): ?>
                <div class="payment-confirmation">
                    <i class="fas fa-shield-alt"></i>
                    <span>Payment Confirmed - ID: <?php echo htmlspecialchars($order_data['razorpay_payment_id']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="menu.php" class="btn btn-secondary">
                    <i class="fas fa-utensils"></i> Order More
                </a>
            </div>
        </div>

        <!-- Review Form Section -->
        <section id="review-form-section" class="content-section">
            <h2 class="section-title">How was your experience?</h2>
            <p class="review-subtitle">We'd love to hear your feedback about our service</p>
            
            <form id="review-form" action="submit_review.php" method="post">
                <input type="hidden" name="redirect_url" value="thank_you.php?order_id=<?php echo $order_id; ?>&review_status=">
                
                <div class="form-group">
                    <label for="user_name">Your Name</label>
                    <input type="text" id="user_name" name="user_name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required/>
                        <label for="star5" title="5 stars"></label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 stars"></label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 stars"></label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 stars"></label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 star"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Review</label>
                    <textarea id="review_text" name="review_text" rows="4" placeholder="Share your thoughts about our food and service..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-review">
                    <i class="fas fa-star"></i> Submit Review
                </button>
            </form>
        </section>

        <div class="support-section">
            <h4><i class="fas fa-headset"></i> Need Help?</h4>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+91 8438425634</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>biteblissbrownie@gmail.com</span>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>

    <style>
        /* Your existing CSS variables and base styles */
        :root {
            --purple: #975ab7;
            --brown: #502325;
            --main-bg: var(--purple);
            --text-color: var(--brown);
            --card-bg: rgba(80, 35, 37, 0.05);
            --card-border: rgba(80, 35, 37, 0.15);
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --success-green: #28a745;
            --gradient-primary: linear-gradient(135deg, #975ab7 0%, #7b4397 50%, #502325 100%);
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.15);
            --border-radius: 20px;
            --transition-fast: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.menu-page-body {
            font-family: 'Inter', sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .thank-you-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Thank You Section - Simple and Clean */
        .thank-you-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            margin: 2rem 0;
            text-align: center;
            box-shadow: var(--shadow-medium);
        }

        .success-checkmark {
            margin-bottom: 2rem;
        }

        .success-checkmark i {
            font-size: 4rem;
            color: var(--success-green);
            animation: checkmarkPulse 2s ease-in-out infinite;
        }

        @keyframes checkmarkPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .thank-you-section h1 {
            font-family: 'Playfair Display', serif;
            color: var(--text-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .thank-you-section > p {
            color: var(--text-color);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .order-summary {
            background: var(--light-gray);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }

        .order-summary h3 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .order-details {
            display: grid;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--card-border);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item .label {
            color: var(--text-color);
            font-weight: 500;
        }

        .detail-item .value {
            color: var(--text-color);
            font-weight: 700;
        }

        .delivery-time {
            color: var(--success-green) !important;
        }

        .payment-confirmation {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--success-green);
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-fast);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--purple);
            border: 2px solid var(--purple);
        }

        .btn-secondary:hover {
            background: var(--purple);
            color: var(--white);
        }

        /* Review Form Section - Integrated Design */
        #review-form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin: 3rem 0;
            box-shadow: var(--shadow-medium);
        }

        .section-title {
            color: var(--text-color);
            font-size: 2rem;
            text-align: center;
            margin-bottom: 0.5rem;
            font-family: 'Playfair Display', serif;
        }

        .review-subtitle {
            text-align: center;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--card-border);
            border-radius: 10px;
            font-family: inherit;
            transition: var(--transition-fast);
            background: var(--white);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(151, 90, 183, 0.1);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .star-rating label::before {
            content: '★';
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }

        .btn-review {
            background: var(--gradient-primary);
            color: var(--white);
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .btn-review:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        /* Support Section */
        .support-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            box-shadow: var(--shadow-light);
        }

        .support-section h4 {
            color: var(--text-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }

        .contact-item i {
            color: var(--purple);
        }

        /* Message Popup for Review Feedback */
        .message-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            background: var(--white);
            box-shadow: var(--shadow-medium);
            z-index: 1000;
            animation: slideInFromRight 0.5s ease-out;
        }

        .message-popup.success {
            border-left: 4px solid var(--success-green);
        }

        .message-popup.error {
            border-left: 4px solid #dc3545;
        }

        .message-popup .message-icon {
            display: inline-block;
            margin-right: 0.5rem;
            font-weight: bold;
        }

        .message-popup.success .message-icon {
            color: var(--success-green);
        }

        .message-popup.error .message-icon {
            color: #dc3545;
        }

        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Form Error Styles */
        .form-error {
            border-color: #dc3545 !important;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .thank-you-container {
                padding: 1rem;
            }

            .thank-you-section h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .contact-info {
                flex-direction: column;
                gap: 1rem;
            }

            .star-rating {
                gap: 0.25rem;
            }

            .star-rating label {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
        // Form validation and interactions
        const form = document.getElementById('review-form');
        const submitBtn = document.querySelector('.btn-review');

        function validateForm() {
            let isValid = true;
            const userName = document.getElementById('user_name');
            const rating = document.querySelector('input[name="rating"]:checked');
            const reviewText = document.getElementById('review_text');

            // Clear previous errors
            document.querySelectorAll('.form-error').forEach(el => {
                el.classList.remove('form-error');
            });
            document.querySelectorAll('.error-message').forEach(el => el.remove());

            // Validate name
            if (userName.value.trim().length < 2) {
                showFieldError(userName, 'Name must be at least 2 characters long');
                isValid = false;
            }

            // Validate rating
            if (!rating) {
                showFieldError(document.querySelector('.star-rating'), 'Please select a rating');
                isValid = false;
            }

            // Validate review text
            if (reviewText.value.trim().length < 10) {
                showFieldError(reviewText, 'Review must be at least 10 characters long');
                isValid = false;
            }

            return isValid;
        }

        function showFieldError(field, message) {
            field.classList.add('form-error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }

        // Form submission
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
        });

        // Auto-hide message popup
        const messagePopup = document.getElementById('message-popup');
        if (messagePopup) {
            setTimeout(() => {
                messagePopup.style.animation = 'slideInFromRight 0.5s ease-out reverse';
                setTimeout(() => messagePopup.remove(), 500);
            }, 4000);
        }

        // Star rating hover effects
        const starLabels = document.querySelectorAll('.star-rating label');
        starLabels.forEach((label, index) => {
            label.addEventListener('mouseenter', function() {
                starLabels.forEach((l, i) => {
                    if (i >= (starLabels.length - index - 1)) {
                        l.style.color = '#ffc107';
                        l.style.transform = 'scale(1.1)';
                    }
                });
            });

            label.addEventListener('mouseleave', function() {
                starLabels.forEach(l => {
                    const isChecked = document.querySelector('input[name="rating"]:checked');
                    if (!isChecked || !isChecked.nextElementSibling === l) {
                        l.style.color = '#ddd';
                        l.style.transform = 'scale(1)';
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>