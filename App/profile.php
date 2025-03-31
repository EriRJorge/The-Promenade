<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get the username from the query string
$username = isset($_GET['username']) ? $_GET['username'] : '';

if (empty($username)) {
    // If no username specified and user is logged in, redirect to their own profile
    if (isLoggedIn()) {
        header("Location: profile.php?username=" . getCurrentUsername());
        exit;
    } else {
        // If not logged in, redirect to login page
        header("Location: login.php");
        exit;
    }
}

// Get user profile data
$profile = getUserProfile($username);
if (!$profile) {
    redirectWithMessage("index.php", "error", "User not found.");
    exit;
}

$pageTitle = $profile['username'] . "'s Profile";
$userId = $profile['id'];
$currentUserId = isLoggedIn() ? getCurrentUserId() : null;
$isCurrentUser = $currentUserId && $userId == $currentUserId;
$isFollowing = $currentUserId && !$isCurrentUser ? isFollowing($currentUserId, $userId) : false;

// Get user's posts
$limit = 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$posts = getPosts($limit, $offset, $userId);

// Get followers and following counts
$followersCount = getFollowersCount($userId);
$followingCount = getFollowingCount($userId);

// Get total likes received for expired posts
$totalLikes = getTotalExpiredPostLikes($userId);

// Include header
include 'includes/header.php';
?>

<div class="profile-header">
    <div class="profile-picture">
        <img src="<?php echo htmlspecialchars($profile['profile_pic']); ?>" alt="<?php echo htmlspecialchars($profile['username']); ?>'s profile picture" style="width:150px;height:150px;object-fit:cover;border-radius:50%;">
    </div>
    
    <div class="profile-info">
        <h1><?php echo htmlspecialchars($profile['username']); ?></h1>
        
        <div class="profile-stats">
            <div class="stat">
                <span class="count"><?php echo count($posts); ?></span>
                <span class="label">Posts</span>
            </div>
            <div class="stat">
                <span class="count"><?php echo $followersCount; ?></span>
                <span class="label">Followers</span>
            </div>
            <div class="stat">
                <span class="count"><?php echo $followingCount; ?></span>
                <span class="label">Following</span>
            </div>
            <div class="stat">
                <span class="count"><?php echo $totalLikes; ?></span>
                <span class="label">Total Likes</span>
            </div>
        </div>
        
        <div class="profile-bio">
            <p><?php echo htmlspecialchars($profile['bio'] ?? 'No bio yet.'); ?></p>
        </div>
        
        <div class="profile-actions">
            <?php if ($isCurrentUser): ?>
                <a href="edit_profile.php" class="button">Edit Profile</a>
            <?php elseif (isLoggedIn()): ?>
                <form action="follow.php" method="POST" style="display:inline;">
                    <input type="hidden" name="following_id" value="<?php echo $userId; ?>">
                    <button type="submit" class="button <?php echo $isFollowing ? 'secondary-button' : 'primary-button'; ?>">
                        <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="profile-posts">
    <h2>Posts</h2>
    
    <?php if (!empty($posts)): ?>
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-content">
                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" style="max-width:100%;">
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-meta">
                        <span class="timestamp"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
                        <div class="stats">
                            <span><?php echo $post['likes_count']; ?> likes</span>
                            <span><?php echo $post['comments_count']; ?> comments</span>
                        </div>
                    </div>
                    
                    <div class="post-actions">
                        <?php if (isLoggedIn()): ?>
                            <button class="like-button" data-post-id="<?php echo $post['id']; ?>">
                                <?php echo $post['user_liked'] ? 'Unlike' : 'Like'; ?>
                            </button>
                        <?php endif; ?>
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="button">View Post</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($offset > 0): ?>
                <a href="profile.php?username=<?php echo urlencode($username); ?>&offset=<?php echo max(0, $offset - $limit); ?>" class="button">Previous</a>
            <?php endif; ?>
            
            <?php if (count($posts) == $limit): ?>
                <a href="profile.php?username=<?php echo urlencode($username); ?>&offset=<?php echo $offset + $limit; ?>" class="button">Next</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>No posts yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
