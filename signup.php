<?php
require_once "config.php";
if (isset($_SESSION['user'])) { header("Location: dashboard.php"); exit; }

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $error = "An account with this email already exists.";
        } else {
            // Public sign up can ONLY ever create a 'student' account.
            // Staff/Admin accounts can only be created from admin.php.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(name,email,password,role,active) VALUES(?,?,?,'student',1)");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);
            if ($stmt->execute()) {
                $success = "Account created successfully. You can now log in.";
            } else {
                $error = "Could not create the account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CampusCare - Sign Up</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="brand">Campus<span>Care</span></div>
    <p class="muted">Create your campus maintenance account</p>

    <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>

    <?php if(!$success): ?>
    <form method="post">
        <label>Full Name</label>
        <input type="text" name="name" required maxlength="100" placeholder="Enter your name">

        <label>Email</label>
        <input type="email" name="email" required maxlength="120" placeholder="Enter your email">

        <label>Password</label>
        <input type="password" name="password" required minlength="6" placeholder="Create a password">

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter your password">

        <button class="btn primary full auth-submit">Create Student Account</button>
    </form>
    <?php endif; ?>

    <div class="auth-switch">
        <?php if($success): ?>
            <a class="btn full" href="login.php">Go to Login</a>
        <?php else: ?>
            Already have an account? <a href="login.php"><b>Login</b></a>
        <?php endif; ?>
    </div>

    <p class="role-note">New sign-ups are created as <b>Student</b> accounts only. Staff and Admin access can only be provided by an administrator.</p>
</div>
</body>
</html>
