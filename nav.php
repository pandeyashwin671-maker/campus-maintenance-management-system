<nav class="topbar">
<div class="nav-inner">
<a class="brand small" href="dashboard.php">Campus<span>Care</span></a>
<div class="nav-links">
<a href="dashboard.php">Dashboard</a>
<a href="requests.php">Requests</a>
<?php if($_SESSION['user']['role']==='student'): ?><a href="new_request.php">Report Problem</a><a href="delete_account.php">Delete Account</a><?php endif; ?>
<?php if($_SESSION['user']['role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
<span class="user-pill"><?=e($_SESSION['user']['name'])?> · <?=ucfirst(e($_SESSION['user']['role']))?></span>
<a href="logout.php">Logout</a>
</div>
</div>
</nav>
