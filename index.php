<?php
require_once 'db_connect.php';

// Fetch recent reviews from database for thank you section
$thank_you_reviews_sql = "SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6";
$thank_you_reviews_result = $conn->query($thank_you_reviews_sql);

// Fetch total counts for statistics
$total_customers_sql = "SELECT COUNT(DISTINCT user_name) as total_customers FROM reviews WHERE is_approved = 1";
$total_customers_result = $conn->query($total_customers_sql);
$total_customers = $total_customers_result->fetch_assoc()['total_customers'] ?: 0;

$total_reviews_sql = "SELECT COUNT(*) as total_reviews FROM reviews WHERE is_approved = 1";
$total_reviews_result = $conn->query($total_reviews_sql);
$total_reviews = $total_reviews_result->fetch_assoc()['total_reviews'] ?: 0;

// Calculate average rating
$avg_rating_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE is_approved = 1";
$avg_rating_result = $conn->query($avg_rating_sql);
$avg_rating_raw = $avg_rating_result->fetch_assoc()['avg_rating'];
$avg_rating = $avg_rating_raw ? round($avg_rating_raw, 1) : 5.0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bite Bliss</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="footer.css"

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?> 

    <main>
        <?php
        // --- This is the new code to fetch your slides from the database ---
        $slider_sql = "SELECT * FROM slider_images ORDER BY id DESC";
        $slider_result = $conn->query($slider_sql);
        ?>
        <div class="swiper hero-slider">
            <div class="swiper-wrapper">
                <?php if ($slider_result->num_rows > 0): ?>
                    <?php while($slide = $slider_result->fetch_assoc()): ?>
                        <div class="swiper-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image_url']); ?>');">
                            <div class="slide-content">
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="swiper-slide" style="background-image: url('img/slide1.jpg');">
                         <div class="slide-content">
                            <h1>Welcome to Bite Bliss</h1>
                            <p>Add your first slide in the admin panel.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>

        <section id="about" class="content-section">
            <div class="about-box">
                <h2>Our Secret Ingredient? Passion.</h2>
                <p>Founded in a small kitchen with a big dream, The Brownie Boutique is all about celebrating the rich, fudgy goodness of the perfect brownie. We use only the finest ingredients, from Belgian chocolate to locally sourced butter.</p>
            </div>
        </section>

        <section class="video-ad-section">
            <video playsinline autoplay muted loop class="ad-video">
                <source src="videos/brownie-ad.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay">
                <h2>Experience Pure Indulgence</h2>
                <p>Our brownies are made with love and the finest Belgian chocolate.</p>
            </div>
        </section>

        <section id="categories" class="content-section">
            <h2 class="section-title hidden">Shop by Category</h2>
            <div class="category-grid">
            <?php
            // Fetch categories from the database
            $category_sql = "SELECT id, name, image_url, category_type FROM categories";
            $category_result = $conn->query($category_sql);

            if ($category_result->num_rows > 0) {
                while($row = $category_result->fetch_assoc()) {
            ?>
            <?php
            $link = 'menu.php?category=' . $row['id'];
            if (isset($row['category_type']) && $row['category_type'] === 'cake_customizer') {
                $link = 'customize_cake.php?category=' . $row['id'];
                }
            ?>
            <a href="<?php echo $link; ?>" class="category-card-container hidden">
                        <div class="category-card">
                            <div class="card-face card-front">
                                <img src="img/<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            </div>
                            <div class="card-face card-back">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <span class="btn-view">View Collection</span>
                            </div>
                        </div>
                    </a>
            <?php
                }
            }
            ?>
            </div>
        </section>

        <section id="reviews" class="reviews-section">
            <h2 class="section-title hidden">What Our Customers Say</h2>
            
            <div class="swiper reviews-carousel">
                <div class="swiper-wrapper">

                    <?php if ($thank_you_reviews_result->num_rows > 0): ?>
                        <?php 
                        // Reset the database result pointer to loop through reviews
                        $thank_you_reviews_result->data_seek(0); 
                        while($review = $thank_you_reviews_result->fetch_assoc()): 
                        ?>
                            <div class="swiper-slide">
                                <div class="review-card">
                                    <i class="fas fa-quote-left review-quote-icon"></i>
                                    <div class="review-stars">
                                        <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                                            <i class="far fa-star"></i> <?php endfor; ?>
                                    </div>
                                    <p class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                                    <div class="review-author">- <?php echo htmlspecialchars($review['user_name']); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="swiper-slide">
                            <div class="review-card">
                                <i class="fas fa-quote-left review-quote-icon"></i>
                                <div class="review-stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <p class="review-text">"Be the first to share your experience with Bite Bliss!"</p>
                                <div class="review-author">- The Bite Bliss Team</div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                
                <div class="swiper-pagination"></div>

                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </section>
    </main>

    <?php require_once 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {

    // --- Panel Toggling and Closing Logic ---
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const forgotPasswordOverlay = document.getElementById('forgotPasswordOverlay');
    const forgotPasswordPanel = document.getElementById('forgotPasswordPanel');
    const closeForgotPasswordBtn = document.getElementById('closeForgotPasswordBtn');
    const sidePanel = document.getElementById('sidePanel');
    const loginOverlay = document.getElementById('loginOverlay');

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            sidePanel.classList.remove('is-open');
            loginOverlay.classList.remove('is-open');
            forgotPasswordOverlay.classList.add('is-open');
            forgotPasswordPanel.classList.add('is-open');
        });
    }

    function closeForgotPasswordPanel() {
        forgotPasswordOverlay.classList.remove('is-open');
        forgotPasswordPanel.classList.remove('is-open');
    }
    if (closeForgotPasswordBtn) closeForgotPasswordBtn.addEventListener('click', closeForgotPasswordPanel);
    if (forgotPasswordOverlay) forgotPasswordOverlay.addEventListener('click', closeForgotPasswordPanel);


    // --- SIGN UP OTP LOGIC (FOR EMAIL) ---
    const registerForm = document.getElementById('registerForm');
    const sendSignUpOtpBtn = document.getElementById('sendSignUpOtpBtn');
    const finalRegisterBtn = document.getElementById('finalRegisterBtn');
    const signUpOtpSection = registerForm.querySelector('.otp-section');
    const signUpPasswordField = registerForm.querySelector('input[name="password"]');
    const signUpConfirmPasswordField = registerForm.querySelector('input[name="confirm_password"]');
    const signUpMessage = registerForm.querySelector('.form-message');

    if (sendSignUpOtpBtn) {
        sendSignUpOtpBtn.addEventListener('click', function() {
            const emailInput = registerForm.querySelector('input[name="email"]');
            if (!emailInput.value) {
                signUpMessage.textContent = 'Please enter an email address.';
                signUpMessage.style.color = 'salmon';
                return;
            }
            const formData = new FormData();
            formData.append('email', emailInput.value);
            formData.append('purpose', 'signup');

            sendSignUpOtpBtn.disabled = true;
            sendSignUpOtpBtn.textContent = 'Sending...';

            fetch('send_otp.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                signUpMessage.textContent = data.message;
                if (data.success) {
                    signUpMessage.style.color = 'lightgreen';
                    sendSignUpOtpBtn.style.display = 'none';
                    signUpOtpSection.style.display = 'block';
                    signUpPasswordField.style.display = 'block';
                    signUpConfirmPasswordField.style.display = 'block';
                    finalRegisterBtn.style.display = 'block';
                } else {
                    signUpMessage.style.color = 'salmon';
                    sendSignUpOtpBtn.disabled = false;
                    sendSignUpOtpBtn.textContent = 'Send OTP';
                }
            });
        });
    }


    // --- FORGOT PASSWORD LOGIC (FOR EMAIL) - IMPROVED ---
    const fpForm = document.getElementById('forgotPasswordForm');
    const sendFpOtpBtn = document.getElementById('sendForgotPasswordOtpBtn');
    const fpStep1 = document.getElementById('fpStep1');
    const fpStep2 = document.getElementById('fpStep2');
    const fpMessage = fpForm.querySelector('.form-message');

    if (sendFpOtpBtn) {
        sendFpOtpBtn.addEventListener('click', function() {
            // ... (this part is fine, no changes needed here)
            const emailInput = fpForm.querySelector('input[name="email"]');
            if (!emailInput.value) {
                fpMessage.textContent = 'Please enter an email address.';
                fpMessage.style.color = 'salmon';
                return;
            }
            const formData = new FormData();
            formData.append('email', emailInput.value);
            formData.append('purpose', 'forgot_password');
            sendFpOtpBtn.disabled = true;
            sendFpOtpBtn.textContent = 'Sending...';
            fetch('send_otp.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                fpMessage.textContent = data.message;
                if (data.success) {
                    fpMessage.style.color = 'lightgreen';
                    fpStep1.style.display = 'none';
                    fpStep2.style.display = 'block';
                } else {
                    fpMessage.style.color = 'salmon';
                    sendFpOtpBtn.disabled = false;
                    sendFpOtpBtn.textContent = 'Send OTP';
                }
            });
        });
    }

    if (fpForm) {
        fpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(fpForm);
            const submitBtn = fpForm.querySelector('button[type="submit"]');

            // --- FIX: Disable button on submit ---
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting...';

            fetch('reset_password_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                fpMessage.textContent = data.message;
                if (data.success) {
                    fpMessage.style.color = 'lightgreen';
                    // This is correct: only close the panel on success
                    setTimeout(() => closeForgotPasswordPanel(), 2000);
                } else {
                    // --- FIX: Re-enable button on error ---
                    fpMessage.style.color = 'salmon';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset Password';
                }
            });
        });
    }

    // --- LOGIN FORM SUBMISSION ---
    const loginForm = document.getElementById('loginForm');
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const messageEl = form.querySelector('.form-message');
            const formData = new FormData(form);

            fetch('login_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                messageEl.textContent = data.message;
                if(data.success) {
                    messageEl.style.color = 'lightgreen';
                    window.location.href = (data.user_type === 'admin') ? 'admin/dashboard.php' : 'index.php';
                } else {
                    messageEl.style.color = 'salmon';
                }
            });
        });
    }
    
    // --- FINAL REGISTRATION FORM SUBMISSION (CORRECTED LOGIC) ---
    if(registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const messageEl = form.querySelector('.form-message');
            const mainFormData = new FormData(form);

            if (mainFormData.get('password') !== mainFormData.get('confirm_password')) {
                messageEl.textContent = 'Passwords do not match.';
                messageEl.style.color = 'salmon';
                return;
            }

            // Step 1: Verify the OTP first
            const otpFormData = new FormData();
            otpFormData.append('otp', mainFormData.get('otp'));

            fetch('verify_otp.php', {
                method: 'POST',
                body: otpFormData
            })
            .then(res => res.json())
            .then(otpData => {
                if (otpData.success) {
                    // Step 2: If OTP is correct, proceed with registration
                    fetch('register_handler.php', {
                        method: 'POST',
                        body: mainFormData
                    })
                    .then(res => res.json())
                    .then(registerData => {
                        messageEl.textContent = registerData.message;
                        if(registerData.success) {
                            messageEl.style.color = 'lightgreen';
                            setTimeout(() => { window.location.href = 'index.php'; }, 1500);
                        } else {
                            messageEl.style.color = 'salmon';
                        }
                    });
                } else {
                    // If OTP is incorrect, show the error
                    messageEl.textContent = otpData.message;
                    messageEl.style.color = 'salmon';
                }
            });
        });
    }
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    

    <script src="script.js"></script>

</body>

</html>