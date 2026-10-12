<?php
session_start();
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check if user exists in database
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'officer') {
                header("Location: admin_panel.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            echo "<p style='color:red;'>Invalid password. <a href='index.php'>Try again</a></p>";
        }
    } else {
        // Fallback redirection for testing if user isn't registered yet
        header("Location: dashboard.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>