<?php
require_once 'session.php';
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_FILES['header_image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$userId = getCurrentUserId();
$file = $_FILES['header_image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'header_' . $userId . '_' . time() . '.' . $extension;
$uploadPath = '../uploads/headers/' . $filename;

// Ensure uploads directory exists
if (!file_exists('../uploads/headers')) {
    mkdir('../uploads/headers', 0777, true);
}

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $imageUrl = 'uploads/headers/' . $filename;
    
    // Update database
    if (updateProfileHeaderImage($userId, $imageUrl)) {
        echo json_encode([
            'success' => true,
            'image_url' => $imageUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update database'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to upload file'
    ]);
} 