<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Prevent raw warnings in output

session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php';

use Razorpay\Api\Api;

// AJAX Handler - Must be at the very top before any HTML output
$input = [];
$action = null;

// Check content type to decide how to parse the body
$contentType = trim($_SERVER["CONTENT_TYPE"] ?? '');

if (strpos($contentType, 'application/json') !== false) {
    $json_payload = file_get_contents('php://input');
    $input = json_decode($json_payload, true);
    
    // Handle cases where json_decode fails on an empty string etc.
    if (!is_array($input)) {
        $input = [];
    }
    $action = $input['action'] ?? null;

} else {
    // Assume form-data and rely on $_REQUEST which handles POST, GET etc.
    $action = $_REQUEST['action'] ?? null;
}


if ($action) {
    // Clean any output buffers to prevent HTML in JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start output buffering to catch any unexpected output
    ob_start();
    
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => false, 'message' => 'Invalid action'];

    if ($action === 'calculate_delivery') {
        try {
            // Get JSON input
            
            if (!$input || !isset($input['address_id'])) {
                throw new Exception("Address ID is required");
            }

            $address_id = intval($input['address_id']);
            
            // Get address coordinates from database
            $stmt = $conn->prepare("SELECT latitude, longitude, city, state FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$result) {
                throw new Exception("Address not found");
            }

            // Check if we have coordinates
            if (!empty($result['latitude']) && !empty($result['longitude'])) {
                $destinationLat = floatval($result['latitude']);
                $destinationLng = floatval($result['longitude']);
                
                // Store coordinates (T. Nagar, Chennai)
                $storeLat = 13.0525;
                $storeLng = 80.0604;

                // Calculate distance using OSRM API
                $osrmUrl = "http://router.project-osrm.org/route/v1/driving/{$storeLng},{$storeLat};{$destinationLng},{$destinationLat}?overview=false&geometries=geojson";
                
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => [
                            'User-Agent: BiteBliss-DeliveryCalculator/1.0',
                            'Accept: application/json'
                        ],
                        'timeout' => 15
                    ]
                ]);
                
                $osrmResponse = @file_get_contents($osrmUrl, false, $context);
                
                if ($osrmResponse === false) {
                    // Fallback to Haversine formula
                    $distance_km = calculateHaversineDistance($storeLat, $storeLng, $destinationLat, $destinationLng);
                    $delivery_charge = calculateDeliveryCharge($distance_km);
                    
                    $response = [
                        'success' => true,
                        'charge' => $delivery_charge,
                        'distance' => round($distance_km, 2),
                        'message' => 'Calculated using direct distance (routing service unavailable)'
                    ];
                } else {
                    $osrmData = json_decode($osrmResponse, true);
                    
                    if ($osrmData && isset($osrmData['routes'][0]['distance'])) {
                        $distance_km = $osrmData['routes'][0]['distance'] / 1000; // Convert meters to km
                        $delivery_charge = calculateDeliveryCharge($distance_km);
                        
                        $response = [
                            'success' => true,
                            'charge' => $delivery_charge,
                            'distance' => round($distance_km, 2),
                            'message' => 'Distance-based delivery charge calculated'
                        ];
                    } else {
                        throw new Exception("Invalid routing response");
                    }
                }
                
            } else {
                // No coordinates available - use standard charge
                $response = [
                    'success' => true,
                    'charge' => 20.00,
                    'message' => 'Standard delivery charge (coordinates not available)'
                ];
            }

        } catch (Exception $e) {
            error_log("Delivery calculation error: " . $e->getMessage());
            
            // Return standard charge on any error
            $response = [
                'success' => true,
                'charge' => 20.00,
                'message' => 'Standard delivery charge applied: ' . $e->getMessage()
            ];
        }
    }
    
    // ADDRESS SAVING ACTION
    elseif ($action === 'save_address') {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }
            
            $user_id = intval($_SESSION['user_id']);
            $required_fields = ['flat_house_no', 'area_street', 'city', 'state', 'pincode', 'phone_number', 'full_name'];
            
            // Validate required fields
            foreach ($required_fields as $field) {
                $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
                if (empty($value)) {
                    throw new Exception("Field '{$field}' is required and cannot be empty");
                }
            }

            // Validate pincode format
            $pincode = trim($_POST['pincode']);
            if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
                throw new Exception("Please enter a valid 6-digit pincode");
            }

            // Validate phone number format
            $phone = trim($_POST['phone_number']);
            if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
                throw new Exception("Please enter a valid 10-digit mobile number");
            }

            // Handle default address - use transaction for consistency
            $conn->autocommit(FALSE);
            
            try {
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                if ($is_default) {
                    $update_stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                    if (!$update_stmt) {
                        throw new Exception("Database prepare failed: " . $conn->error);
                    }
                    $update_stmt->bind_param("i", $user_id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Failed to update default addresses: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                }

                // Insert new address
                $stmt = $conn->prepare("
                    INSERT INTO user_addresses 
                    (user_id, full_name, phone_number, flat_house_no, area_street, landmark, city, state, pincode, is_default, latitude, longitude, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if (!$stmt) {
                    throw new Exception("Database prepare failed: " . $conn->error);
                }
                
                $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
                $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
                $landmark = isset($_POST['landmark']) ? trim($_POST['landmark']) : '';
                
                $stmt->bind_param("issssssssidd", 
                    $user_id,
                    trim($_POST['full_name']),
                    $phone,
                    trim($_POST['flat_house_no']),
                    trim($_POST['area_street']),
                    $landmark,
                    trim($_POST['city']),
                    trim($_POST['state']),
                    $pincode,
                    $is_default,
                    $latitude,
                    $longitude
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save address: " . $stmt->error);
                }
                
                $address_id = $conn->insert_id;
                $stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Address saved successfully',
                    'address_id' => $address_id
                ];
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            } finally {
                $conn->autocommit(TRUE);
            }
            
        } catch (Exception $e) {
            error_log("Address save error: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    // Clean any unexpected output and send JSON
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        error_log("Unexpected output in AJAX handler: " . $unexpected_output);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if (isset($conn)) $conn->close();
    exit();
}

// Helper functions for distance calculation
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Earth's radius in kilometers
    
    $dlat = deg2rad($lat2 - $lat1);
    $dlon = deg2rad($lon2 - $lon1);
    
    $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

function calculateDeliveryCharge($distance_km) {
    // New tiered delivery pricing logic
    // 0-5 km: ₹20
    // 5-10 km: ₹30
    // 10-15 km: ₹40
    // 15+ km: ₹50 (max charge)

    if ($distance_km <= 5) {
        return 20.00;
    } elseif ($distance_km <= 10) {
        return 30.00;
    } elseif ($distance_km <= 15) {
        return 40.00;
    } else { // Anything over 15 km
        return 50.00;
    }
}

// =================================================================
// --- NORMAL PAGE LOAD LOGIC (IF NOT AN AJAX REQUEST) ---
// =================================================================

// --- Security & Session Checks ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to proceed to checkout.";
    header('Location: cart.php');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// --- Fetch User and Cart Data ---
$user_data = null;
$user_addresses = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Fetch user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Fetch user addresses
    $addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    if ($addr_stmt) {
        $addr_stmt->bind_param("i", $user_id);
        $addr_stmt->execute();
        $addr_result = $addr_stmt->get_result();
        while ($row = $addr_result->fetch_assoc()) {
            $user_addresses[] = $row;
        }
        $addr_stmt->close();
    }
}

$default_address = $user_addresses[0] ?? null;

// --- Process Cart Items ---
$cart_items = [];
$subtotal = 0;
$products_data = [];

if (!empty($_SESSION['cart'])) {
    // 1. Get all product IDs from the cart
    $all_product_ids = array_column($_SESSION['cart'], 'product_id');
    
    // 2. Filter out only the numeric IDs for the database query
    $numeric_product_ids = array_filter($all_product_ids, 'is_numeric');
    
    if (!empty($numeric_product_ids)) {
        // Fetch data ONLY for the standard products that exist in the database
        $id_string = implode(',', array_map('intval', $numeric_product_ids));
        $sql = "SELECT * FROM products WHERE id IN ($id_string)";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products_data[$row['id']] = $row;
            }
        }
    }
    
    // 3. Loop through the original cart to build the display list
    foreach ($_SESSION['cart'] as $item) {
        if (!is_array($item) || !isset($item['product_id'])) {
            continue; // Skip malformed items
        }
        
        $item_id = $item['product_id'];
        $price_per_unit = $item['price_per_unit'];
        $quantity = $item['quantity'];
        
        // Add to the subtotal regardless of item type
        $subtotal += $price_per_unit * $quantity;
        
        // ** Check if it's the custom box **
        if ($item_id === 'custom_box') {
            // Manually build the custom box item for display
            $cart_items[] = [
                'id'        => 'custom_box',
                'name'      => 'Custom Brownie Box',
                'price'     => $price_per_unit,
                'image_url' => 'img/custom_box_placeholder.png',
                'quantity'  => $quantity,
                'options'   => $item['options'] ?? []
            ];
        } 
        // ** Check if it's a standard product we fetched data for **
        elseif (isset($products_data[$item_id])) { 
            $product = $products_data[$item_id];
            $cart_items[] = [
                'id'        => $item_id,
                'name'      => $product['name'],
                'price'     => $price_per_unit,
                'image_url' => $product['image_url'],
                'quantity'  => $quantity,
                'options'   => $item['options'] ?? []
            ];
        }
    }
}

// NOTE: These totals are initial values. JavaScript will update them dynamically.
$delivery_charge = 20.00;
$tax_amount = $subtotal * 0.05; // 5% tax
$total = $subtotal + $delivery_charge + $tax_amount;

// IMPORTANT: Razorpay order creation has been moved to `create_razorpay_order.php`
// and is now called by JavaScript just before payment to ensure the correct total amount.

if (isset($conn)) $conn->close();

// Helper function to format address
function format_address($addr) {
    if (!$addr) return '';
    $landmark = !empty($addr['landmark']) ? htmlspecialchars($addr['landmark']) . ",<br>" : "";
    return htmlspecialchars($addr['flat_house_no']) . ", " . htmlspecialchars($addr['area_street']) . ",<br>" . $landmark . htmlspecialchars($addr['city']) . ", " . htmlspecialchars($addr['state']) . " - " . htmlspecialchars($addr['pincode']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bite Bliss</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="checkout.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .item-options-summary { 
            font-size: 0.8rem; 
            color: #777; 
            margin-top: 5px; 
            padding-left: 5px; 
        }
        .item-options-summary p { 
            margin: 2px 0; 
            line-height: 1.4; 
        }
        
        /* --- Styles for Address Management Features --- */
        .address-display-box { 
            padding: 1rem; 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
        }
        .address-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 0.5rem; 
        }
        .address-header h4 { margin: 0; }
        .address-details { color: #555; line-height: 1.6; }
        .change-address-btn { 
            color: var(--primary); 
            text-decoration: none; 
            font-weight: 600; 
            cursor: pointer;
        }
        .change-address-btn:hover { text-decoration: underline; }
        
        /* Modal Styles */
        .modal { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            z-index: 1050; 
            display: none; 
            align-items: center; 
            justify-content: center; 
        }
        .modal.is-open { display: flex; }
        .modal-content { 
            background: white; 
            border-radius: 12px; 
            max-width: 800px; 
            width: 90%; 
            max-height: 90vh; 
            display: flex; 
            flex-direction: column; 
        }
        .modal-header { 
            padding: 1rem 1.5rem; 
            border-bottom: 1px solid #eee; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        .modal-close-btn:hover { color: #000; }
        .modal-body { 
            padding: 1.5rem; 
            overflow-y: auto; 
        }
        .modal-footer { 
            padding: 1rem 1.5rem; 
            border-top: 1px solid #eee; 
            text-align: right; 
        }
        .address-list .address-item { 
            padding: 1rem; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
            cursor: pointer; 
            position: relative;
        }
        .address-list .address-item:hover {
            border-color: var(--primary);
        }
        .address-list .address-item.selected { 
            border-color: var(--primary); 
            background: #fdf8ff; 
            box-shadow: 0 0 0 2px rgba(151, 90, 183, 0.2); 
        }
        .address-list .address-item input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .address-list .address-item label {
            cursor: pointer;
            display: block;
            width: 100%;
        }
        .add-new-address-form { 
            display: none; 
        }
        .add-new-address-form h4 {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .form-grid-2 { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 1rem; 
        }
        .add-new-address-form input,
        .add-new-address-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        .add-new-address-form label {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .add-new-address-form label input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
            margin-bottom: 0;
        }
        .add-new-address-form button {
            background: #bd80e6ff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 1rem;
        }
        .add-new-address-form button:hover {
            background: #7c4a9d;
        }
        .add-new-address-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            margin: 1rem 0;
        }
        .add-new-address-link:hover {
            text-decoration: underline;
        }
        .cancel-add-address {
            color: #666;
            text-decoration: none;
            cursor: pointer;
        }
        .cancel-add-address:hover {
            text-decoration: underline;
        }
        
        /* OpenStreetMap Styles */
        #map-canvas {
            height: 350px; 
            width: 100%; 
            margin-bottom: 1rem; 
            border-radius: 8px; 
            border: 1px solid #ddd;
            z-index: 1;
        }
        
        #map-search-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        
        /* Address Search Suggestions */
        .search-container {
            position: relative;
        }
        
        .address-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .address-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        
        .address-suggestion:hover {
            background: #f5f5f5;
        }
        
        .address-suggestion:last-child {
            border-bottom: none;
        }
        
        /* Loading and Button States */
        .btn-loader {
            display: none;
        }
        .loader-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #975ab7;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced CSS for the address form */
        
        /* Location Detection Section */
        .location-detection-section {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4fd 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e1e8ff;
        }
        
        .current-location-btn {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
            min-width: 200px;
        }
        
        .current-location-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(66, 133, 244, 0.4);
        }
        
        .current-location-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .location-help-text, .map-help-text {
            font-size: 0.85rem;
            color: #666;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .location-help-text i, .map-help-text i {
            color: #4285f4;
        }
        
        /* Map Section */
        .map-section {
            margin-bottom: 2rem;
        }
        
        #map-search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e8ff;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        #map-search-input:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }
        
        #map-canvas {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            border: 2px solid #e1e8ff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.75rem;
        }
        
        /* Address Form Fields */
        .address-form-fields {
            background: #fafbff;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e1e8ff;
        }
        
        .address-form-fields h5 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .address-form-fields input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .address-form-fields input[type="text"]:focus {
            outline: none;
            border-color: #975ab7;
            box-shadow: 0 0 0 3px rgba(151, 90, 183, 0.1);
            transform: translateY(-1px);
        }
        
        .address-form-fields input[type="text"]:valid {
            border-color: #34a853;
        }
        
        /* Default Address Checkbox */
        .default-address-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            cursor: pointer;
            font-size: 14px;
            color: #555;
        }
        
        .default-address-checkbox input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 0.75rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .default-address-checkbox input[type="checkbox"]:checked + .checkmark {
            background: #975ab7;
            border-color: #975ab7;
        }
        
        .default-address-checkbox input[type="checkbox"]:checked + .checkmark:after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .save-address-btn {
            background: linear-gradient(135deg, #975ab7 0%, #7c4a9d 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(151, 90, 183, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .save-address-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(151, 90, 183, 0.4);
        }
        
        .save-address-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .cancel-add-address {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .cancel-add-address:hover {
            color: #333;
            background: #f0f0f0;
            text-decoration: none;
        }
        
        /* Success/Error States */
        .input-success {
            border-color: #34a853 !important;
            background: #f8fff8;
        }
        
        .input-error {
            border-color: #ea4335 !important;
            background: #fff8f8;
        }
        
        /* Highlight Animation */
        @keyframes highlightForm {
            0% { background-color: #f8fff8; }
            100% { background-color: transparent; }
        }
        
        .address-form-fields.highlight {
            animation: highlightForm 2s ease-out;
        }
        
        /* Address Display Box Enhancements */
        .address-display-box {
            background: linear-gradient(135deg, #fafbff 0%, #f0f4ff 100%);
            border: 2px solid #e1e8ff;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .address-display-box:hover {
            border-color: #975ab7;
            box-shadow: 0 4px 12px rgba(151, 90, 183, 0.1);
        }
        
        .change-address-btn {
            background: #975ab7;
            color: white !important;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .change-address-btn:hover {
            background: #7c4a9d;
            transform: translateY(-1px);
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 6px 20px rgba(66, 133, 244, 0.5); }
            100% { transform: scale(1); box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3); }
        }
        
        @media (max-width: 768px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
            .current-location-btn {
                min-width: auto;
                width: 100%;
                padding: 14px 20px;
            }
            #map-canvas {
                height: 250px;
            }
            .form-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            .save-address-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="checkout-page-body">
    <?php include 'header.php'; ?>
    
    <main class="checkout-container">
        <div class="progress-indicator">
            <div class="progress-step active current">
                <div class="step-number">1</div>
                <span>Details</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="step-number">2</div>
                <span>Payment</span>
            </div>
        </div>
        
        <div class="checkout-content">
            <div class="checkout-form-section">
                <div class="section-header">
                    <h2><i class="fas fa-truck"></i> Delivery Address</h2>
                </div>
                
                <div id="addressDisplayBox" class="address-display-box">
                    <div class="address-header">
                        <h4>Your Delivery Address</h4>
                        <a href="#" id="changeAddressBtn" class="change-address-btn">Change</a>
                    </div>
                    <div id="addressDetails" class="address-details">
                        <?php if($default_address): ?>
                            <strong><?php echo htmlspecialchars($default_address['full_name']); ?></strong><br>
                            <?php echo format_address($default_address); ?>
                        <?php else: ?>
                            <p>Please add a delivery address.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form id="checkoutForm" action="verify.php" method="POST">
                    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                    <input type="hidden" name="address_id" id="form_address_id" value="<?php echo $default_address['id'] ?? '0'; ?>">
                    <input type="hidden" name="full_name" id="form_full_name" value="<?php echo htmlspecialchars($default_address['full_name'] ?? $user_data['username']); ?>">
                    <input type="hidden" name="phone_number" id="form_phone_number" value="<?php echo htmlspecialchars($default_address['phone_number'] ?? $user_data['phone_number']); ?>">
                    <input type="hidden" name="pincode" id="form_pincode" value="<?php echo htmlspecialchars($default_address['pincode'] ?? ''); ?>">
                    <input type="hidden" name="flat_house_no" id="form_flat_house_no" value="<?php echo htmlspecialchars($default_address['flat_house_no'] ?? ''); ?>">
                    <input type="hidden" name="area_street" id="form_area_street" value="<?php echo htmlspecialchars($default_address['area_street'] ?? ''); ?>">
                    <input type="hidden" name="landmark" id="form_landmark" value="<?php echo htmlspecialchars($default_address['landmark'] ?? ''); ?>">
                    <input type="hidden" name="city" id="form_city" value="<?php echo htmlspecialchars($default_address['city'] ?? ''); ?>">
                    <input type="hidden" name="state" id="form_state" value="<?php echo htmlspecialchars($default_address['state'] ?? ''); ?>">
                    
                    <div class="form-group">
                        <label for="special_instructions"><i class="fas fa-sticky-note"></i> Special Instructions (Optional)</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3" placeholder="Add any special delivery instructions..." class="form-input"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="order-summary-section">
                <div class="order-summary-card">
                    <div class="summary-header">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                        <span class="item-count"><?php echo count($cart_items); ?> items</span>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <span class="quantity-badge"><?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <?php if (!empty($item['options'])): ?>
                                        <div class="item-options-summary">
                                            <?php foreach ($item['options'] as $name => $value): ?>
                                                <p>
                                                    <strong><?php echo htmlspecialchars($name); ?>:</strong>
                                                    <?php
                                                    if (is_array($value)) {
                                                        echo htmlspecialchars(implode(', ', $value));
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?> each</div>
                                </div>
                                <div class="item-total">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="bill-details">
                        <h4><i class="fas fa-file-invoice-dollar"></i> Bill Details</h4>
                        <div class="bill-row">
                            <span>Item Total</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="bill-row">
                            <span>Delivery Fee</span>
                            <span id="delivery-charge-display">₹<?php echo number_format($delivery_charge, 2); ?></span>
                        </div>
                        <div class="bill-row">
                            <span>Taxes & Fees</span>
                            <span>₹<?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                        <div class="bill-row total">
                            <span>Total Amount</span>
                            <span id="total-amount-display">₹<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                    
                    <button id="rzp-button1" class="proceed-btn">
                        <span class="btn-text"><i class="fas fa-shield-alt"></i> Pay Securely</span>
                        <div class="btn-loader"><div class="loader-spinner"></div></div>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <div id="addressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select a delivery address</h3>
                <button class="modal-close-btn" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div class="address-list">
                    <?php foreach($user_addresses as $addr): ?>
                        <div class="address-item <?php echo $addr['is_default'] ? 'selected' : ''; ?>" data-address-id="<?php echo $addr['id']; ?>">
                            <input type="radio" name="selected_address" value="<?php echo $addr['id']; ?>" id="addr_<?php echo $addr['id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?>>
                            <label for="addr_<?php echo $addr['id']; ?>">
                                <strong><?php echo htmlspecialchars($addr['full_name']); ?></strong><br>
                                <?php echo format_address($addr); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="#" id="showAddAddressFormBtn" class="add-new-address-link">+ Add a new address</a>
                
                <form id="addNewAddressForm" class="add-new-address-form">
                    <input type="hidden" name="action" value="save_address">
                    <h4>📍 Pin your location on the map</h4>
                    
                    <div class="location-detection-section">
                        <button type="button" id="useCurrentLocationBtn" class="current-location-btn" onclick="detectCurrentLocation()">
                            <i class="fas fa-location-arrow"></i> Use current location
                        </button>
                        <p class="location-help-text">
                            <i class="fas fa-info-circle"></i> 
                            Allow location access for automatic address detection, or search manually below
                        </p>
                    </div>
                    
                    <div class="map-section">
                        <div class="search-container">
                            <input id="map-search-input" type="text" placeholder="🔍 Search for area, street name, or landmark...">
                            <div id="address-suggestions" class="address-suggestions"></div>
                        </div>
                        <div id="map-canvas"></div>
                        <p class="map-help-text">
                            <i class="fas fa-hand-pointer"></i> 
                            Drag the red pin to adjust the exact location, or click anywhere on the map
                        </p>
                    </div>
                    
                    <div class="address-form-fields">
                        <h5>📝 Complete your address details</h5>
                        
                        <input type="text" name="flat_house_no" placeholder="🏠 Flat, House no., Building, Floor*" required>
                        
                        <input type="text" name="area_street" placeholder="🛣️ Area, Street, Sector, Village*" required>
                        
                        <input type="text" name="landmark" placeholder="📍 Landmark (e.g. near hospital, opposite mall)">
                        
                        <div class="form-grid-2">
                            <input type="text" name="city" placeholder="🏙️ Town/City*" required>
                            <input type="text" name="state" placeholder="🗺️ State*" required>
                        </div>
                        
                        <div class="form-grid-2">
                            <input type="text" name="pincode" placeholder="📮 Pincode*" required pattern="[1-9][0-9]{5}" title="Enter a valid 6-digit pincode">
                            <input type="text" name="phone_number" placeholder="📱 Mobile Number*" required pattern="[6-9][0-9]{9}" title="Enter a valid 10-digit mobile number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>">
                        </div>
                        
                        <input type="hidden" name="full_name" required value="<?php echo htmlspecialchars($user_data['username']); ?>">

                        <label class="default-address-checkbox">
                            <input type="checkbox" name="is_default"> 
                            <span class="checkmark"></span>
                            Make this my default delivery address
                        </label>
                        
                        <div class="form-actions">
                            <button type="submit" class="save-address-btn">
                                <i class="fas fa-save"></i> Save Address
                            </button>
                            <a href="#" id="cancelAddAddressBtn" class="cancel-add-address">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="useAddressBtn" class="proceed-btn">Use this address</button>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Bite Bliss. All Rights Reserved.</p>
    </footer>
    <script src="script.js?v=2.5"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- State Variables ---
        let currentSubtotal = <?php echo $subtotal; ?>;
        let currentTax = <?php echo $tax_amount; ?>;
        let currentDeliveryCharge = <?php echo $delivery_charge; ?>;
        let currentTotal = <?php echo $total; ?>;
        var addressData = <?php echo json_encode($user_addresses); ?>;
        
        // --- OpenStreetMap Variables ---
        let map;
        let marker;
        let mapInitialized = false;
        let userLocation = null;
        let searchTimeout;
        
        // --- DOM Elements ---
        const modal = document.getElementById('addressModal');
        const changeBtn = document.getElementById('changeAddressBtn');
        const closeBtns = modal.querySelectorAll('.modal-close-btn');
        const useAddressBtn = document.getElementById('useAddressBtn');
        const showAddFormBtn = document.getElementById('showAddAddressFormBtn');
        const cancelAddBtn = document.getElementById('cancelAddAddressBtn');
        const addAddressForm = document.getElementById('addNewAddressForm');
        const addressListDiv = modal.querySelector('.address-list');
        const payButton = document.getElementById('rzp-button1');
        const payButtonText = payButton.querySelector('.btn-text');
        const payButtonLoader = payButton.querySelector('.btn-loader');
        
        // --- Core Function to Update Delivery and Totals ---
        async function updateDeliveryCharge(addressId) {
            if (!addressId || addressId === '0') {
                console.log('No valid address ID provided');
                return;
            }
            
            console.log('Calculating delivery for address ID:', addressId);
            
            // Show loading state
            payButton.disabled = true;
            const deliveryDisplay = document.getElementById('delivery-charge-display');
            deliveryDisplay.textContent = 'Calculating...';
            deliveryDisplay.style.color = '#975ab7';
            
            try {
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ 
                        action: 'calculate_delivery',
                        address_id: parseInt(addressId)
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    console.error('Non-JSON response received:', responseText.substring(0, 500));
                    throw new Error('Server returned invalid response format');
                }
                
                const data = await response.json();
                console.log('Delivery calculation response:', data);
                
                if (data.success) {
                    currentDeliveryCharge = parseFloat(data.charge);
                    
                    // Update display with success styling
                    deliveryDisplay.style.color = '#2e7d32';
                    
                    // Show additional info if available
                    if (data.distance) {
                        console.log(`Delivery charge: ₹${currentDeliveryCharge} for ${data.distance}km`);
                        deliveryDisplay.title = `${data.distance}km distance - ${data.message}`;
                    }
                    
                    // Show message for 3 seconds if it's not the standard charge
                    if (data.message && !data.message.includes('Standard')) {
                        const originalColor = deliveryDisplay.style.color;
                        deliveryDisplay.style.color = '#1976d2';
                        setTimeout(() => {
                            deliveryDisplay.style.color = originalColor;
                        }, 3000);
                    }
                    
                } else {
                    // Handle server-side errors
                    console.warn('Delivery calculation failed:', data.message);
                    currentDeliveryCharge = 20.00;
                    deliveryDisplay.style.color = '#f57c00';
                    deliveryDisplay.title = data.message || 'Using standard delivery charge';
                }
                
            } catch (error) {
                console.error('Delivery calculation error:', error);
                
                // Fallback to standard charge
                currentDeliveryCharge = 20.00;
                deliveryDisplay.style.color = '#666';
                deliveryDisplay.title = 'Standard delivery charge applied due to calculation error';
                
            } finally {
                // Always update the bill and re-enable the button
                updateBillDetails();
                payButton.disabled = false;
            }
        }

        // Enhanced updateBillDetails function
        function updateBillDetails() {
            currentTotal = currentSubtotal + currentDeliveryCharge + currentTax;
            
            const deliveryDisplay = document.getElementById('delivery-charge-display');
            const totalDisplay = document.getElementById('total-amount-display');
            
            deliveryDisplay.textContent = `₹${currentDeliveryCharge.toFixed(2)}`;
            totalDisplay.textContent = `₹${currentTotal.toFixed(2)}`;
            
            console.log(`Bill updated - Subtotal: ₹${currentSubtotal}, Delivery: ₹${currentDeliveryCharge}, Tax: ₹${currentTax}, Total: ₹${currentTotal}`);
        }
        
        // --- Modal Functions ---
        function openModal() {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
        }
        
        // --- Event Listeners for Modal ---
        changeBtn.addEventListener('click', (e) => { e.preventDefault(); openModal(); });
        closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });
        
        // --- Show/Hide Add New Address Form & Init Map ---
        showAddFormBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addressListDiv.style.display = 'none';
            addAddressForm.style.display = 'block';
            showAddFormBtn.style.display = 'none';
            
            if (!mapInitialized) {
                setTimeout(() => {
                    initMap();
                    setupAddressSearch();
                }, 150);
            }
        });
        
        cancelAddBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addressListDiv.style.display = 'block';
            addAddressForm.style.display = 'none';
            showAddFormBtn.style.display = 'inline-block';
        });
        
        // --- OpenStreetMap Integration Functions ---
        function initMap() {
            try {
                const mapCanvas = document.getElementById('map-canvas');
                if (!mapCanvas) {
                    console.error('Map canvas element not found');
                    return;
                }

                const defaultLocation = [13.0827, 80.2707]; // Chennai coordinates [lat, lng]
                const userCoords = userLocation ? [userLocation.lat, userLocation.lng] : defaultLocation;
                
                map = L.map('map-canvas').setView(userCoords, userLocation ? 16 : 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                marker = L.marker(userCoords, {
                    draggable: true,
                    title: "Drag me to your exact location!"
                }).addTo(map);
                
                marker.bindPopup("<b>📍 Your Delivery Location</b><br>Drag me to adjust the position!").openPopup();
                
                marker.on('dragend', function(e) {
                    const position = marker.getLatLng();
                    userLocation = { lat: position.lat, lng: position.lng };
                    reverseGeocode(position.lat, position.lng);
                });
                
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    userLocation = { lat: e.latlng.lat, lng: e.latlng.lng };
                    reverseGeocode(e.latlng.lat, e.latlng.lng);
                    marker.bindPopup("<b>📍 New Location Selected</b><br>Perfect! Address detected.").openPopup();
                });
                
                mapInitialized = true;
                console.log('OpenStreetMap initialized successfully');
                
                if (userLocation) {
                    reverseGeocode(userLocation.lat, userLocation.lng);
                }
                
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        }
        
        function setupAddressSearch() {
            const searchInput = document.getElementById('map-search-input');
            const suggestionsDiv = document.getElementById('address-suggestions');
            
            if (!searchInput || !suggestionsDiv) return;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 3) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    searchAddresses(query);
                }, 300);
            });
            
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
            
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstSuggestion = suggestionsDiv.querySelector('.address-suggestion');
                    if (firstSuggestion) {
                        firstSuggestion.click();
                    }
                }
            });
        }
        
        // *** CORRECTED FUNCTION TO USE PROXY ***
        async function searchAddresses(query) {
            try {
                const response = await fetch(`geocode_proxy.php?action=search&q=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                const suggestionsDiv = document.getElementById('address-suggestions');
                suggestionsDiv.innerHTML = '';

                if (data.status && data.status === 'error') {
                    throw new Error(data.message);
                }
                
                if (data.length > 0) {
                    data.slice(0, 5).forEach((item) => {
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.className = 'address-suggestion';
                        suggestionDiv.innerHTML = `<i class="fas fa-map-marker-alt" style="color: #975ab7; margin-right: 8px;"></i>${item.display_name}`;
                        
                        suggestionDiv.addEventListener('click', function() {
                            const lat = parseFloat(item.lat);
                            const lng = parseFloat(item.lon);
                            
                            if (map && marker) {
                                map.setView([lat, lng], 16);
                                marker.setLatLng([lat, lng]);
                                marker.bindPopup("<b>📍 Selected Location</b><br>Getting address details...").openPopup();
                                userLocation = { lat: lat, lng: lng };
                                reverseGeocode(lat, lng);
                            }
                            
                            document.getElementById('map-search-input').value = item.display_name.split(',')[0];
                            suggestionsDiv.style.display = 'none';
                        });
                        
                        suggestionsDiv.appendChild(suggestionDiv);
                    });
                    
                    suggestionsDiv.style.display = 'block';
                } else {
                    suggestionsDiv.innerHTML = '<div class="address-suggestion" style="color:#666;">No results found.</div>';
                    suggestionsDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Address search error:', error);
                const suggestionsDiv = document.getElementById('address-suggestions');
                suggestionsDiv.innerHTML = `<div class="address-suggestion" style="color:red;">Search failed. Please try again.</div>`;
                suggestionsDiv.style.display = 'block';
            }
        }
            
        // *** CORRECTED FUNCTION TO USE PROXY ***
        async function reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`geocode_proxy.php?action=reverse&lat=${lat}&lon=${lng}`);
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to fetch address details.');
                }

                const data = await response.json();

                if (data.error || !data.address) {
                    throw new Error('No address found for this location');
                }

                fillAddressForm(data.address, data.display_name);
                if (marker) {
                    marker.bindPopup(`<b>📍 Address Detected</b><br>${data.display_name.substring(0, 50)}...`).openPopup();
                }

            } catch (error) {
                console.error('Reverse geocoding error:', error);
                if (marker) {
                    marker.bindPopup(`<b>⚠️ Address Detection Failed</b><br>Please enter address manually.`).openPopup();
                }
                // Optionally, alert the user
                // alert(error.message);
            }
        }

        function fillAddressForm(address, displayName) {
            const form = document.getElementById('addNewAddressForm');
            if (!form) return;
            
            const get = (name) => form.querySelector(`[name="${name}"]`);
            
            const areaStreet = [address.road, address.suburb, address.neighbourhood, address.quarter].filter(Boolean).join(', ');
            if (get('area_street')) get('area_street').value = areaStreet;
            
            const city = address.city || address.town || address.village || address.municipality || '';
            if (get('city')) get('city').value = city;

            const state = address.state || address['ISO3166-2-lvl4'] || '';
            if (get('state')) get('state').value = state;
            
            const pincode = address.postcode || '';
            if (get('pincode')) get('pincode').value = pincode;

            const searchInput = document.getElementById('map-search-input');
            if (searchInput) searchInput.value = displayName.split(',').slice(0, 2).join(', ');

            const formFields = form.querySelector('.address-form-fields');
            if (formFields) {
                formFields.classList.add('highlight');
                setTimeout(() => formFields.classList.remove('highlight'), 2000);
            }
        }
            
        // --- Location Detection Function ---
        async function detectCurrentLocation() {
            const locationBtn = document.getElementById('useCurrentLocationBtn');
            const originalText = locationBtn.innerHTML;
            
            try {
                locationBtn.innerHTML = '<div class="loader-spinner"></div> Getting your location...';
                locationBtn.disabled = true;
                
                if (!navigator.geolocation) throw new Error('Geolocation is not supported by this browser.');
                
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 60000
                    });
                });
                
                const { latitude, longitude } = position.coords;
                userLocation = { lat: latitude, lng: longitude };
                
                locationBtn.innerHTML = '<div class="loader-spinner"></div> Getting address...';
                
                if (map && marker) {
                    map.setView([latitude, longitude], 16);
                    marker.setLatLng([latitude, longitude]);
                }
                
                await reverseGeocode(latitude, longitude);
                
                locationBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location detected!';
                locationBtn.style.background = 'linear-gradient(135deg, #34a853 0%, #137333 100%)';
                
            } catch (error) {
                console.error('Location detection error:', error);
                let msg = 'Location detection failed. Please search manually.';
                if (error.code === 1) msg = 'Location access denied.';
                if (error.code === 3) msg = 'Location request timed out.';
                
                locationBtn.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${msg}`;
                locationBtn.style.background = 'linear-gradient(135deg, #ea4335 0%, #d33b2c 100%)';

            } finally {
                setTimeout(() => {
                    locationBtn.innerHTML = originalText;
                    locationBtn.style.background = '';
                    locationBtn.disabled = false;
                }, 4000);
            }
        }
        
        // --- Handle Address Selection and Saving ---
        addressListDiv.addEventListener('click', function(e) {
            const item = e.target.closest('.address-item');
            if (!item) return;
            addressListDiv.querySelectorAll('.address-item').forEach(el => el.classList.remove('selected'));
            item.classList.add('selected');
            item.querySelector('input[type="radio"]').checked = true;
        });
        
        useAddressBtn.addEventListener('click', function() {
            const selectedRadio = modal.querySelector('input[name="selected_address"]:checked');
            if (!selectedRadio) { 
                alert('Please select an address.'); 
                return; 
            }
            
            const addressId = selectedRadio.value;
            const selectedAddress = addressData.find(addr => addr.id == addressId);
            
            if (selectedAddress) {
                const addressHtml = `<strong>${escapeHtml(selectedAddress.full_name)}</strong><br>${escapeHtml(selectedAddress.flat_house_no)}, ${escapeHtml(selectedAddress.area_street)},<br>${selectedAddress.landmark ? escapeHtml(selectedAddress.landmark) + ',<br>' : ''}${escapeHtml(selectedAddress.city)}, ${escapeHtml(selectedAddress.state)} - ${escapeHtml(selectedAddress.pincode)}`;
                document.getElementById('addressDetails').innerHTML = addressHtml;
                
                Object.keys(selectedAddress).forEach(key => {
                    const input = document.getElementById(`form_${key}`);
                    if (input) input.value = selectedAddress[key] || '';
                });
                
                document.getElementById('form_address_id').value = selectedAddress.id;

                updateDeliveryCharge(selectedAddress.id);
            }
            closeModal();
        });
        
        addAddressForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loader-spinner"></div> Saving...';
            
            const formData = new FormData(this);
            if (userLocation) {
                formData.append('latitude', userLocation.lat);
                formData.append('longitude', userLocation.lng);
            }
            
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Saved!';
                    submitBtn.style.background = 'linear-gradient(135deg, #34a853 0%, #137333 100%)';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to save address');
                }
            })
            .catch(error => {
                console.error('Error saving address:', error);
                alert('Error: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.style.background = '';
            });
        });
        
        // --- Razorpay Payment Integration ---
        payButton.onclick = async function(e) {
            e.preventDefault();
            
            if (!document.getElementById('form_pincode').value || document.getElementById('form_address_id').value === '0') {
                alert('Please select a delivery address.');
                changeBtn.click();
                return;
            }
            
            payButtonText.style.display = 'none';
            payButtonLoader.style.display = 'block';
            payButton.disabled = true;
            
            try {
                const orderResponse = await fetch('create_razorpay_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ delivery_charge: currentDeliveryCharge })
                });
                const orderData = await orderResponse.json();
                
                if (orderData.status !== 'success') {
                    throw new Error(orderData.message);
                }
                
                var options = {
                    "key": "<?php echo RAZORPAY_KEY_ID; ?>", // Correctly inserts the key // IMPORTANT: Replace with your actual Razorpay Key ID
                    "amount": orderData.amount,
                    "currency": "INR",
                    "name": "Bite Bliss",
                    "order_id": orderData.order_id,
                    "handler": function (response) {
                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                        document.getElementById('razorpay_signature').value = response.razorpay_signature;
                        document.getElementById('checkoutForm').submit();
                    },
                    "prefill": {
                        "name": "<?php echo htmlspecialchars($user_data['username']); ?>",
                        "email": "<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>",
                        "contact": "<?php echo htmlspecialchars($user_data['phone_number']); ?>"
                    },
                    "theme": { "color": "#975ab7" },
                    "modal": { 
                        "ondismiss": function() {
                            payButton.disabled = false;
                            payButtonText.style.display = 'block';
                            payButtonLoader.style.display = 'none';
                        }
                    }
                };
                
                var rzp1 = new Razorpay(options);
                rzp1.on('payment.failed', function(response) {
                    alert('Payment failed: ' + response.error.description);
                    payButton.disabled = false;
                    payButtonText.style.display = 'block';
                    payButtonLoader.style.display = 'none';
                });
                rzp1.open();
                
            } catch (error) {
                console.error('Payment error:', error);
                alert('Error initiating payment: ' + error.message);
                payButton.disabled = false;
                payButtonText.style.display = 'block';
                payButtonLoader.style.display = 'none';
            }
        };
        
        // --- Utility Functions ---
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // --- Initial Setup ---
        const defaultAddressId = document.getElementById('form_address_id').value;
        if (defaultAddressId && defaultAddressId !== '0') {
            updateDeliveryCharge(defaultAddressId);
        } else {
            updateBillDetails();
            if(addressData.length === 0){
                 setTimeout(() => changeBtn.click(), 500);
            }
        }
        
        // --- Expose functions to global scope ---
        window.detectCurrentLocation = detectCurrentLocation;
        
    });
    </script>
</body>
</html>