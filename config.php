<?php
// File: config.php - Updated to remove Google API dependency

// Dynamically determine the base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = ($script_name == '/') ? '/' : $script_name . '/';

define('BASE_URL', $protocol . $host . $base_path);
// Razorpay API Keys (keep these)
define('RAZORPAY_KEY_ID', 'rzp_test_APuQCp0MiHoD9M'); // Replace with your Key ID
define('RAZORPAY_KEY_SECRET', '06kTw2BRDXPQ3FUuhBZTrPXZ'); // Replace with your Key Secret

// Store location for delivery calculations
define('STORE_LAT', 13.0389); // T. Nagar, Chennai latitude
define('STORE_LNG', 80.2347); // T. Nagar, Chennai longitude

// Delivery settings
define('BASE_DELIVERY_CHARGE', 50);     // ₹50 for first 5km
define('DELIVERY_RATE_PER_KM', 8);      // ₹8 per additional km
define('MAX_DELIVERY_DISTANCE', 25);    // Maximum delivery distance in km

// OpenStreetMap settings (no API key required!)
define('NOMINATIM_USER_AGENT', 'Bite Bliss Food Delivery - Contact: your-email@example.com');
define('NOMINATIM_RATE_LIMIT_DELAY', 1); // Seconds between requests (be nice to free service)

// Optional: If you want to use MapBox instead (has more generous free tier)
// define('MAPBOX_ACCESS_TOKEN', 'your_mapbox_token_here');

// Remove Google API key definition - not needed anymore!
// define('GOOGLE_API_KEY', '...');
?>