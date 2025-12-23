<?php
// THIS MUST BE THE VERY FIRST THING IN THE FILE - NO BLANK LINES OR SPACES BEFORE IT

// Start the session here to ensure it's always active.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the configuration file
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "bitebliss_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>