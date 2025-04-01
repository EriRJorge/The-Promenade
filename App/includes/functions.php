<?php
require_once 'db.php';

/**
 * Format a timestamp into a human-readable string
 */
function formatTimestamp($timestamp) {
    $datetime = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($datetime);
    
    if ($interval->y > 0) {
        return $datetime->format('M j, Y g:i A');
    } elseif ($interval->m > 0) {
        return $datetime->format('M j g:i A');
    } elseif ($interval->d > 0) {
        return $datetime->format('M j g:i A');
    } elseif ($interval->h > 0) {
        return $interval->h . 'h ago';
    } elseif ($interval->i > 0) {
        return $interval->i . 'm ago';
    } else {
        return 'Just now';
    }
}

// Function to create a new post
function createPost($userId, $content, $image) {
    $conn = getDbConnection();
    
    // Validate content length (max 100 words)
    $wordCount = str_word_count($content);
    if ($wordCount > 100) {
        return ["success" => false, "message" => "Post content exceeds 100 words limit"];
    }
    
    // Calculate expiry date (1 week from now)
    $expiryDate = date('Y-m-d H:i:s', strtotime('+1 week'));
    
    // Insert the post
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $content, $image, $expiryDate);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Post created successfully", "post_id" => $stmt->insert_id];
    } else {
        return ["success" => false, "message" => "Failed to create post: " . $stmt->error];
    }
}

/**
 * Function to get a single post by ID
 */
function getPostById($postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT p.*, u.username, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get posts for the feed (not expired)
function getPosts($limit = 10, $offset = 0, $userId = null) {
    $conn = getDbConnection();
    $currentDate = date('Y-m-d H:i:s');
    
    if ($userId) {
        // Get posts from a specific user
        $sql = "SELECT p.*, u.username, u.profile_pic, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ? AND p.expiry_date > ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisii", $userId, $userId, $currentDate, $limit, $offset);
    } else {
        // Get all posts or posts from followed users
        $sql = "SELECT p.*, u.username, u.profile_pic, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.expiry_date > ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isii", $userId, $currentDate, $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

/**
 * Get posts for user's feed
 */
function getFeedPosts($userId, $limit = 10, $offset = 0) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN follows f ON p.user_id = f.followed_id
            WHERE f.follower_id = ? OR p.user_id = ?
            AND p.expiry_date > NOW()
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iiiii", $userId, $userId, $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

/**
 * Get posts by user ID
 */
function getUserPosts($userId, $limit = 10, $offset = 0) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? AND p.expiry_date > NOW()
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iiii", $userId, $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

/**
 * Get all posts (for admin or public feed)
 */
function getAllPosts($limit = 10, $offset = 0, $currentUserId = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.expiry_date > NOW()
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iii", $currentUserId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

/**
 * Add a new comment to a post
 */
function addComment($postId, $userId, $content) {
    try {
        $conn = getDbConnection();
        
        // Check if post exists
        $checkPost = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $checkPost->bind_param("i", $postId);
        $checkPost->execute();
        if ($checkPost->get_result()->num_rows === 0) {
            return ["success" => false, "message" => "Post not found"];
        }
        
        // Check if user exists
        $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        if ($checkUser->get_result()->num_rows === 0) {
            return ["success" => false, "message" => "User not found"];
        }
        
        // Insert comment
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return ["success" => false, "message" => "Database error: " . $conn->error];
        }
        
        $stmt->bind_param("iis", $postId, $userId, $content);
        
        if ($stmt->execute()) {
            return ["success" => true, "comment_id" => $conn->insert_id];
        } else {
            error_log("Execute failed: " . $stmt->error);
            return ["success" => false, "message" => "Failed to add comment: " . $stmt->error];
        }
    } catch (Exception $e) {
        error_log("Error in addComment: " . $e->getMessage());
        return ["success" => false, "message" => "An error occurred while adding the comment"];
    }
}

/**
 * Get all comments for a specific post
 */
function getCommentsByPostId($postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Function to toggle like/unlike
 */
function toggleLike($userId, $postId) {
    $conn = getDbConnection();

    // Check if the user has already liked the post
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $userId, $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Unlike the post
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $userId, $postId);
        if ($stmt->execute()) {
            // Get updated like count
            $likeCount = getLikeCount($postId);
            return [
                "success" => true, 
                "message" => "Post unliked successfully.",
                "action" => "unliked",
                "likes_count" => $likeCount
            ];
        }
    } else {
        // Like the post
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $userId, $postId);
        if ($stmt->execute()) {
            // Get updated like count
            $likeCount = getLikeCount($postId);
            return [
                "success" => true, 
                "message" => "Post liked successfully.",
                "action" => "liked",
                "likes_count" => $likeCount
            ];
        }
    }

    return [
        "success" => false, 
        "message" => "Failed to process like/unlike: " . $stmt->error
    ];
}

/**
 * Function to get like count for a post
 */
function getLikeCount($postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (int)$row['count'];
}

/**
 * Function to toggle follow status between users
 */
function toggleFollow($followerId, $followedId) {
    try {
        $conn = getDbConnection();
        
        // Check if already following
        $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Unfollow
            $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("ii", $followerId, $followedId);
            $stmt->execute();
            return false; // Not following anymore
        } else {
            // Follow
            $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("ii", $followerId, $followedId);
            $stmt->execute();
            return true; // Now following
        }
    } catch (Exception $e) {
        error_log("Database error in toggleFollow: " . $e->getMessage());
        throw new Exception("Failed to process follow request");
    }
}

// Function to get user profile
function getUserProfile($username) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, email, profile_pic, bio, created_at FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Function to update user profile
function updateUserProfile($userId, $bio, $profilePic = null) {
    $conn = getDbConnection();
    
    if ($profilePic) {
        $stmt = $conn->prepare("UPDATE users SET bio = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("ssi", $bio, $profilePic, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->bind_param("si", $bio, $userId);
    }
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Profile updated successfully"];
    } else {
        return ["success" => false, "message" => "Failed to update profile: " . $stmt->error];
    }
}

/**
 * Get number of followers for a user
 */
function getFollowersCount($userId) {
    $conn = getDbConnection();
    // Changed following_id to followed_id since this gets users who are following this profile
    $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count;
}

/**
 * Get number of users this user is following
 */
function getFollowingCount($userId) {
    $conn = getDbConnection();
    // This gets the count of users that this profile is following
    $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count;
}

/**
 * Check if user1 is following user2
 */
function isFollowing($followerId, $followedId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("ii", $followerId, $followedId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Get followers of a user
 */
function getFollowers($userId, $limit = 10) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT u.* 
        FROM follows f 
        JOIN users u ON f.follower_id = u.id 
        WHERE f.followed_id = ? 
        ORDER BY f.created_at DESC 
        LIMIT ?
    ");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get users that a user is following
 */
function getFollowing($userId, $limit = 10) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT u.* 
        FROM follows f 
        JOIN users u ON f.followed_id = u.id 
        WHERE f.follower_id = ? 
        ORDER BY f.created_at DESC 
        LIMIT ?
    ");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Function to upload an image
 * @param array $file The uploaded file array from $_FILES
 * @param string $targetDir The directory to upload to (default: "uploads/images/")
 * @return array Success status and message/file path
 */
function uploadImage($file, $targetDir = "uploads/images/") {
    // Check if directory exists, if not create it
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $targetFile = $targetDir . time() . '_' . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "File is not an image"];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large (max 5MB)"];
    }
    
    // Allow only certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return ["success" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed"];
    }
    
    // Move the uploaded file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ["success" => true, "message" => "Image uploaded successfully", "file_path" => $targetFile];
    } else {
        return ["success" => false, "message" => "Error uploading file"];
    }
}

// Function to check and archive expired posts
function checkAndArchiveExpiredPosts() {
    $conn = getDbConnection();
    $currentDate = date('Y-m-d H:i:s');
    
    // Get all expired posts
    $stmt = $conn->prepare("SELECT id, user_id FROM posts WHERE expiry_date <= ?");
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $expiredPosts = $stmt->get_result();
    
    while ($post = $expiredPosts->fetch_assoc()) {
        // Get like count for this post
        $likeStmt = $conn->prepare("SELECT COUNT(*) as likes_count FROM likes WHERE post_id = ?");
        $likeStmt->bind_param("i", $post['id']);
        $likeStmt->execute();
        $likeResult = $likeStmt->get_result();
        $likeData = $likeResult->fetch_assoc();
        $likesCount = $likeData['likes_count'];
        
        // Store in expired_posts_stats
        $archiveStmt = $conn->prepare("INSERT INTO expired_posts_stats (user_id, likes_count, original_post_id) VALUES (?, ?, ?)");
        $archiveStmt->bind_param("iii", $post['user_id'], $likesCount, $post['id']);
        $archiveStmt->execute();
        
        // Delete the post (comments and likes will cascade delete)
        $deleteStmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $deleteStmt->bind_param("i", $post['id']);
        $deleteStmt->execute();
    }
}

// Function to get total likes from expired posts
function getTotalExpiredPostLikes($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT SUM(likes_count) as total_likes FROM expired_posts_stats WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total_likes'] ? $row['total_likes'] : 0;
}

// Run the expired posts check on every page load
checkAndArchiveExpiredPosts();

/**
 * Function to check if a post is liked by a user
 */
function isPostLikedByUser($postId, $userId) {
    if (!$userId) return false;
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Check if a user has liked a post
 */
function hasUserLikedPost($userId, $postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $userId, $postId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Get the total number of likes for a post
 */
function getPostLikesCount($postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

/**
 * Get total likes received on all posts by a user
 */
function getTotalUserLikes($userId) {
    try {
        $conn = getDbConnection();
        
        // Simplified query to count all likes on user's posts
        $sql = "SELECT COUNT(*) as total 
                FROM likes l 
                INNER JOIN posts p ON l.post_id = p.id 
                WHERE p.user_id = ?";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['total'];
    } catch (Exception $e) {
        error_log("Error getting total likes: " . $e->getMessage());
        return 0;
    }
}

function validatePost($content) {
    if (strlen($content) > 100) {
        return false;
    }
    return true;
}

function getProfileHeaderImage($userId) {
    try {
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        $stmt = $conn->prepare("
            SELECT header_image 
            FROM users 
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['header_image'] ?? 'assets/images/default-header.jpg';
    } catch (Exception $e) {
        error_log("Error getting header image: " . $e->getMessage());
        return 'assets/images/default-header.jpg';
    }
}

function updateProfileHeaderImage($userId, $headerImage) {
    try {
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        $stmt = $conn->prepare("
            UPDATE users 
            SET header_image = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $headerImage, $userId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating header image: " . $e->getMessage());
        return false;
    }
}