<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "campus_maintenance";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

// One-time flash messages so a redirect-after-POST never resubmits a form
// and success/error banners still show correctly after the redirect.
function flash_set($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return "";
}
?>
