<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Secure password hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user without touching the strict role column
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect back to login page on success
        header("Location: index.php?success=registered");
        exit();
    } else {
        die("Registration Failed: " . $stmt->error);
    }
}
?>