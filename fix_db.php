<?php
require_once __DIR__ . '/config/db.php';

// Drop the restrictive column and recreate it fresh as VARCHAR(50)
$queries = [
    "ALTER TABLE users DROP COLUMN role",
    "ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'"
];

$success = true;
$errorMsg = '';

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        // If dropping fails because it doesn't exist, try just adding it
        if (strpos($sql, 'DROP') !== false) {
            continue; 
        }
        $success = false;
        $errorMsg = $conn->error;
        break;
    }
}

if ($success) {
    echo "<h2>Success! Database role column completely rebuilt.</h2>";
    echo "<p>The 'role' column is now clean, flexible, and ready for registrations.</p>";
    echo "<br><a href='index.php' style='font-size: 1.2rem; background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>Click here to go back and register!</a>";
} else {
    echo "<h2>Error rebuilding column:</h2>";
    echo "<p>" . htmlspecialchars($errorMsg) . "</p>";
}

$conn->close();
?>