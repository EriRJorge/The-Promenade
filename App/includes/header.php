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
    <title><?php echo isset($pageTitle) ? $pageTitle : 'The Promenade'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1><a href="index.php">The Promenade</a></h1>
                <nav>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="create_post.php">Create Post</a></li>
                            <li><a href="profile.php?username=<?php echo htmlspecialchars(getCurrentUsername()); ?>">My Profile</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php
        // Display flash messages if any
        $message = getSessionMessage();
        if ($message): 
        ?>
        <div class="alert alert-<?php echo $message['type']; ?>">
            <?php echo $message['text']; ?>
        </div>
        <?php endif; ?> 