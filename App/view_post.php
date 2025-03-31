<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Assuming this contains helper functions like `getPostById` and `getCommentsByPostId`

// Check if the user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get the post ID from the query string
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($postId <= 0) {
    header("Location: index.php");
    exit;
}

$post = getPostById($postId);
if (!$post) {
    header("Location: index.php");
    exit;
}

$comments = getCommentsByPostId($postId);

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

/**
 * Function to get comments for a post
 */
function getCommentsByPostId($postId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    return $comments;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($post['content']); ?></h1>
    <p>Posted by: <?php echo htmlspecialchars($post['username']); ?></p>
    <p>Posted on: <?php echo htmlspecialchars($post['created_at']); ?></p>
    <?php if (!empty($post['image'])): ?>
        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" width="300"><br>
    <?php endif; ?>

    <h2>Comments</h2>
    <?php if (!empty($comments)): ?>
        <ul>
            <?php foreach ($comments as $comment): ?>
                <li>
                    <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                    <?php echo htmlspecialchars($comment['content']); ?>
                    <br><small><?php echo htmlspecialchars($comment['created_at']); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No comments yet. Be the first to comment!</p>
    <?php endif; ?>
</body>
</html>