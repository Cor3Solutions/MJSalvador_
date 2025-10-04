<?php
// submit_testimonial.php

// 1. Set the Header FIRST to ensure a JSON response
header('Content-Type: application/json');

// 2. Configuration and Database Connection
require_once 'config.php'; 

// Helper function definition is included here for completeness, 
// as its presence (or removal and resulting empty line) often causes issues.
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Initialize response array
$response = ['success' => false, 'message' => ''];


// 3. Handle POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Safely retrieve and trim post data
    $quote_text = trim($_POST['quote_text'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $client_title = trim($_POST['client_title'] ?? '');

    // Basic validation
    if (empty($quote_text) || empty($client_name)) {
        $response['message'] = "Please fill out both the Testimonial and Your Name fields.";
    } else {
        try {
            // Establish connection
            $conn = getDBConnection();
            
            // Prepare the SQL statement for insertion
            $stmt = $conn->prepare("
                INSERT INTO testimonials (quote_text, client_name, client_title, is_approved) 
                VALUES (:quote_text, :client_name, :client_title, 0)
            ");

            $stmt->bindParam(':quote_text', $quote_text);
            $stmt->bindParam(':client_name', $client_name);
            $stmt->bindParam(':client_title', $client_title);
            
            // Execute the statement
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Thank you! Your testimonial has been submitted for approval. ✨";
            } else {
                $response['message'] = "A database error occurred during insertion.";
            }

        } catch (PDOException $e) {
            // Log the detailed error for backend debugging
            error_log("Testimonial Submission Error: " . $e->getMessage()); 
            $response['message'] = "An unexpected server error occurred.";
        }
    }
} else {
    // Handle non-POST requests
    $response['message'] = "Invalid request method.";
}

// 4. Output the JSON response
echo json_encode($response);
exit; // End execution here
?>