<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For development only, be more specific in production

error_reporting(0);
ini_set('display_errors', 0);

try {
    $action = $_GET['action'] ?? '';
    $url = '';

    if ($action === 'search') {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            throw new Exception("Missing search query");
        }
        $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
            'format' => 'json',
            'addressdetails' => '1',
            'limit' => '5',
            'countrycodes' => 'in', // Restrict to India for better results
            'q' => $query
        ]);
    } elseif ($action === 'reverse') {
        $lat = $_GET['lat'] ?? '';
        $lon = $_GET['lon'] ?? '';
        if (empty($lat) || empty($lon)) {
            throw new Exception("Missing coordinates");
        }
        $url = "https://nominatim.openstreetmap.org/reverse?" . http_build_query([
            'format' => 'json',
            'addressdetails' => '1',
            'lat' => $lat,
            'lon' => $lon
        ]);
    } else {
        throw new Exception("Invalid action specified");
    }

    // Set a valid User-Agent header as required by Nominatim's policy
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: BiteBliss/1.0 (YourAppContact@example.com)\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception("Could not connect to the location service. Please try again.");
    }

    $data = json_decode($response, true);

    // **CRITICAL FIX HERE**
    // Check if the response from Nominatim is an error or is empty
    if (isset($data['error']) || empty($data)) {
        throw new Exception("Could not find a valid address for this location. Please enter it manually.");
    }

    // If everything is okay, output the data from Nominatim directly
    echo json_encode($data);

} catch (Exception $e) {
    // If any error occurs, send a structured error message
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}