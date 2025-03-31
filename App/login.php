<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Check if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required";
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['loggedin'] = true;
            
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Include header
$pageTitle = "Login";
include 'includes/header.php';
?>

<div class="auth-container">
    <h1>Login to The Promenade</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="login.php" method="POST" class="auth-form">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="button primary-button">Login</button>
    </form>
    
    <div class="auth-links">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>