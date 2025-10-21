<?php
header('Content-Type: application/json');

require_once 'config.php'; 

$response = ['success' => false, 'message' => ''];

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = "Invalid request method.";
    echo json_encode($response);
    exit;
} 
// Rate limiting check
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit('testimonial_' . $ip_address, 3, 600)) {
    $response['message'] = 'Too many submissions. Please try again in 10 minutes.';
    echo json_encode($response);
    exit;
}

// Retrieve and trim post data
$quote_text = trim($_POST['quote_text'] ?? '');
$client_name = trim($_POST['client_name'] ?? '');
$client_title = trim($_POST['client_title'] ?? '');

// Basic validation
if (empty($quote_text) || empty($client_name)) {
    $response['message'] = "Please fill out both the Testimonial and Your Name fields.";
    echo json_encode($response);
    exit;
}

// Length validation
if (strlen($client_name) < 2 || strlen($client_name) > 100) {
    $response['message'] = "Name must be between 2 and 100 characters.";
    echo json_encode($response);
    exit;
}

if (strlen($quote_text) < 10 || strlen($quote_text) > 500) {
    $response['message'] = "Testimonial must be between 10 and 500 characters.";
    echo json_encode($response);
    exit;
}

if (!empty($client_title) && strlen($client_title) > 150) {
    $response['message'] = "Title/Company must be less than 150 characters.";
    echo json_encode($response);
    exit;
}

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO testimonials (quote_text, client_name, client_title, is_approved) 
        VALUES (:quote_text, :client_name, :client_title, 0 )
    ");

    $stmt->bindParam(':quote_text', $quote_text);
    $stmt->bindParam(':client_name', $client_name);
    $stmt->bindParam(':client_title', $client_title);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Thank you! Your testimonial has been submitted for approval. ✨";
    } else {
        $response['message'] = "A database error occurred during insertion.";
    }

} catch (PDOException $e) {
    error_log("Testimonial Submission Error: " . $e->getMessage()); 
    $response['message'] = "An unexpected server error occurred. Please try again later.";
}

echo json_encode($response);
exit;
?>