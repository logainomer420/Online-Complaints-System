<?php
// config.php
$host = 'localhost';
$user = 'root'; // Change to your DB user
$pass = '';     // Change to your DB password
$db   = 'online_complaint_system';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create Users Table (Pre-populates 1 student and 1 admin)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL
)");

// Create Complaints Table with Tracking Status
$conn->query("CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default accounts if table is empty (Password is 'password123')
$check = $conn->query("SELECT * FROM users LIMIT 1");
if ($check->num_rows == 0) {
    $student_pass = password_hash('password123', PASSWORD_DEFAULT);
    $admin_pass = password_hash('password123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, role) VALUES 
        ('student', '$student_pass', 'student'),
        ('admin', '$admin_pass', 'admin')");
}
?>