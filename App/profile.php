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
$isFollowing = false;
if (isLoggedIn() && getCurrentUserId() !== $userId) {
    $isFollowing = isFollowing(getCurrentUserId(), $userId);
}

// Get user's posts
$limit = 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$posts = getPosts($limit, $offset, $userId);

// Get followers and following counts
$followersCount = getFollowersCount($userId);
$followingCount = getFollowingCount($userId);
$totalLikes = getTotalUserLikes($userId);

$headerImage = getProfileHeaderImage($userId);

// Include header
include 'includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($headerImage); ?>" alt="Profile header" class="profile-header-image">
        <div class="header-overlay"></div>
        <div class="profile-picture">
            <img src="<?php echo !empty($profile['profile_pic']) ? htmlspecialchars($profile['profile_pic']) : 'assets/images/default-profile.png'; ?>" 
                 alt="Profile Picture">
        </div>
    </div>

    <div class="profile-info">
        <div class="profile-details">
            <h1 class="profile-name">@<?php echo htmlspecialchars($profile['username']); ?></h1>
            
            <?php if (!empty($profile['bio'])): ?>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p>
            <?php endif; ?>

            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($followersCount); ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($followingCount); ?></span>
                    <span class="stat-label">Following</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($totalLikes); ?></span>
                    <span class="stat-label">Likes</span>
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($isCurrentUser): ?>
                <a href="settings.php" class="btn settings-btn">
                    <i class="fas fa-cog"></i> Profile Settings
                </a>
            <?php elseif (isLoggedIn() && getCurrentUserId() !== $profile['id']): ?>
                <button class="btn follow-btn <?php echo $isFollowing ? 'following' : ''; ?>" 
                        data-user-id="<?php echo $profile['id']; ?>">
                    <?php echo $isFollowing ? 'Following' : 'Follow'; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="posts-section">
    <h2 class="posts-header">Posts</h2>
    
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headerImageInput = document.getElementById('headerImage');
    const profilePicInput = document.getElementById('profilePic');
    
    if (headerImageInput) {
        headerImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('header_image', file);
            
            fetch('includes/update_header.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.profile-header-image').src = data.image_url;
                } else {
                    alert('Error updating header image. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating header image. Please try again.');
            });
        });
    }

    if (profilePicInput) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('profile_pic', file);
            
            fetch('includes/update_profile_pic.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.profile-picture img').src = data.image_url;
                } else {
                    alert('Error updating profile picture. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating profile picture. Please try again.');
            });
        });
    }
});
</script>

<style>
.profile-container {
    background: var(--bg-white);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    margin-bottom: 32px;
}

.profile-header {
    position: relative;
    height: 300px;
    overflow: hidden;
    border-radius: 20px 20px 0 0;
}

.profile-header-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.header-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        var(--overlay-light),
        var(--overlay-dark)
    );
    z-index: 1;
}

.profile-picture {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    z-index: 3;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 5px solid var(--bg-white);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    background: var(--bg-white);
    overflow: hidden;
}

.profile-picture img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.profile-info {
    padding: 32px;
    text-align: center;
    margin-top: 75px;
}

.profile-details {
    margin-left: 0;
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.profile-bio {
    color: var(--text-medium);
    font-size: 16px;
    max-width: 600px;
    margin: 0 auto 24px;
    line-height: 1.6;
}

.profile-stats {
    display: flex;
    justify-content: center;
    gap: 32px;
    margin: 32px 0;
    padding: 24px;
    background: var(--bg-light);
    border-radius: 16px;
}

.stat-item {
    text-align: center;
    padding: 16px 32px;
    background: var(--bg-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-actions {
    margin-top: 24px;
    display: flex;
    gap: 12px;
    justify-content: center;
}

.settings-btn,
.follow-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    gap: 8px;
    line-height: 1.4;
    text-decoration: none;
    min-width: 150px;
}

.settings-btn {
    background: var(--primary);
    color: var(--bg-white);
}

.settings-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 161, 103, 0.2);
}

.follow-btn {
    background: var(--primary);
    color: var(--bg-white);
}

.follow-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 161, 103, 0.2);
}

.follow-btn.following {
    background: var(--bg-white);
    color: var(--primary);
    border: 2px solid var(--primary);
}

.follow-btn.following:hover {
    background: var(--danger-light);
    color: var(--danger);
    border-color: var(--danger);
}

.settings-btn i {
    font-size: 16px;
}

/* Posts Section */
.posts-section {
    margin-top: 32px;
}

.posts-header {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 24px;
}

.posts-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.post {
    background: var(--bg-white);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: transform 0.2s ease;
}

.post:hover {
    transform: translateY(-2px);
}

.post-content {
    padding: 24px;
}

.post-content p {
    color: var(--text-dark);
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 16px;
}

.post-content img {
    width: 100%;
    max-height: 500px;
    object-fit: cover;
    border-radius: 12px;
}

.post-meta {
    padding: 16px 24px;
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--text-light);
    font-size: 14px;
}

.post-actions {
    padding: 16px 24px;
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination {
    margin-top: 32px;
    display: flex;
    justify-content: center;
    gap: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header {
        height: 250px;
    }

    .profile-picture {
        width: 130px;
        height: 130px;
    }

    .profile-info {
        padding: 75px 20px 24px;
    }

    .profile-stats {
        flex-direction: column;
        gap: 16px;
    }

    .stat-item {
        padding: 16px;
    }
}

@media (max-width: 576px) {
    .profile-container {
        border-radius: 0;
    }

    .profile-header {
        height: 200px;
        border-radius: 0;
    }

    .profile-picture {
        width: 120px;
        height: 120px;
    }

    .profile-info {
        padding-top: 70px;
    }

    .profile-stats {
        padding: 15px;
        gap: 15px;
    }

    .stat-number {
        font-size: 18px;
    }

    .stat-label {
        font-size: 12px;
    }
}
</style>
