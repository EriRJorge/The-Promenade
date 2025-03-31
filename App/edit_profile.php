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
$errors = [];
$successMessage = '';
$user = getUserProfileById($userId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio']);
    $profilePic = isset($_FILES['profile_pic']) ? $_FILES['profile_pic'] : null;

    // Validate bio length
    if (strlen($bio) > 255) {
        $errors[] = "Bio must not exceed 255 characters.";
    }

    // Handle profile picture upload
    $profilePicPath = $user['profile_pic']; // Keep existing profile picture by default
    if ($profilePic && $profilePic['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($profilePic, 'uploads/profile_pics/');
        if ($uploadResult['success']) {
            $profilePicPath = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }

    // If no errors, update the profile
    if (empty($errors)) {
        $updateResult = updateUserProfile($userId, $bio, $profilePicPath);
        if ($updateResult['success']) {
            $successMessage = "Profile updated successfully.";
            $user = getUserProfileById($userId); // Refresh user data
        } else {
            $errors[] = $updateResult['message'];
        }
    }
}

/**
 * Function to get user profile by ID
 */
function getUserProfileById($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT username, email, bio, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Include header
$pageTitle = "Edit Profile";
include 'includes/header.php';
?>

<div class="auth-container">
    <h1>Edit Your Profile</h1>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="auth-form">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            <small>Username cannot be changed</small>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            <small>Email cannot be changed</small>
        </div>

        <div class="form-group">
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio" rows="4" maxlength="255"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            <small>Maximum 255 characters</small>
        </div>

        <div class="form-group">
            <label for="profile_pic">Profile Picture:</label>
            <?php if (!empty($user['profile_pic'])): ?>
                <div class="current-profile-pic">
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Current profile picture" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                </div>
            <?php endif; ?>
            <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
            <small>Allowed formats: JPG, JPEG, PNG, GIF (Max size: 5MB)</small>
        </div>

        <button type="submit" class="button primary-button">Update Profile</button>
        <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>" class="button secondary-button">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>