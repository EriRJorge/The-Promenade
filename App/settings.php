<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getCurrentUserId();
$profile = getUserProfile(getCurrentUsername());

// Initialize variables
$success = false;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Update bio
    if (isset($_POST['bio'])) {
        $result = updateUserProfile($userId, $bio);
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = $result['message'];
        }
    }
    
    // Update password if provided
    if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        } else {
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = "Failed to update password";
                }
            } else {
                $errors[] = "Current password is incorrect";
            }
        }
    }
}

$pageTitle = "Profile Settings";
include 'includes/header.php';
?>

<div class="settings-container">
    <h1>Profile Settings</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>Settings updated successfully!</p>
        </div>
    <?php endif; ?>
    
    <div class="settings-grid">
        <!-- Profile Picture Section -->
        <div class="settings-section">
            <h2>Profile Picture</h2>
            <div class="profile-picture-preview">
                <img src="<?php echo htmlspecialchars($profile['profile_pic'] ?? 'assets/images/default-profile.png'); ?>" 
                     alt="Profile Picture">
                <form id="profilePicForm" enctype="multipart/form-data">
                    <label for="profilePic" class="btn">
                        <i class="fas fa-camera"></i> Change Picture
                    </label>
                    <input type="file" id="profilePic" name="profile_pic" accept="image/*" style="display: none;">
                </form>
            </div>
        </div>
        
        <!-- Header Image Section -->
        <div class="settings-section">
            <h2>Header Image</h2>
            <div class="header-image-preview">
                <img src="<?php echo htmlspecialchars(getProfileHeaderImage($userId)); ?>" 
                     alt="Header Image">
                <form id="headerImageForm" enctype="multipart/form-data">
                    <label for="headerImage" class="btn">
                        <i class="fas fa-camera"></i> Change Header
                    </label>
                    <input type="file" id="headerImage" name="header_image" accept="image/*" style="display: none;">
                </form>
            </div>
        </div>
        
        <!-- Bio Section -->
        <div class="settings-section">
            <h2>Bio</h2>
            <form method="POST" class="bio-form">
                <div class="form-group">
                    <label for="bio">Your Bio</label>
                    <textarea name="bio" id="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                    <div class="character-count">0/500 characters</div>
                </div>
                <div class="bio-preview">
                    <h3>Preview</h3>
                    <div class="preview-content">
                        <p id="bioPreview"><?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?></p>
                    </div>
                </div>
                <button type="submit" name="bio" class="btn">Save Bio</button>
            </form>
        </div>
        
        <!-- Password Section -->
        <div class="settings-section">
            <h2>Change Password</h2>
            <form method="POST" class="password-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle profile picture upload
    const profilePicInput = document.getElementById('profilePic');
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
                    document.querySelector('.profile-picture-preview img').src = data.image_url;
                    // Update profile picture in header if it exists
                    const headerProfilePic = document.querySelector('.nav-profile-pic');
                    if (headerProfilePic) {
                        headerProfilePic.src = data.image_url;
                    }
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
    
    // Handle header image upload
    const headerImageInput = document.getElementById('headerImage');
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
                    document.querySelector('.header-image-preview img').src = data.image_url;
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

    // Bio preview functionality
    const bioTextarea = document.getElementById('bio');
    const bioPreview = document.getElementById('bioPreview');
    const characterCount = document.querySelector('.character-count');
    
    if (bioTextarea && bioPreview) {
        function updateBioPreview() {
            const text = bioTextarea.value;
            bioPreview.innerHTML = text.replace(/\n/g, '<br>');
            
            // Update character count
            const count = text.length;
            characterCount.textContent = `${count}/500 characters`;
            
            // Update character count styling
            characterCount.classList.remove('limit-close', 'limit-reached');
            if (count >= 500) {
                characterCount.classList.add('limit-reached');
            } else if (count >= 450) {
                characterCount.classList.add('limit-close');
            }
        }
        
        bioTextarea.addEventListener('input', updateBioPreview);
        updateBioPreview(); // Initial update
    }
});
</script>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.settings-section {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-section h2 {
    margin-bottom: 20px;
    color: #333;
}

.profile-picture-preview,
.header-image-preview {
    text-align: center;
    margin-bottom: 20px;
}

.profile-picture-preview img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 4px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-image-preview img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #666;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #1a73e8;
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s;
}

.btn:hover {
    background: #1557b0;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.bio-preview {
    margin: 20px 0;
    padding: 15px;
    background: var(--bg-light);
    border-radius: 8px;
    border: 1px solid var(--border-light);
}

.bio-preview h3 {
    color: var(--text-medium);
    font-size: 14px;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.preview-content {
    color: var(--text-dark);
    font-size: 16px;
    line-height: 1.6;
}

.preview-content p {
    margin: 0;
}

.character-count {
    text-align: right;
    font-size: 14px;
    color: var(--text-light);
    margin-top: 4px;
}

.character-count.limit-close {
    color: var(--warning);
}

.character-count.limit-reached {
    color: var(--danger);
}
</style>

<?php include 'includes/footer.php'; ?> 