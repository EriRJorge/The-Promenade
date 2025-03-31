<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'functions.php';

// Function to register a new user
function registerUser($username, $email, $password) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return ["success" => false, "message" => "Please fill all required fields"];
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ["success" => false, "message" => "Username or email already exists"];
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Registration successful, please login", "user_id" => $stmt->insert_id];
    } else {
        return ["success" => false, "message" => "Registration failed: " . $stmt->error];
    }
}

// Function to login a user
function loginUser($username, $password) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ["success" => false, "message" => "Please enter both username and password"];
    }
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user["password"])) {
            // Password is correct, start the session
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            
            return ["success" => true, "message" => "Login successful", "user_id" => $user["id"]];
        } else {
            return ["success" => false, "message" => "Invalid password"];
        }
    } else {
        return ["success" => false, "message" => "User not found"];
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Function to logout a user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return ["success" => true, "message" => "Logged out successfully"];
}

// Function to get current user ID
function getCurrentUserId() {
    return isset($_SESSION["id"]) ? $_SESSION["id"] : null;
}

// Function to get current username
function getCurrentUsername() {
    return isset($_SESSION["username"]) ? $_SESSION["username"] : null;
}
?>