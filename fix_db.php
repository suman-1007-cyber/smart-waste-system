<?php
require_once __DIR__ . '/config/db.php';

// Force the role column to be a wide enough VARCHAR to avoid any data truncation
$sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'";

if ($conn->query($sql) === TRUE) {
    echo "<h2>Success! Database table updated.</h2>";
    echo "<p>The 'role' column is now set to VARCHAR(50).</p>";
    echo "<br><a href='index.php' style='font-size: 1.2rem; background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to go back and register!</a>";
} else {
    echo "<h2>Error updating table:</h2>";
    echo "<p>" . $conn->error . "</p>";
}

$conn->close();
?>