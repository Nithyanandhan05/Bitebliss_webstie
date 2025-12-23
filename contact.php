<?php 
// Add this to the VERY TOP of contact.php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bite Bliss</title>
    <link rel="icon" type="image/png" href="img/logo_tag.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="login.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --purple: #975ab7;
            --brown: #502325;
            --main-bg: var(--purple);
            --text-color: var(--brown);
            --card-bg: rgba(80, 35, 37, 0.05);
            --card-border: rgba(80, 35, 37, 0.15);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--purple) 0%, #7a4a99 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .contact-hero {
            background: linear-gradient(135deg, rgba(151, 90, 183, 0.9), rgba(122, 74, 153, 0.9));
            padding: 80px 20px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(80, 35, 37, 0.3);
            position: relative;
            z-index: 2;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(255,255,255,0.9);
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin: 0 auto;
        }

        .main-container {
            max-width: 1200px;
            margin: -60px auto 0;
            padding: 0 20px 60px;
            position: relative;
            z-index: 3;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 40px;
            margin-top: 40px;
        }

        .contact-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(80, 35, 37, 0.1);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--purple), #7a4a99, var(--brown));
            border-radius: 24px 24px 0 0;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(80, 35, 37, 0.15);
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--purple);
            font-size: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 16px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .contact-item:hover {
            background: rgba(151, 90, 183, 0.1);
            border-left-color: var(--purple);
            transform: translateX(5px);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--purple), #7a4a99);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 8px 25px rgba(151, 90, 183, 0.3);
        }

        .contact-text {
            flex: 1;
        }

        .contact-label {
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .contact-value {
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--card-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            color: var(--text-color);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(151, 90, 183, 0.1);
            background: white;
        }

        .form-input::placeholder {
            color: rgba(80, 35, 37, 0.5);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #8A63D2, #667eea);
            color: white;
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(138, 99, 210, 0.4);
        }

        .business-hours {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(138, 99, 210, 0.1));
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            border: 1px solid rgba(138, 99, 210, 0.2);
        }

        .hours-title {
            font-weight: 700;
            color:var(--brown);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hours-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(138, 99, 210, 0.1);
        }

        .hours-item:last-child {
            border-bottom: none;
        }

        .day {
            font-weight: 600;
            color: #502325;
        }

        .time {
            color: #8A63D2;
            font-weight: 500;
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: floatUp 15s infinite linear;
        }

        .floating-circle:nth-child(1) {
            width: 60px;
            height: 60px;
            left: 10%;
            animation-delay: -5s;
            animation-duration: 20s;
        }

        .floating-circle:nth-child(2) {
            width: 40px;
            height: 40px;
            left: 80%;
            animation-delay: -15s;
            animation-duration: 25s;
        }

        .floating-circle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 60%;
            animation-delay: -10s;
            animation-duration: 18s;
        }

        @keyframes floatUp {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .success-message {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .contact-card {
                padding: 30px 25px;
            }
            
            .main-container {
                margin-top: -40px;
            }
        }

        .interactive-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(80, 35, 37, 0.08);
            animation: pulse 4s ease-in-out infinite;
        }

        .bg-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            left: -10%;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 60%;
            right: -5%;
            animation-delay: 2s;
        }

        .bg-circle:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.1;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <br>
    <br>
    <br>
    <br> 
    <br>
    <br>
    <div class="interactive-bg">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <header class="contact-hero">
        <h1 class="hero-title">Let's Connect</h1>
        <p class="hero-subtitle">We'd love to hear from you! Reach out with any questions, feedback, or just to say hello.</p>
    </header>

    <main class="main-container">
        <div class="contact-grid">
            <div class="contact-card">
                <h2 class="card-title">
                    <i class="fas fa-address-card"></i>
                    Get In Touch
                </h2>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Location</div>
                        <div class="contact-value">Chennai, Tamil Nadu</div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Phone</div>
                        <div class="contact-value">+91 8438425634</div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Email</div>
                        <div class="contact-value">support@bitebliss.shop</div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">WhatsApp</div>
                        <div class="contact-value">Chat with us instantly</div>
                    </div>
                </div>
            </div>

            <div class="contact-card">
                <h2 class="card-title">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </h2>

                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    Thank you! Your message has been sent successfully.
                </div>

                <form id="contactForm" action="send_email.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-input" required placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required placeholder="your.email@example.com">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-input" required placeholder="What's this about?">
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-input form-textarea" required placeholder="Tell us what's on your mind..."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Form submission handling with animation
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show success message
            const successMessage = document.getElementById('successMessage');
            successMessage.style.display = 'flex';
            
            // Reset form after a delay
            setTimeout(() => {
                this.reset();
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
            }, 1000);
        });

        // Interactive mouse movement effect
        document.addEventListener('mousemove', function(e) {
            const circles = document.querySelectorAll('.bg-circle');
            const mouseX = e.clientX;
            const mouseY = e.clientY;

            circles.forEach((circle, index) => {
                const speed = (index + 1) * 0.002;
                const x = (mouseX - window.innerWidth / 2) * speed;
                const y = (mouseY - window.innerHeight / 2) * speed;
                
                circle.style.transform = `translate(${x}px, ${y}px) scale(${1 + speed})`;
            });
        });

        // Add input focus animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Add clicking effect to contact items
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', function() {
                const value = this.querySelector('.contact-value').textContent;
                
                if (value.includes('@')) {
                    window.location.href = `mailto:${value}`;
                } else if (value.includes('+91')) {
                    window.location.href = `tel:${value}`;
                } else if (value.includes('WhatsApp')) {
                    window.open('https://wa.me/918438425634', '_blank');
                }
            });
        });
    </script>
</body>
</html>