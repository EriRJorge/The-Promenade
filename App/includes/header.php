<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Promenade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="assets/brand/logo.png" alt="The Promenade">
            </a>
            <div class="nav-links">
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="create_post.php">Create Post</a></li>
                        <li><a href="profile.php?username=<?php echo htmlspecialchars(getCurrentUsername()); ?>">My Profile</a></li>
                        <li><a href="logout.php" class="nav-link-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content">
        <?php
        // Display flash messages if any
        $message = getSessionMessage();
        if ($message): 
        ?>
        <div class="alert alert-<?php echo $message['type']; ?>">
            <?php echo $message['text']; ?>
        </div>
        <?php endif; ?> 