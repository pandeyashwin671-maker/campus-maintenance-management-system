<?php
require_once "config.php";
require_login();
$u = $_SESSION['user'];

// Statistics are scoped to the logged-in user's role so students cannot
// infer or view other students' maintenance information.
if ($u['role']==='admin') {
    $total = $conn->query("SELECT COUNT(*) c FROM requests")->fetch_assoc()['c'];
    $open = $conn->query("SELECT COUNT(*) c FROM requests WHERE status NOT IN ('Closed','Rejected','Verified')")->fetch_assoc()['c'];
    $resolved = $conn->query("SELECT COUNT(*) c FROM requests WHERE status IN ('Resolved','Verified','Closed')")->fetch_assoc()['c'];
    $critical = $conn->query("SELECT COUNT(*) c FROM requests WHERE priority='Critical' AND status NOT IN ('Closed','Rejected')")->fetch_assoc()['c'];
    $totalStaff = $conn->query("SELECT COUNT(*) c FROM staff")->fetch_assoc()['c'];
    $totalStudents = $conn->query("SELECT COUNT(*) c FROM users WHERE role='student' AND active=1")->fetch_assoc()['c'];
    $where = "";
} else {
    $uid=(int)$u['id'];
    $total = $conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid")->fetch_assoc()['c'];
    $open = $conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND status NOT IN ('Closed','Rejected','Verified')")->fetch_assoc()['c'];
    $resolved = $conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND status IN ('Resolved','Verified','Closed')")->fetch_assoc()['c'];
    $critical = $conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND priority='Critical' AND status NOT IN ('Closed','Rejected')")->fetch_assoc()['c'];
    $where = "WHERE r.user_id=$uid";
}

$sql = "SELECT r.*, c.name category, l.building,l.floor,l.room,
        u.name requester, s.name staff
        FROM requests r
        JOIN categories c ON r.category_id=c.id
        JOIN locations l ON r.location_id=l.id
        JOIN users u ON r.user_id=u.id
        LEFT JOIN staff s ON r.assigned_to=s.id
        $where ORDER BY r.created_at DESC LIMIT 8";
$requests = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard - CampusCare</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script></head>
<body>
<?php include "nav.php"; ?>
<main class="container">
    <div class="page-head">
        <div><h1>Good day, <?=e($u['name'])?> 👋</h1><p class="muted"><?php if($u['role']==='student'): ?>Track your reported maintenance problems and their progress.<?php else: ?>Here's the current campus maintenance overview.<?php endif; ?></p></div>
        <?php if($u['role']==='student'): ?><a class="btn primary" href="new_request.php">+ Report Problem</a><?php endif; ?>
    </div>

    <div class="stats<?= $u['role']==='admin' ? ' stats-6' : '' ?>">
        <div class="stat"><span><?= $u['role']==='student' ? 'My Requests' : 'Total Requests' ?></span><b><?=$total?></b></div>
        <div class="stat"><span>Open Requests</span><b><?=$open?></b></div>
        <div class="stat"><span>Resolved</span><b><?=$resolved?></b></div>
        <div class="stat danger-stat"><span>Critical</span><b><?=$critical?></b></div>
        <?php if($u['role']==='admin'): ?>
        <div class="stat"><span>Total Staff</span><b><?=$totalStaff?></b></div>
        <div class="stat"><span>Total Students</span><b><?=$totalStudents?></b></div>
        <?php endif; ?>
    </div>

    <section class="panel">
        <div class="panel-head"><h2><?= $u['role']==='student' ? 'My Recent Requests' : 'Recent Maintenance Requests' ?></h2><a href="requests.php">View all →</a></div>
        <div class="table-wrap">
        <table>
            <tr><th>Request</th><th>Problem</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th>Staff</th></tr>
            <?php if($requests->num_rows===0): ?>
            <tr><td colspan="7" class="muted">No requests to show yet.</td></tr>
            <?php endif; ?>
            <?php while($r=$requests->fetch_assoc()): ?>
            <tr>
                <td><a href="request_view.php?id=<?=$r['id']?>"><?=e($r['request_code'])?></a></td>
                <td><?=e($r['title'])?></td>
                <td><?=e($r['category'])?></td>
                <td><?=e($r['building'])?> / <?=e($r['room'])?></td>
                <td><span class="badge <?=strtolower($r['priority'])?>"><?=e($r['priority'])?></span></td>
                <td><span class="badge status"><?=e($r['status'])?></span></td>
                <td><?=e($r['staff'] ?: 'Not assigned')?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </section>
</main>
</body>
</html>
