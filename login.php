<?php
require_once "config.php";
if (isset($_SESSION['user'])) { header("Location: dashboard.php"); exit; }

$error = "";
$success = isset($_GET['deleted']) ? "Your student account has been deleted successfully." : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id,name,email,password,role,active FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && (int)$user['active'] === 1 && (password_verify($password, $user['password']) || $user['password'] === md5($password))) {
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];
        // Every role lands on the same dashboard.php, but dashboard.php
        // shows completely different, scoped content per role.
        header("Location: dashboard.php");
        exit;
    } elseif ($user && (int)$user['active'] === 0) {
        $error = "This account has been deactivated. Contact an administrator.";
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CampusCare - Login</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="brand">Campus<span>Care</span></div>
    <p class="muted">Campus Maintenance Management System</p>
    <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required placeholder="Enter email">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter password">
        <button class="btn primary full auth-submit">Login</button>
    </form>
    <div class="auth-switch">
        Don't have an account? <a href="signup.php"><b>Sign Up</b></a>
    </div>
</div>
</body>
</html>
