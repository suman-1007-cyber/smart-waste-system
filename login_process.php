<?php
// Start session management
session_start();

// Include the cloud database connection
require_once __DIR__ . '/config/db.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user inputs
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (!$email || empty($password)) {
        die("Please enter a valid email address and password. <a href='index.php'>Try again</a>");
    }

    // 1. Fetch user record using Prepared Statements (prevents SQL Injection)
    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 2. Verify hashed password against user input
        if (password_verify($password, $user['password'])) {
            // Password is correct! Store session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // 3. Redirect based on user role
            if ($user['role'] === 'admin' || $user['role'] === 'officer') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php"); // Regular user dashboard
            }
            exit();
        } else {
            // Invalid password
            die("Incorrect password. <a href='index.php'>Try again</a>");
        }
    } else {
        // No user found with that email
        die("No account found with that email address. <a href='register.php'>Register here</a>");
    }

    $stmt->close();
    $conn->close();
} else {
    // Direct access redirect
    header("Location: index.php");
    exit();
}