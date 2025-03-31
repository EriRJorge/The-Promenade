<?php
require_once 'config/database.php';

echo "<h1>Database Connection Test</h1>";

if ($conn) {
    echo "<p style='color:green'>✅ Connected to database successfully!</p>";
    
    // Show all tables
    $result = $conn->query("SHOW TABLES");
    
    echo "<h2>Database Tables:</h2>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
    
    // Count users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $users = $result->fetch_assoc();
    echo "<p>Users in database: " . $users['count'] . "</p>";
} else {
    echo "<p style='color:red'>❌ Database connection failed!</p>";
}
?> 