<?php
// Enable error logging to a file instead of output
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Start with a basic test
try {
    // Test 1: Check if we can output JSON
    $test_response = [
        "success" => true,
        "message" => "Basic test working",
        "timestamp" => date('Y-m-d H:i:s')
    ];
    
    // Test 2: Check input
    $input = file_get_contents('php://input');
    $test_response['raw_input'] = $input;
    
    if ($input) {
        $decoded = json_decode($input, true);
        $test_response['decoded_input'] = $decoded;
    }
    
    // Test 3: Check POST data
    $test_response['post_data'] = $_POST;
    
    // Test 4: Check if db_connect.php exists and can be included
    if (file_exists('db_connect.php')) {
        $test_response['db_file_exists'] = true;
        try {
            include_once('db_connect.php');
            $test_response['db_included'] = true;
            
            // Test database connection
            if (isset($pdo)) {
                $test_response['pdo_available'] = true;
            } elseif (isset($conn)) {
                $test_response['conn_available'] = true;
            } else {
                $test_response['no_db_connection'] = true;
            }
            
        } catch (Exception $e) {
            $test_response['db_error'] = $e->getMessage();
        }
    } else {
        $test_response['db_file_missing'] = true;
    }
    
    echo json_encode($test_response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "line" => $e->getLine(),
        "file" => $e->getFile()
    ]);
} catch (Error $e) {
    echo json_encode([
        "success" => false,
        "error" => "PHP Error: " . $e->getMessage(),
        "line" => $e->getLine(),
        "file" => $e->getFile()
    ]);
}
exit;