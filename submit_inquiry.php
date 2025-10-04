<?php
// Set header to indicate a JSON response
header('Content-Type: application/json');

// Check if config file exists and load the database connection function
if (!file_exists('config.php')) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error: Database setup file missing.']);
    exit;
}
require_once 'config.php';

// Define HTML escaping helper function (for safety, though response is JSON)
if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Check for required POST data
if (empty($_POST['full_name']) || empty($_POST['email']) || empty($_POST['inquiry_type']) || empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Name, Email, Type, Message).']);
    exit;
}

// Sanitize and assign variables
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone_number = trim($_POST['phone_number'] ?? '');
$inquiry_type = trim($_POST['inquiry_type']);
$message = trim($_POST['message']);

try {
    // Attempt to get DB connection (assuming getDBConnection() is in config.php)
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO inquiries (full_name, email, phone_number, inquiry_type, message, is_read, submission_date) 
        VALUES (:full_name, :email, :phone_number, :inquiry_type, :message, 0, NOW())
    ");
    
    $stmt->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':inquiry_type' => $inquiry_type,
        ':message' => $message
    ]);

    // SUCCESS RESPONSE for SweetAlert
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your inquiry! I will get back to you shortly.'
    ]);
    
} catch (PDOException $e) {
    // Log the actual error for your records
    error_log("Inquiry Submission Error: " . $e->getMessage());
    
    // ERROR RESPONSE for SweetAlert
    echo json_encode([
        'success' => false, 
        'message' => 'A database error occurred. Please try again later or contact me directly via email.'
    ]);
}
?>