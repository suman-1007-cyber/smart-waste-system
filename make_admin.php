<?php
require_once __DIR__ . '/config/db.php';

// Put your registered email address here:
$email = 'sumankrishna710@gmail.com'; 

$stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo "<h2>Success! Account ($email) is now an Admin!</h2>";
    echo "<a href='index.php'>Go to Login Page</a>";
} else {
    echo "Error updating role: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>