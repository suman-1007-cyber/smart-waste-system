<?php
require_once __DIR__ . '/config/db.php';

// Automatically widen the role column to prevent data truncation
$sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'user'";

if ($conn->query($sql) === TRUE) {
    echo "<h2>Success! The database table has been updated.</h2>";
    echo "<p>The 'role' column can now safely store user types.</p>";
    echo "<br><a href='index.php'>Click here to go back and register!</a>";
} else {
    echo "Error updating table: " . $conn->error;
}

$conn->close();
?>