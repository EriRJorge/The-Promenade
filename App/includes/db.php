<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define a function to get database connection
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'promenade');
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    
    return $conn;
}

// Function to close the database connection
function closeDbConnection() {
    global $conn;
    mysqli_close($conn);
} 