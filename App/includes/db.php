<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Define a function to get database connection
function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli('localhost', 'root', '', 'promenade'); // Adjust these values to match your setup
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    return $conn;
}

// Function to close the database connection
function closeDbConnection() {
    global $conn;
    mysqli_close($conn);
}
?>