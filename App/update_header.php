<?php
// Prevent any output before headers
ob_start();

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Add this at the top of update_header.php after the requires
error_log("Upload request received");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Ensure we're outputting JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $data = null, $error = null) {
    ob_clean(); // Clear any previous output
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    if (!isLoggedIn()) {
        sendJsonResponse(false, null, 'Not logged in');
    }

    if (!isset($_FILES['header_image']) || $_FILES['header_image']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, null, 'No image uploaded or upload error occurred');
    }

    $file = $_FILES['header_image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        sendJsonResponse(false, null, 'Invalid file type. Only JPG, PNG and GIF are allowed.');
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        sendJsonResponse(false, null, 'File is too large. Maximum size is 5MB.');
    }

    // Create upload directory
    $uploadDir = 'uploads/headers/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            sendJsonResponse(false, null, 'Failed to create upload directory');
        }
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('header_') . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        sendJsonResponse(false, null, 'Failed to move uploaded file');
    }

    // Update database
    $conn = getDbConnection();
    $userId = getCurrentUserId();

    // Get current header image
    $stmt = $conn->prepare("SELECT header_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldImage = $result->fetch_assoc()['header_image'];

    // Update with new image
    $stmt = $conn->prepare("UPDATE users SET header_image = ? WHERE id = ?");
    $stmt->bind_param("si", $filepath, $userId);
    
    if (!$stmt->execute()) {
        unlink($filepath); // Delete new file if update fails
        sendJsonResponse(false, null, 'Failed to update database');
    }

    // Delete old image if it exists
    if ($oldImage && file_exists($oldImage) && $oldImage !== 'assets/images/default-header.jpg') {
        @unlink($oldImage);
    }

    sendJsonResponse(true, ['image_url' => $filepath], null);

} catch (Exception $e) {
    error_log("Header upload error: " . $e->getMessage());
    sendJsonResponse(false, null, 'An error occurred while processing your request');
}

// Clear any remaining output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
} 