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
    const followForms = document.querySelectorAll(".follow-form");
    if (followForms) {
        followForms.forEach(form => {
            form.addEventListener("submit", async function(e) {
                e.preventDefault();
                
                const followingId = this.querySelector("input[name='following_id']").value;
                const button = this.querySelector("button");
                
                try {
                    const response = await fetch("follow.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: `following_id=${followingId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Toggle button text and class
                        if (button.textContent.trim() === "Follow") {
                            button.textContent = "Unfollow";
                            button.classList.remove("primary-button");
                            button.classList.add("secondary-button");
                        } else {
                            button.textContent = "Follow";
                            button.classList.remove("secondary-button");
                            button.classList.add("primary-button");
                        }
                        
                        // Reload the page to update stats
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
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