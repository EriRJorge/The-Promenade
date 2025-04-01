// Function to handle like/unlike actions
document.addEventListener("DOMContentLoaded", function() {
    // Handle like buttons
    const likeButtons = document.querySelectorAll(".like-button");
    if (likeButtons) {
        likeButtons.forEach(button => {
            button.addEventListener("click", async function(e) {
                e.preventDefault();
                
                // Prevent double-clicking while processing
                if (this.classList.contains('processing')) {
                    return;
                }
                
                const postId = this.dataset.postId;
                const likeCounter = document.querySelector(`#likes-count-${postId}`);
                const likeIcon = this.querySelector('i');
                const likeText = this.querySelector('.like-text');
                
                // Add processing state
                this.classList.add('processing');
                
                try {
                    const formData = new FormData();
                    formData.append('post_id', postId);
                    
                    const response = await fetch('like.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('Server response:', data); // Debug log
                    
                    if (data.success) {
                        // Update button state
                        this.classList.toggle('liked');
                        
                        // Update icon and text
                        if (data.action === 'liked') {
                            likeIcon.className = 'fas fa-heart';
                            likeText.textContent = 'Liked';
                        } else {
                            likeIcon.className = 'far fa-heart';
                            likeText.textContent = 'Like';
                        }
                        
                        // Update counter with animation
                        if (likeCounter) {
                            likeCounter.textContent = data.likes_count;
                            likeCounter.classList.add('updating');
                            setTimeout(() => {
                                likeCounter.classList.remove('updating');
                            }, 300);
                        }
                        
                        // Show success message
                        showFeedback(data.message, 'success');
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                } catch (error) {
                    console.error('Like Error:', error);
                    showFeedback(error.message, 'error');
                } finally {
                    this.classList.remove('processing');
                }
            });
        });
    }
    
    // Handle follow buttons
    const followButtons = document.querySelectorAll('.follow-btn');
    if (followButtons) {
        followButtons.forEach(button => {
            button.addEventListener('click', async function() {
                const userId = this.dataset.userId;
                console.log('Following user ID:', userId); // Debug log
                
                const originalText = this.textContent;
                
                try {
                    // Disable button and show loading state
                    this.disabled = true;
                    this.textContent = 'Processing...';
                    
                    // Make the fetch request
                    const response = await fetch('follow.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${userId}`
                    });

                    // Log the raw response for debugging
                    const responseText = await response.text();
                    console.log('Raw server response:', responseText);

                    // Try to parse the response
                    let data;
                    try {
                        data = JSON.parse(responseText);
                        console.log('Parsed response:', data);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        throw new Error('Invalid server response');
                    }

                    if (!data.success) {
                        throw new Error(data.error || 'Failed to process request');
                    }

                    // Update button state on success
                    const isFollowing = data.data?.following ?? false;
                    this.classList.toggle('following', isFollowing);
                    this.textContent = isFollowing ? 'Following' : 'Follow';
                    
                } catch (error) {
                    console.error('Error:', error);
                    this.textContent = originalText;
                    alert(error.message || 'An error occurred. Please try again.');
                } finally {
                    this.disabled = false;
                }
            });

            // Hover states
            button.addEventListener('mouseenter', function() {
                if (this.classList.contains('following')) {
                    this.textContent = 'Unfollow';
                }
            });

            button.addEventListener('mouseleave', function() {
                if (this.classList.contains('following')) {
                    this.textContent = 'Following';
                }
            });
        });
    }
    
    // Handle comment forms
    const commentForms = document.querySelectorAll(".comment-form");
    if (commentForms) {
        commentForms.forEach(form => {
            form.addEventListener("submit", async function(e) {
                e.preventDefault();
                
                const postId = this.dataset.postId;
                const commentInput = this.querySelector("textarea[name='comment']");
                const commentContent = commentInput.value.trim();
                const commentsList = document.getElementById('comments-list');
                
                if (!commentContent) {
                    showFeedback("Comment cannot be empty", 'error');
                    return;
                }
                
                // Disable form while processing
                this.classList.add('processing');
                const submitButton = this.querySelector('button');
                submitButton.disabled = true;
                
                try {
                    const formData = new FormData();
                    formData.append('post_id', postId);
                    formData.append('comment', commentContent);
                    
                    const response = await fetch("comment.php", {
                        method: "POST",
                        body: formData
                    });
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        console.error("JSON parse error:", e);
                        throw new Error("Server returned invalid response");
                    }
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to add comment');
                    }
                    
                    if (data.success) {
                        // Create new comment element
                        const newComment = document.createElement('div');
                        newComment.className = 'comment new';
                        newComment.innerHTML = `
                            <div class="comment-header">
                                <img src="${data.profile_pic}" alt="Profile picture" class="profile-pic">
                                <div class="comment-meta">
                                    <a href="profile.php?username=${data.username}" class="username">
                                        ${data.username}
                                    </a>
                                    <span class="timestamp">Just now</span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <p>${data.content}</p>
                            </div>
                        `;
                        
                        // Remove "no comments" message if it exists
                        const noComments = commentsList.querySelector('.no-comments');
                        if (noComments) {
                            noComments.remove();
                        }
                        
                        // Add new comment at the top
                        commentsList.insertBefore(newComment, commentsList.firstChild);
                        
                        // Clear input
                        commentInput.value = '';
                        
                        // Show success message
                        showFeedback("Comment added successfully", 'success');
                        
                        // Remove animation class after animation completes
                        setTimeout(() => {
                            newComment.classList.remove('new');
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Failed to add comment');
                    }
                } catch (error) {
                    console.error("Error:", error);
                    showFeedback(error.message, 'error');
                } finally {
                    // Re-enable form
                    this.classList.remove('processing');
                    submitButton.disabled = false;
                }
            });
        });
    }
    
    // Word counter for post creation
    const contentTextarea = document.getElementById('content');
    if (contentTextarea) {
        const wordCountDisplay = document.getElementById('word-count');
        
        contentTextarea.addEventListener('input', function() {
            const wordCount = this.value.trim() ? this.value.trim().split(/\s+/).length : 0;
            wordCountDisplay.textContent = `${wordCount}/100 words`;
            
            if (wordCount > 100) {
                wordCountDisplay.classList.add('error');
            } else {
                wordCountDisplay.classList.remove('error');
            }
        });
    }

    // Character count functionality
    const textarea = document.getElementById('postContent');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const remaining = this.value.length;
            charCount.textContent = remaining;
            
            const charCountDiv = charCount.parentElement;
            if (remaining >= 90) {
                charCountDiv.classList.add('limit-close');
            } else {
                charCountDiv.classList.remove('limit-close');
            }
            
            if (remaining === 100) {
                charCountDiv.classList.add('limit-reached');
            } else {
                charCountDiv.classList.remove('limit-reached');
            }
            
            // Prevent further input if limit is reached
            if (this.value.length > 100) {
                this.value = this.value.substring(0, 100);
            }
        });
    }
});

// Feedback message function
function showFeedback(message, type) {
    const feedback = document.createElement('div');
    feedback.className = `feedback-message ${type}`;
    feedback.textContent = message;
    
    // Remove any existing feedback
    const existingFeedback = document.querySelector('.feedback-message');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    document.body.appendChild(feedback);
    
    // Trigger animation
    requestAnimationFrame(() => {
        feedback.classList.add('show');
    });
    
    // Remove after 3 seconds
    setTimeout(() => {
        feedback.classList.remove('show');
        setTimeout(() => feedback.remove(), 300);
    }, 3000);
}

// Add this helper function for notifications
function showNotification(message, type = 'info') {
    // You can implement this however you want - alert, toast, etc.
    if (type === 'error') {
        alert(message); // For now, using simple alert
    } else {
        // Optional: implement a nicer notification system
        console.log(message);
    }
}

// Header image upload functionality
function openHeaderUpload() {
    document.getElementById('headerUploadModal').classList.add('active');
}

function closeHeaderUpload() {
    document.getElementById('headerUploadModal').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const headerUploadForm = document.getElementById('headerUploadForm');
    const headerImageInput = document.getElementById('headerImage');
    const imagePreview = document.getElementById('imagePreview');

    if (headerImageInput) {
        headerImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG and GIF are allowed.');
                    this.value = '';
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (headerUploadForm) {
        headerUploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Uploading...';

            try {
                const formData = new FormData();
                const file = headerImageInput.files[0];
                
                if (!file) {
                    throw new Error('Please select an image first');
                }

                formData.append('header_image', file);

                const response = await fetch('update_header.php', {
                    method: 'POST',
                    body: formData
                });

                // Log the raw response for debugging
                const responseText = await response.text();
                console.log('Raw server response:', responseText);

                // Try to parse the response as JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Server returned invalid response');
                }

                if (!data.success) {
                    throw new Error(data.error || 'Failed to update header image');
                }

                // Update header image
                const headerImg = document.querySelector('.profile-header-image');
                if (headerImg) {
                    headerImg.src = data.data.image_url;
                } else {
                    const newHeaderImg = document.createElement('img');
                    newHeaderImg.src = data.data.image_url;
                    newHeaderImg.className = 'profile-header-image';
                    document.querySelector('.profile-header').prepend(newHeaderImg);
                }
                
                closeHeaderUpload();
                alert('Header image updated successfully');

            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while updating the header image');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Save Changes';
            }
        });
    }
});