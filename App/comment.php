<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Please log in to comment');
    }

    // Validate POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    if (!isset($_POST['post_id']) || !isset($_POST['comment'])) {
        throw new Exception('Missing required fields');
    }

    $postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);
    $userId = getCurrentUserId();

    if ($postId === false) {
        throw new Exception('Invalid post ID');
    }

    if (empty($comment)) {
        throw new Exception('Comment cannot be empty');
    }

    // Add comment
    $result = addComment($postId, $userId, $comment);
    
    if ($result['success']) {
        // Get the new comment data
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.profile_pic 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $result['comment_id']);
        $stmt->execute();
        $commentData = $stmt->get_result()->fetch_assoc();

        if (!$commentData) {
            error_log("No comment data found for ID: " . $result['comment_id']);
            throw new Exception('Failed to retrieve comment data');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'username' => $commentData['username'],
            'profile_pic' => $commentData['profile_pic'],
            'content' => $commentData['content'],
            'created_at' => $commentData['created_at']
        ]);
    } else {
        error_log("Failed to add comment: " . $result['message']);
        throw new Exception($result['message']);
    }
} catch (Exception $e) {
    error_log("Comment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>