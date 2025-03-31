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

// Function to get feed posts (from followed users and own posts)
function getFeedPosts($userId, $limit = 10, $offset = 0) {
    $conn = getDbConnection();
    $currentDate = date('Y-m-d H:i:s');
    
    $sql = "SELECT p.*, u.username, u.profile_pic, 
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN follows f ON p.user_id = f.following_id AND f.follower_id = ?
            WHERE (f.id IS NOT NULL OR p.user_id = ?) AND p.expiry_date > ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisii", $userId, $userId, $userId, $currentDate, $limit, $offset);
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
    if ($followedId <= 0) {
        return false;
    }
    
    $conn = getDbConnection();
    
    // Check if already following
    $stmt = $conn->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    
    if (!$stmt) {
        // Debug: Print any SQL errors
        error_log("MySQL Error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $followerId, $followedId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unfollow
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        return false; // Indicates unfollowed
    } else {
        // Follow
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        return true; // Indicates followed
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

// Function to check if a user is following another user
function isFollowing($followerId, $followingId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $followerId, $followingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Function to get followers count
function getFollowersCount($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Function to get following count
function getFollowingCount($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
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