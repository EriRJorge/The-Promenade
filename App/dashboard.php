<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getCurrentUserId();
$username = getCurrentUsername();

// Get feed posts
$limit = 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$posts = getFeedPosts($userId, $limit, $offset);

// Include header
$pageTitle = "Dashboard";
include 'includes/header.php';
?>

<div class="dashboard-header">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <a href="create_post.php" class="button primary-button">Create New Post</a>
</div>

<div class="feed-container">
    <h2>Your Feed</h2>
    
    <?php if (!empty($posts)): ?>
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile picture" class="profile-pic">
                        <div class="post-meta">
                            <a href="profile.php?username=<?php echo htmlspecialchars($post['username']); ?>" class="username">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                            <span class="timestamp"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-actions">
                        <div class="stats">
                            <span id="likes-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span> likes | 
                            <span><?php echo $post['comments_count']; ?></span> comments
                        </div>
                        
                        <div class="actions">
                            <button class="like-button" data-post-id="<?php echo $post['id']; ?>">
                                <?php echo $post['user_liked'] ? 'Unlike' : 'Like'; ?>
                            </button>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="button">View Post</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($offset > 0): ?>
                <a href="dashboard.php?offset=<?php echo max(0, $offset - $limit); ?>" class="button">Previous</a>
            <?php endif; ?>
            
            <?php if (count($posts) == $limit): ?>
                <a href="dashboard.php?offset=<?php echo $offset + $limit; ?>" class="button">Next</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="no-posts">No posts to display. Follow some users or create your first post!</p>
        <div class="cta-buttons">
            <a href="search.php" class="button">Find Users to Follow</a>
            <a href="create_post.php" class="button primary-button">Create Your First Post</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>