<?php
// Check if running on Render (Cloud Environment Variables) or Locally
$host     = getenv('DB_HOST')     ?: 'localhost';
$username = getenv('DB_USER')     ?: 'root';$password = getenv('DB_PASS')     ?: '';
$database = getenv('DB_NAME')     ?: 'smart_waste_system';$port     = getenv('DB_PORT')     ?: 3306;

// 1. Create database connection (connect to server first)
$conn = new mysqli($host,$username, $password, "", (int)$port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create database if it doesn't exist (useful for local setup)
$sql = "CREATE DATABASE IF NOT EXISTS `$database`";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($database);
} else {
    die("Error creating database: " . $conn->error);
}

// Set character set
$conn->set_charset("utf8mb4");

// 3. Create users table (using VARCHAR for role to prevent truncation errors)
$table_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'citizen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_users);

// 4. Create complaints table (including waste_type for dashboard compatibility)
$table_complaints = "CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    location VARCHAR(255) NOT NULL,
    waste_type VARCHAR(100) NOT NULL DEFAULT 'Organic',
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($table_complaints);
?>