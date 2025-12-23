<div class="login-overlay" id="forgotPasswordOverlay"></div>
<div class="side-panel" id="forgotPasswordPanel">
    <button class="close-btn" id="closeForgotPasswordBtn"><i class="fas fa-times"></i></button>
    <div class="panel-content">
        <form id="forgotPasswordForm">
            <h1>Reset Password</h1>
            <span>Enter your email to get an OTP</span>
            
            <div id="fpStep1">
                <input type="email" name="email" placeholder="Registered Email Address" required autocomplete="email" />
                <button type="button" id="sendForgotPasswordOtpBtn">Send OTP</button>
            </div>
            
            <div id="fpStep2" style="display: none;">
                <input type="text" name="otp" placeholder="Enter OTP" required autocomplete="one-time-code" />
                <input type="password" name="new_password" placeholder="New Password" required autocomplete="new-password" />
                <button type="submit">Reset Password</button>
            </div>
            
            <p class="form-message"></p>
        </form>
    </div>
</div>