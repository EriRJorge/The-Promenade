<?php
// Start session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to set a session message (for notifications/alerts)
function setSessionMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type, // 'success', 'error', 'info', etc.
        'text' => $message
    ];
}

// Function to get and clear session message
function getSessionMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Function to redirect to a page with a message
function redirectWithMessage($url, $type, $message) {
    setSessionMessage($type, $message);
    header("Location: $url");
    exit;
}

// Function to require login for a page
function requireLogin() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        redirectWithMessage("login.php", "error", "You must be logged in to access this page");
    }
}
?>