<?php
// admin_upload_portrait.php

// 1. Configuration and Database Connection
require_once '../config.php'; // Adjust path if needed
// You should also include any authentication checks here!

try {
    // 2. Check for File Upload
    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed with error code: " . ($_FILES['image_file']['error'] ?? 'N/A'));
    }

    // 3. Define Upload Directory and Target File
    $upload_dir = '../images/portraits/'; // Adjust path to your images/portraits folder
    $file_info = pathinfo($_FILES['image_file']['name']);
    
    // Sanitize and create a unique filename to prevent overwriting and path traversal
    $original_name = basename($file_info['basename']);
    $extension = strtolower($file_info['extension']);
    
    // Create a safe, unique filename (e.g., hash_timestamp.jpg)
    $safe_filename = md5(time() . $original_name) . '.' . $extension;
    $target_file = $upload_dir . $safe_filename;
    
    // Check file type (optional but recommended for security)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowed_types)) {
        throw new Exception("Sorry, only JPG, JPEG, PNG, GIF, & WEBP files are allowed.");
    }
    
    // 4. Move the Uploaded File
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
        throw new Exception("Error moving uploaded file to the destination directory.");
    }

    // 5. Database Insertion
    
    // Safely retrieve other form data
    $title = trim($_POST['title'] ?? 'Untitled');
    $categories = trim($_POST['categories'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_setcard = (int)($_POST['is_setcard'] ?? 0); // Assuming 0 or 1 from the form

    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO portraits (image_filename, title, categories, is_setcard, sort_order) 
        VALUES (:image_filename, :title, :categories, :is_setcard, :sort_order)
    ");

    $stmt->bindParam(':image_filename', $safe_filename); // Store the safe, unique name
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':categories', $categories);
    $stmt->bindParam(':is_setcard', $is_setcard, PDO::PARAM_INT);
    $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $message = "Portrait added successfully!";
        // Redirect back to the Manage Portraits page
        header("Location: portraits.php?status=success&msg=" . urlencode($message));
        exit;
    } else {
        throw new Exception("Database failed to insert the portrait record.");
    }

} catch (Exception $e) {
    // Log error and redirect with an error message
    error_log("Portrait Upload Error: " . $e->getMessage());
    $error_msg = $e->getMessage();
    
    // Attempt to delete the file if it was moved but the DB insert failed
    if (isset($target_file) && file_exists($target_file)) {
        unlink($target_file);
        error_log("Cleaned up orphaned file: " . $target_file);
    }
    
    header("Location: portraits.php?status=error&msg=" . urlencode($error_msg));
    exit;
}
?>
