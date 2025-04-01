<?php
// Start with a clean output buffer
ob_clean();
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Basic auth check
    if (!isLoggedIn()) {
        throw new Exception('Not logged in');
    }

    // Get user IDs
    $followerId = getCurrentUserId();
    $followedId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    // Validate IDs
    if ($followedId <= 0) {
        throw new Exception('Invalid user ID');
    }

    if ($followerId === $followedId) {
        throw new Exception('Cannot follow yourself');
    }

    // Get database connection
    $conn = getDbConnection();

    // Check if already following
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $followerId, $followedId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isFollowing = $result->num_rows > 0;
    $stmt->close();

    if ($isFollowing) {
        // Unfollow
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        $success = true;
        $following = false;
    } else {
        // Follow
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        $success = true;
        $following = true;
    }

    // Clear any previous output
    ob_clean();
    
    // Send response
    echo json_encode([
        'success' => true,
        'data' => [
            'following' => $following
        ]
    ]);

} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Log the error
    error_log("Follow error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// End output buffer and send response
ob_end_flush();
exit;