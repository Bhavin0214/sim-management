<?php
// config.php - Database configuration only
session_start();

$host = '127.0.0.1';
$dbname = 'sim_managment';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Only declare functions if they don't exist
if (!function_exists('checkAuth')) {
    function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
}

if (!function_exists('getUserId')) {
    function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>