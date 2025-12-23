<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Review Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
            z-index: 0;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
        }

        #review-form-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateX(2deg);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.320, 1);
        }

        #review-form-section:hover {
            transform: perspective(1000px) rotateX(0deg) translateY(-10px);
            box-shadow: 
                0 35px 80px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .section-title {
            color: rgba(255, 255, 255, 0.95);
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb);
            border-radius: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            0% { box-shadow: 0 0 10px rgba(255, 107, 107, 0.5); }
            100% { box-shadow: 0 0 20px rgba(72, 219, 251, 0.8); }
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 18px 24px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
            outline: none;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"]:focus, textarea:focus {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 
                inset 0 2px 10px rgba(0, 0, 0, 0.1),
                0 10px 30px rgba(255, 255, 255, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform-style: preserve-3d;
            position: relative;
        }

        .star-rating label::before {
            content: '★';
        }

        .star-rating label:hover {
            transform: scale(1.2) rotateY(10deg);
            color: #ffd700;
            text-shadow: 
                0 0 20px rgba(255, 215, 0, 0.8),
                0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover ~ label {
            color: #ffd700;
            transform: scale(1.1);
            text-shadow: 
                0 0 15px rgba(255, 215, 0, 0.6),
                0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn {
            width: 100%;
            padding: 20px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(-1px) scale(1.01);
        }

        /* Success animation */
        .form-success {
            opacity: 0;
            transform: scale(0.8);
            animation: successPulse 0.6s cubic-bezier(0.23, 1, 0.320, 1) forwards;
        }

        @keyframes successPulse {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Responsive design */
        @media (max-width: 600px) {
            #review-form-section {
                padding: 30px 25px;
                border-radius: 20px;
            }

            .section-title {
                font-size: 2rem;
                margin-bottom: 30px;
            }

            .star-rating label {
                font-size: 2rem;
            }

            input[type="text"], textarea {
                padding: 16px 20px;
            }
        }

        /* Loading animation */
        .btn.loading {
            pointer-events: none;
            position: relative;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <section id="review-form-section" class="content-section">
            <h2 class="section-title">Leave a Review</h2>
            <form id="review-form" action="submit_review.php" method="post">
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
                    <textarea id="review_text" name="review_text" rows="4" placeholder="Share your thoughts and experience..." required></textarea>
                </div>
                
                <button type="submit" class="btn">Submit Review</button>
            </form>
        </section>
    </div>

    <?php
    // Display success/error messages
    if (isset($_GET['status'])) {
        echo '<div id="message-popup" class="message-popup ' . ($_GET['status'] === 'success' ? 'success' : 'error') . '">';
        if ($_GET['status'] === 'success') {
            echo '<div class="message-icon">✓</div>';
            echo '<h3>Review Submitted Successfully!</h3>';
            echo '<p>Thank you for your feedback. Your review has been saved.</p>';
        } else {
            echo '<div class="message-icon">✗</div>';
            echo '<h3>Submission Failed</h3>';
            echo '<p>' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Please try again.') . '</p>';
        }
        echo '</div>';
    }
    ?>

    <style>
        .message-popup {
            position: fixed;
            top: 30px;
            right: 30px;
            padding: 20px 25px;
            border-radius: 16px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            z-index: 1000;
            max-width: 350px;
            transform: translateX(400px);
            animation: slideIn 0.5s cubic-bezier(0.23, 1, 0.320, 1) forwards;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .message-popup.success {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.5);
            color: #ecfdf5;
        }

        .message-popup.error {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #fef2f2;
        }

        .message-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .message-popup h3 {
            margin-bottom: 8px;
            font-weight: 600;
        }

        .message-popup p {
            margin: 0;
            opacity: 0.9;
        }

        @keyframes slideIn {
            to { transform: translateX(0); }
        }

        .form-error {
            border-color: rgba(239, 68, 68, 0.6) !important;
            background: rgba(239, 68, 68, 0.1) !important;
        }

        .error-message {
            color: #fecaca;
            font-size: 0.875rem;
            margin-top: 8px;
            display: block;
        }
    </style>

    <script>
        // Enhanced form interactions
        const form = document.getElementById('review-form');
        const submitBtn = document.querySelector('.btn');
        const formSection = document.getElementById('review-form-section');

        // Client-side validation
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
            const errorDiv = document.createElement('span');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }

        // Add focus effects
        const inputs = document.querySelectorAll('input[type="text"], textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.form-group').style.transform = 'translateZ(10px)';
                this.classList.remove('form-error');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-group').style.transform = 'translateZ(0px)';
            });
        });

        // Star rating animations
        const stars = document.querySelectorAll('.star-rating input');
        const starLabels = document.querySelectorAll('.star-rating label');

        starLabels.forEach((label, index) => {
            label.addEventListener('mouseenter', function() {
                starLabels.forEach((l, i) => {
                    if (i >= (starLabels.length - index - 1)) {
                        l.style.transform = 'scale(1.2) rotateY(10deg)';
                        l.style.color = '#ffd700';
                    }
                });
            });

            label.addEventListener('mouseleave', function() {
                starLabels.forEach(l => {
                    const isChecked = Array.from(stars).some(star => 
                        star.checked && star.nextElementSibling === l
                    );
                    if (!isChecked && !l.matches(':hover ~ label')) {
                        l.style.transform = 'scale(1)';
                        l.style.color = 'rgba(255, 255, 255, 0.3)';
                    }
                });
            });
        });

        // Form submission with validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            submitBtn.classList.add('loading');
            submitBtn.textContent = '';
        });

        // Auto-hide message popup
        const messagePopup = document.getElementById('message-popup');
        if (messagePopup) {
            setTimeout(() => {
                messagePopup.style.animation = 'slideIn 0.5s cubic-bezier(0.23, 1, 0.320, 1) reverse forwards';
                setTimeout(() => messagePopup.remove(), 500);
            }, 4000);
        }

        // Parallax effect on mouse move
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            formSection.style.transform = `
                perspective(1000px) 
                rotateX(${(mouseY - 0.5) * 5}deg) 
                rotateY(${(mouseX - 0.5) * 5}deg)
                translateZ(0)
            `;
        });

        // Reset transform on mouse leave
        document.addEventListener('mouseleave', () => {
            formSection.style.transform = 'perspective(1000px) rotateX(2deg)';
        });
    </script>
</body>
</html>