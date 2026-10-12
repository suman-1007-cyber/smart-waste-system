<?php
// Include the cloud database connection
require_once __DIR__ . '/config/db.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user inputs
    $name     = trim($_POST['name'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($name) || !$email || empty($password)) {
        die("Please fill in all required fields with valid information. <a href='register.php'>Try again</a>");
    }

    // 1. Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        die("An account with this email already exists. <a href='index.php'>Log in here</a>");
    }
    $checkStmt->close();

    // 2. Hash the password securely using standard BCRYPT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 3. Securely insert the new user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        $stmt->close();
        // Registration successful - redirect to login
        header("Location: index.php?registered=success");
        exit();
    } else {
        echo "Error creating account: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Direct access redirect
    header("Location: register.php");
    exit();
}