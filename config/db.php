<?php
// Determine environment parameters (Cloud env vars vs. Local XAMPP defaults)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'smart_waste_system';$port = getenv('DB_PORT') ?: 3306;

// 1. Initial connection to MySQL server (without specifying database name first)
$conn = new mysqli($host,$username, $password, "", $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS `$database`";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($database);
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Auto-create users table if it doesn't exist
$table_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'officer') DEFAULT 'citizen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_users);

// 4. Auto-create complaints table if it doesn't exist
$table_complaints = "CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($table_complaints);
?>