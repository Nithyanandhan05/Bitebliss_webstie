<?php
// Start session for better error handling
session_start();

// Include the database connection file
require_once 'db_connect.php';

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to validate input data
function validateInput($name, $rating, $review) {
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long";
    } elseif (strlen($name) > 100) {
        $errors[] = "Name must be less than 100 characters";
    }
    
    // Validate rating
    if (empty($rating)) {
        $errors[] = "Rating is required";
    } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5";
    }
    
    // Validate review text
    if (empty($review)) {
        $errors[] = "Review text is required";
    } elseif (strlen($review) < 10) {
        $errors[] = "Review must be at least 10 characters long";
    } elseif (strlen($review) > 1000) {
        $errors[] = "Review must be less than 1000 characters";
    }
    
    return $errors;
}

// Function to redirect with status
function redirectWithStatus($status, $message = '') {
    // Check if a custom redirect URL is provided
    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : 'review_form.php?status=';
    
    if (strpos($redirect_url, '?') !== false) {
        $url = $redirect_url . $status;
    } else {
        $url = $redirect_url . '?status=' . $status;
    }
    
    if (!empty($message)) {
        $url .= '&message=' . urlencode($message);
    }
    header("Location: $url");
    exit();
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if database connection exists
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // Sanitize input data
        $user_name = sanitizeInput($_POST['user_name'] ?? '');
        $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
        $review_text = sanitizeInput($_POST['review_text'] ?? '');
        
        // Validate input data
        $validation_errors = validateInput($user_name, $rating, $review_text);
        
        if (!empty($validation_errors)) {
            $error_message = implode(', ', $validation_errors);
            redirectWithStatus('error', $error_message);
        }
        
        // Additional security: Check for suspicious patterns
        $suspicious_patterns = ['<script', 'javascript:', 'onload=', 'onerror='];
        $combined_input = $user_name . ' ' . $review_text;
        
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($combined_input, $pattern) !== false) {
                redirectWithStatus('error', 'Invalid input detected');
            }
        }
        
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("INSERT INTO reviews (user_name, rating, review_text, created_at) VALUES (?, ?, ?, NOW())");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("sis", $user_name, $rating, $review_text);
        
        if ($stmt->execute()) {
            // Log successful submission (optional)
            error_log("Review submitted successfully by: " . $user_name);
            
            // Close statement
            $stmt->close();
            
            // Redirect with success status
            redirectWithStatus('success');
            
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Review submission error: " . $e->getMessage());
        
        // Close statement if it exists
        if (isset($stmt)) {
            $stmt->close();
        }
        
        // Redirect with error status
        redirectWithStatus('error', 'A system error occurred. Please try again.');
    }
    
} else {
    // If someone tries to access this file directly
    redirectWithStatus('error', 'Invalid access method');
}

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>