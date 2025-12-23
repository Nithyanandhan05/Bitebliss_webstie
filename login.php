<?php
require_once 'db_connect.php'; // This starts the session and connects to the DB

// If user is already logged in, redirect them to the profile page or homepage
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php'); // or index.php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bite Bliss - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="login_page.css">
    
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    
    <div class="login-wrapper">

        <div class="form-column">
            <div class="login-container">
                <div class="login-header">
                    <img src="img/logo.png" alt="Bite Bliss Logo">
                    <p>Welcome back! Please login to continue</p>
                </div>

                <div class="tabs">
                    <button class="tab-btn active" data-tab="signin">Sign In</button>
                    <button class="tab-btn" data-tab="signup">Sign Up</button>
                </div>

                <div class="form-section">
                    <div class="form-content active" id="signin-form">
                        <form id="loginForm">
                             <div class="input-group">
                                <label>Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="phone_number" placeholder="Enter phone number" required>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>Password</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            <div class="forgot-link">
                                <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                            </div>
                            <button type="submit" class="btn-primary">Sign In</button>
                            <div class="message" id="signin-message"></div>
                        </form>
                    </div>

                    <div class="form-content" id="signup-form">
                        <form id="registerForm">
                            
                            <div id="signup-step-1">
                                <div class="input-group">
                                    <label>Full Name</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-user"></i>
                                        <input type="text" name="username" placeholder="Enter your name" required autocomplete="name">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Email Address</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-envelope"></i>
                                        <input type="email" name="email" placeholder="Enter your email" required autocomplete="email">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Phone Number</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-phone"></i>
                                        <input type="tel" name="phone_number" placeholder="Enter your phone number" required autocomplete="tel">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Create Password</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" name="password" placeholder="Minimum 6 characters" required autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Confirm Password</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" name="confirm_password" placeholder="Re-enter password" required autocomplete="new-password">
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary">Get OTP & Continue</button>
                            </div>

                            <div id="signup-step-2" style="display: none;">
                                <p style="color: rgba(255,255,255,0.8); text-align: center; margin-bottom: 20px;">
                                    An OTP has been sent to your email. Please enter it below.
                                </p>
                                <div class="input-group">
                                    <label>Enter OTP</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-key"></i>
                                        <input type="text" name="otp" placeholder="6-digit code" maxlength="6" autocomplete="one-time-code">
                                    </div>
                                </div>
                                <button type="button" id="verifyAndRegisterBtn" class="btn-primary">Verify & Create Account</button>
                                <div class="resend-container">
                                    <span>Didn't receive code?</span> 
                                    <button type="button" id="resendOtpBtn" disabled>
                                        Resend OTP <span id="resendTimer">30</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                         <div class="message" id="signup-message"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="branding-column">
        </div>

    </div>
    
    <?php include 'login_panel.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- COMMON ELEMENTS ---
        const signinForm = document.getElementById('signin-form');
        const signupForm = document.getElementById('signup-form');
        const signupMessage = document.getElementById('signup-message');
        
        // --- TAB SWITCHING ---
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                signinForm.classList.toggle('active', tab === 'signin');
                signupForm.classList.toggle('active', tab === 'signup');
            });
        });

        function showMessage(element, text, type) {
            element.textContent = text;
            element.className = `message show ${type}`;
        }

        // --- FORGOT PASSWORD MODAL LOGIC ---
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const forgotPasswordOverlay = document.getElementById('forgotPasswordOverlay');
        const forgotPasswordPanel = document.getElementById('forgotPasswordPanel');
        const closeForgotPasswordBtn = document.getElementById('closeForgotPasswordBtn');

        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
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

        const fpForm = document.getElementById('forgotPasswordForm');
        if (fpForm) {
            const sendFpOtpBtn = document.getElementById('sendForgotPasswordOtpBtn');
            const fpStep1 = document.getElementById('fpStep1');
            const fpStep2 = document.getElementById('fpStep2');
            const fpMessage = fpForm.querySelector('.form-message');

            if (sendFpOtpBtn) {
                sendFpOtpBtn.addEventListener('click', function() {
                    const emailInput = fpForm.querySelector('input[name="email"]');
                    if (!emailInput.value) {
                        fpMessage.textContent = 'Please enter an email address.'; return;
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
                            fpStep1.style.display = 'none';
                            fpStep2.style.display = 'block';
                        } else {
                            sendFpOtpBtn.disabled = false;
                            sendFpOtpBtn.textContent = 'Send OTP';
                        }
                    });
                });
            }

            fpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(fpForm);
                const submitBtn = fpForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Resetting...';

                fetch('reset_password_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    fpMessage.textContent = data.message;
                    if (data.success) {
                        setTimeout(() => closeForgotPasswordPanel(), 2000);
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Reset Password';
                    }
                });
            });
        }

        // --- NEW SIGNUP FLOW LOGIC ---
        const registerForm = document.getElementById('registerForm');
        const step1Div = document.getElementById('signup-step-1');
        const step2Div = document.getElementById('signup-step-2');
        const verifyBtn = document.getElementById('verifyAndRegisterBtn');
        const resendBtn = document.getElementById('resendOtpBtn');
        let resendInterval;

        function startResendTimer() {
            let timeLeft = 30;
            resendBtn.disabled = true;
            const timerSpan = document.getElementById('resendTimer');
            if(timerSpan) timerSpan.textContent = timeLeft;

            resendInterval = setInterval(() => {
                timeLeft--;
                if(timerSpan) timerSpan.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(resendInterval);
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = 'Resend OTP';
                }
            }, 1000);
        }

        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = step1Div.querySelector('button[type="submit"]');
            const formData = new FormData(registerForm);

            if (formData.get('password') !== formData.get('confirm_password')) {
                showMessage(signupMessage, 'Passwords do not match.', 'error'); return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            fetch('send_signup_otp.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMessage(signupMessage, data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    step1Div.style.display = 'none';
                    step2Div.style.display = 'block';
                    startResendTimer();
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Get OTP & Continue';
            });
        });

        verifyBtn.addEventListener('click', function() {
            const otpInput = step2Div.querySelector('input[name="otp"]');
            if (!otpInput.value) {
                showMessage(signupMessage, 'Please enter the OTP.', 'error'); return;
            }
            
            const formData = new FormData();
            formData.append('otp', otpInput.value);

            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            fetch('register_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMessage(signupMessage, data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => { window.location.href = 'index.php'; }, 1500);
                } else {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify & Create Account';
                }
            });
        });
        
        resendBtn.addEventListener('click', function() {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            fetch('send_signup_otp.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                showMessage(signupMessage, data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    resendBtn.innerHTML = `Resend OTP (<span id="resendTimer">30</span>s)`;
                    startResendTimer();
                } else {
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend OTP';
                }
            });
        });
        
        // --- LOGIN FORM SUBMISSION ---
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const messageEl = document.getElementById('signin-message');
                const formData = new FormData(loginForm);
                fetch('login_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    showMessage(messageEl, data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        window.location.href = (data.user_type === 'admin') ? 'admin/index.php' : 'index.php';

                    }
                });
            });
        }
    });
    </script>
</body>
</html>