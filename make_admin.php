<?php
require_once __DIR__ . '/config/db.php';

// REPLACE THIS WITH YOUR EXACT REGISTERED EMAIL ADDRESS
$email = 'your-email@example.com'; 

$stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo "<h2>Success! Account ($email) is now an Admin!</h2>";
    echo "<br><a href='index.php'>Click here to Log In as Admin</a>";
} else {
    echo "Error updating role: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>