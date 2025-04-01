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

$errors = [];
$success = '';

// Process post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $userId = getCurrentUserId();
    
    // Validate content
    if (empty($content)) {
        $errors[] = "Post content cannot be empty.";
    } elseif (str_word_count($content) > 100) {
        $errors[] = "Post content must not exceed 100 words.";
    }
    
    // Handle image upload if present
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }
    
    // If no errors, create the post
    if (empty($errors)) {
        $result = createPost($userId, $content, $imagePath);
        if ($result['success']) {
            $success = "Post created successfully!";
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Include header
$pageTitle = "Create Post";
include 'includes/header.php';
?>

<h1>Create a New Post</h1>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="create_post.php" method="POST" enctype="multipart/form-data" class="post-form">
    <div class="form-group">
        <textarea name="content" id="postContent" maxlength="100" required 
            placeholder="What's on your mind? (100 characters max)"></textarea>
        <div class="character-count">
            <span id="charCount">0</span>/100 characters
        </div>
    </div>
    
    <div class="form-group">
        <label for="image">Image (optional):</label>
        <input type="file" id="image" name="image" accept="image/*">
    </div>
    
    <p class="expiry-notice">Note: Posts will automatically expire after 1 week!</p>
    
    <button type="submit" class="button primary-button">Create Post</button>
</form>

<script>
    // Word counter script
    const contentTextarea = document.getElementById('postContent');
    const charCountDisplay = document.getElementById('charCount');
    
    contentTextarea.addEventListener('input', function() {
        const charCount = this.value.length;
        charCountDisplay.textContent = `${charCount}/100 characters`;
        
        if (charCount > 100) {
            charCountDisplay.classList.add('error');
        } else {
            charCountDisplay.classList.remove('error');
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 