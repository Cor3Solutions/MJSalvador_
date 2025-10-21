<?php
// submit_inquiry.php
header('Content-Type: application/json');

// Start output buffering to prevent any accidental output
ob_start();

try {
    // Include config file
    require_once 'config.php';

    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please refresh the page and try again.');
    }

    // Validate required fields
    $required_fields = ['full_name', 'email', 'inquiry_type', 'message'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('Please fill in all required fields.');
        }
    }

    // Sanitize and validate inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $inquiry_type = trim($_POST['inquiry_type']);
    $message = trim($_POST['message']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }

    // Validate name length
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        throw new Exception('Name must be between 2 and 100 characters.');
    }

    // Validate message length
    if (strlen($message) < 10 || strlen($message) > 1000) {
        throw new Exception('Message must be between 10 and 1000 characters.');
    }

    // Get database connection
    $conn = getDBConnection();

    // Prepare SQL statement
    $sql = "INSERT INTO inquiries (full_name, email, phone_number, inquiry_type, message, submission_date) 
            VALUES (:full_name, :email, :phone_number, :inquiry_type, :message, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
    $stmt->bindParam(':inquiry_type', $inquiry_type, PDO::PARAM_STR);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);
    
    // Execute query
    if ($stmt->execute()) {
        // Clear output buffer
        ob_end_clean();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your inquiry! We will get back to you within 24-48 hours.'
        ]);
    } else {
        throw new Exception('Failed to submit inquiry. Please try again.');
    }

} catch (PDOException $e) {
    // Log database errors
    error_log("Inquiry Submission Database Error: " . $e->getMessage());
    
    // Clear output buffer
    ob_end_clean();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);
    
} catch (Exception $e) {
    // Clear output buffer
    ob_end_clean();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;