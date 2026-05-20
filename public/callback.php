<?php
require_once __DIR__ . '/../controllers/UssdController.php';

// Set the content type to plain text as required by Beem
header('Content-Type: text/plain');

// Read the raw input from Beem
$input = file_get_contents('php://input');

if (empty($input)) {
    echo "END An error occurred. Please try again.";
    exit;
}

// Decode the JSON data
$beem_data = json_decode($input, true);

if (!$beem_data) {
    error_log("Failed to decode Beem JSON: " . $input);
    echo "END An error occurred. Please try again.";
    exit;
}

// Log the incoming request (useful for debugging)
error_log("Beem Request: " . print_r($beem_data, true));

// Create controller and handle the request
$controller = new UssdController();
$response = $controller->handleRequest($beem_data);

// Log the outgoing response
error_log("Response: " . $response);

// Send the response back to Beem
echo $response;
?>