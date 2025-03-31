<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    echo json_encode(["success" => false, "message" => "You must be logged in to perform this action."]);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

// Get the user ID to follow/unfollow from the POST request
$followingId = isset($_POST['following_id']) ? intval($_POST['following_id']) : 0;
$followerId = $_SESSION['user_id'];

if ($followingId <= 0 || $followerId === $followingId) {
    echo json_encode(["success" => false, "message" => "Invalid user ID."]);
    exit;
}

// Toggle follow/unfollow
$result = toggleFollow($followerId, $followingId);
if ($result['success']) {
    echo json_encode(["success" => true, "message" => $result['message']]);
} else {
    echo json_encode(["success" => false, "message" => $result['message']]);
}

/**
 * Function to toggle follow/unfollow
 */
function toggleFollow($followerId, $followingId) {
    $conn = getDbConnection();

    // Check if the user is already following
    $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $followerId, $followingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Unfollow the user
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $followerId, $followingId);
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Unfollowed successfully."];
        } else {
            return ["success" => false, "message" => "Failed to unfollow: " . $stmt->error];
        }
    } else {
        // Follow the user
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $followerId, $followingId);
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Followed successfully."];
        } else {
            return ["success" => false, "message" => "Failed to follow: " . $stmt->error];
        }
    }
}
?>