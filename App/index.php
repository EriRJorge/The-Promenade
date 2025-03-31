<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get latest posts
$limit = 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$userId = isLoggedIn() ? getCurrentUserId() : null;
$posts = getPosts($limit, $offset, null);

// Include header
$pageTitle = "Welcome to The Promenade";
include 'includes/header.php';
?>

<div class="welcome-section">
    <h1>Welcome to The Promenade</h1>
    <p>A social media platform where posts disappear after a week. Share your moments and connect with others!</p>
    
    <?php if (!isLoggedIn()): ?>
        <div class="cta-buttons">
            <a href="register.php" class="button primary-button">Sign Up</a>
            <a href="login.php" class="button secondary-button">Login</a>
        </div>
    <?php endif; ?>
</div>

<div class="recent-posts">
    <h2>Recent Posts</h2>
    
    <?php if (!empty($posts)): ?>
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile picture" class="profile-pic" style="width:50px;height:50px;border-radius:50%;">
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
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image" style="max-width:100%;">
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-actions">
                        <div class="stats">
                            <span id="likes-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span> likes | 
                            <span><?php echo $post['comments_count']; ?></span> comments
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="actions">
                                <button class="like-button" data-post-id="<?php echo $post['id']; ?>">
                                    <?php echo $post['user_liked'] ? 'Unlike' : 'Like'; ?>
                                </button>
                                <a href="post.php?id=<?php echo $post['id']; ?>">View Details</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="pagination">
            <?php if ($offset > 0): ?>
                <a href="index.php?offset=<?php echo max(0, $offset - $limit); ?>" class="button">Previous</a>
            <?php endif; ?>
            
            <?php if (count($posts) == $limit): ?>
                <a href="index.php?offset=<?php echo $offset + $limit; ?>" class="button">Next</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>No posts to display yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>