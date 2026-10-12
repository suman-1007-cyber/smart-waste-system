<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Secure password hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user into the database, letting MySQL apply the default 'user' role automatically
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redirect back to login/index page on success
        header("Location: index.php?success=registered");
        exit();
    } else {
        // Fallback: if 'role' column rejects the value, try inserting without the role column entirely
        $stmt->close();
        
        $stmt_fallback = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt_fallback->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt_fallback->execute()) {
            $stmt_fallback->close();
            $conn->close();
            header("Location: index.php?success=registered");
            exit();
        } else {
            die("Registration Failed: " . $stmt_fallback->error);
        }
    }
}
?>