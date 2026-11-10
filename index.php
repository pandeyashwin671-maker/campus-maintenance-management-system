<?php
require_once "config.php";
if (isset($_SESSION['user'])) header("Location: dashboard.php");
else header("Location: login.php");
exit;
?>
