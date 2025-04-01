<?php
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$users = [];
$posts = [];

if (!empty($query)) {
    $users = searchUsers($query);
    $posts = searchPosts($query);
}

/**
 * Function to search for users
 */
function searchUsers($query) {
    $conn = getDbConnection();
    $searchQuery = "%$query%";
    
    $stmt = $conn->prepare("SELECT id, username, profile_pic, bio FROM users WHERE username LIKE ? LIMIT 20");
    $stmt->bind_param("s", $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Function to search for posts
 */
function searchPosts($query) {
    $conn = getDbConnection();
    $searchQuery = "%$query%";
    $currentDate = date('Y-m-d H:i:s');
    $userId = isLoggedIn() ? getCurrentUserId() : null;
    
    $sql = "SELECT p.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.content LIKE ? AND p.expiry_date > ?
            ORDER BY p.created_at DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $searchQuery, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Include header
$pageTitle = "Search";
include 'includes/header.php';
?>

<h1>Search</h1>

<form action="search.php" method="GET" class="search-form">
    <div class="form-group">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search for users or posts...">
        <button type="submit" class="button">Search</button>
    </div>
</form>

<?php if (!empty($query)): ?>
    <h2>Results for "<?php echo htmlspecialchars($query); ?>"</h2>
    
    <?php if (empty($users) && empty($posts)): ?>
        <p>No results found. Try different keywords.</p>
    <?php else: ?>
        <!-- Users results -->
        <?php if (!empty($users)): ?>
            <div class="search-section">
                <h3>Users</h3>
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>'s profile picture" style="width:60px;height:60px;border-radius:50%;">
                            <div class="user-info">
                                <h4><a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></a></h4>
                                <p><?php echo htmlspecialchars(substr($user['bio'] ?? '', 0, 100)); ?><?php echo strlen($user['bio'] ?? '') > 100 ? '...' : ''; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Posts results -->
        <?php if (!empty($posts)): ?>
            <div class="search-section">
                <h3>Posts</h3>
                <div class="posts-container">
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile picture" style="width:50px;height:50px;border-radius:50%;">
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
                                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" style="max-width:100%;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-actions">
                                <div class="stats">
                                    <span><?php echo $post['likes_count']; ?> likes</span>
                                    <span><?php echo $post['comments_count']; ?> comments</span>
                                </div>
                                
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="button">View Post</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 