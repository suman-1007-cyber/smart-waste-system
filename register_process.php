<?php
// Include the cloud database connection
require_once __DIR__ . '/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        die("All fields are required. <a href='index.php'>Go back</a>");
    }

    // Securely hash the password using BCRYPT
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user WITHOUT touching the strict role column 
    // (MySQL will automatically apply the default table value)
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        die("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect to index/login upon successful registration
        header("Location: index.php?success=registered");
        exit();
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        die("Registration Error: " . htmlspecialchars($error));
    }
} else {
    // Direct access redirect
    header("Location: index.php");
    exit();
}
?>