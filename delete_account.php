<?php
require_once "config.php";
require_login();

// Only student accounts are allowed to access account deletion.
if ($_SESSION['user']['role'] !== 'student') {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $userId = (int)$_SESSION['user']['id'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role='student' AND active=1 LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $validPassword = $user && (password_verify($password, $user['password']) || $user['password'] === md5($password));

    if (!$validPassword) {
        $error = "Incorrect password. Your account was not deleted.";
    } else {
        // The database uses ON DELETE CASCADE for the student's requests/history/feedback.
        $delete = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
        $delete->bind_param("i", $userId);

        if ($delete->execute() && $delete->affected_rows > 0) {
            session_unset();
            session_destroy();
            header("Location: login.php?deleted=1");
            exit;
        }
        $error = "Unable to delete your account. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Delete Account - CampusCare</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="container narrow">
    <section class="panel">
        <h1>Delete Account</h1>
        <p class="muted">This permanently deletes your student account and your submitted maintenance requests. This action cannot be undone.</p>

        <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>

        <form method="post">
            <label>Enter your password to confirm</label>
            <input type="password" name="password" required placeholder="Enter your password">
            <div class="actions">
                <button class="btn danger-btn" type="submit" onclick="return confirm('Are you sure you want to permanently delete your account?');">Delete My Account</button>
                <a class="btn" href="dashboard.php">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
