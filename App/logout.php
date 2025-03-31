<?php
require_once 'includes/session.php';

// Destroy the session
session_start();
session_unset();
session_destroy();

// Redirect to the login page with a success message
header("Location: login.php?message=logged_out");
exit;
?>