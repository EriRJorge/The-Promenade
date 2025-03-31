<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

var_dump(function_exists('formatTimestamp'));

// Get post ID from URL
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get post data
$post = getPostById($postId);
if (!$post) {
    header("Location: index.php");
    exit;
}

// Get comments for this post
$comments = getCommentsByPostId($postId);
error_log("Comments for post $postId: " . print_r($comments, true));

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="post">
        <div class="post-header">
            <img src="<?php echo $post['profile_pic']; ?>" alt="Profile picture" class="profile-pic">
            <div class="post-meta">
                <a href="profile.php?username=<?php echo $post['username']; ?>" class="username">
                    <?php echo $post['username']; ?>
                </a>
                <span class="timestamp"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <?php if ($post['image']): ?>
                <img src="<?php echo $post['image']; ?>" alt="Post image" class="post-image">
            <?php endif; ?>
        </div>
        
        <div class="post-actions">
            <button class="like-button <?php echo hasUserLikedPost($_SESSION['user_id'], $postId) ? 'liked' : ''; ?>" 
                    data-post-id="<?php echo $postId; ?>">
                <i class="<?php echo hasUserLikedPost($_SESSION['user_id'], $postId) ? 'fas' : 'far'; ?> fa-heart"></i>
                <span class="like-text"><?php echo hasUserLikedPost($_SESSION['user_id'], $postId) ? 'Liked' : 'Like'; ?></span>
                <span class="like-count" id="likes-count-<?php echo $postId; ?>">
                    <?php echo getPostLikesCount($postId); ?>
                </span>
            </button>
        </div>
        
        <!-- Comments Section -->
        <div class="comments-section">
            <form class="comment-form" data-post-id="<?php echo $postId; ?>">
                <div class="form-group">
                    <textarea name="comment" placeholder="Write a comment..." required></textarea>
                </div>
                <button type="submit" class="primary-button">Post Comment</button>
            </form>
            
            <div id="comments-list" class="comments-list">
                <?php if (empty($comments)): ?>
                    <div class="no-comments">No comments yet. Be the first to comment!</div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <img src="<?php echo $comment['profile_pic']; ?>" alt="Profile picture" class="profile-pic">
                                <div class="comment-meta">
                                    <a href="profile.php?username=<?php echo $comment['username']; ?>" class="username">
                                        <?php echo $comment['username']; ?>
                                    </a>
                                    <span class="timestamp"><?php echo formatTimestamp($comment['created_at']); ?></span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>