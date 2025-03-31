<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header first, before any output
header('Content-Type: application/json');

// Debug log function
function debugLog($message) {
    error_log(print_r($message, true));
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Please log in to like posts');
    }

    // Validate POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate post_id
    if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
        throw new Exception('Post ID is required');
    }

    $postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
    if ($postId === false) {
        throw new Exception('Invalid post ID format');
    }

    $userId = getCurrentUserId();
    $conn = getDbConnection();

    // Check if post exists
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Post not found');
    }

    // Check if already liked
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $userId, $postId);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $isLiked = $result->num_rows > 0;

    if ($isLiked) {
        // Unlike the post
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $userId, $postId);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        $action = 'unliked';
    } else {
        // Like the post
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $userId, $postId);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        $action = 'liked';
    }

    // Get updated like count
    $likeCount = getLikeCount($postId);

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likeCount,
        'message' => $action === 'liked' ? 'Post liked successfully' : 'Post unliked successfully'
    ]);

} catch (Exception $e) {
    error_log('Like Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}