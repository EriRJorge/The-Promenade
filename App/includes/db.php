<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Define a function to get database connection
function getDbConnection() {
    global $conn;
    return $conn;
}

// Function to close the database connection
function closeDbConnection() {
    global $conn;
    mysqli_close($conn);
}
?>