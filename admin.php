<?php
require_once "config.php";
require_login();
if($_SESSION['user']['role']!=='admin'){header("Location: dashboard.php");exit;}

if($_SERVER['REQUEST_METHOD']==='POST') {
    if(isset($_POST['assign'])) {
        $request=(int)$_POST['request_id'];
        $staff=(int)$_POST['staff_id'];

        $check = $conn->prepare("SELECT id FROM staff WHERE id=? LIMIT 1");
        $check->bind_param("i", $staff);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            flash_set('error', "Selected staff member does not exist.");
        } else {
            $oldStmt = $conn->prepare("SELECT status FROM requests WHERE id=? LIMIT 1");
            $oldStmt->bind_param("i", $request);
            $oldStmt->execute();
            $oldRow = $oldStmt->get_result()->fetch_assoc();
            $oldStatus = $oldRow['status'] ?? 'New';

            $stmt=$conn->prepare("UPDATE requests SET assigned_to=?,status='Assigned' WHERE id=?");
            $stmt->bind_param("ii",$staff,$request);
            $stmt->execute();

            $stmt=$conn->prepare("INSERT INTO status_history(request_id,old_status,new_status,changed_by) VALUES(?,?,?,?)");
            $newStatus = 'Assigned';
            $stmt->bind_param("issi",$request,$oldStatus,$newStatus,$_SESSION['user']['id']);
            $stmt->execute();
            flash_set('success', "Request assigned successfully.");
        }
    }

    // Redirect after every POST (Post/Redirect/Get) so a refresh never
    // resubmits the form and the page always shows fresh data.
    if(isset($_POST['mark_done'])) {
        $request=(int)$_POST['request_id'];

        $oldStmt = $conn->prepare("SELECT status FROM requests WHERE id=? LIMIT 1");
        $oldStmt->bind_param("i", $request);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        $oldStatus = $oldRow['status'] ?? 'Assigned';

        $stmt=$conn->prepare("UPDATE requests SET status='Resolved', resolved_at=NOW() WHERE id=?");
        $stmt->bind_param("i",$request);
        $stmt->execute();

        $newStatus='Resolved';
        $stmt=$conn->prepare("INSERT INTO status_history(request_id,old_status,new_status,changed_by) VALUES(?,?,?,?)");
        $stmt->bind_param("issi",$request,$oldStatus,$newStatus,$_SESSION['user']['id']);
        $stmt->execute();
        flash_set('success', "Task marked as work done.");
    }

    header("Location: admin.php");
    exit;
}

$error = flash_get('error');
$success = flash_get('success');

// Staff is now fixed reference data - no add/remove, no login, no active flag.
$staff=$conn->query("SELECT id,name,specialty FROM staff ORDER BY name");
$staffList=[];
while($s=$staff->fetch_assoc()) $staffList[]=$s;

$pending=$conn->query("SELECT r.*,c.name category,l.building,l.room,u.name requester FROM requests r JOIN categories c ON r.category_id=c.id JOIN locations l ON r.location_id=l.id JOIN users u ON r.user_id=u.id WHERE r.assigned_to IS NULL ORDER BY FIELD(r.priority,'Critical','High','Medium','Low'),r.created_at");
$ongoing=$conn->query("SELECT r.*,c.name category,l.building,l.room,u.name requester,s.name staff FROM requests r JOIN categories c ON r.category_id=c.id JOIN locations l ON r.location_id=l.id JOIN users u ON r.user_id=u.id LEFT JOIN staff s ON r.assigned_to=s.id WHERE r.assigned_to IS NOT NULL AND r.status NOT IN ('Resolved','Verified','Closed','Rejected') ORDER BY FIELD(r.priority,'Critical','High','Medium','Low'),r.created_at");
?>
<!DOCTYPE html><html><head><title>Admin - CampusCare</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body><?php include "nav.php"; ?><main class="container">
<div class="page-head"><div><h1>Admin Control Center</h1><p class="muted">Assign requests and monitor maintenance workload.</p></div></div>

<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>

<div class="detail-grid">
<section class="panel">
<h2>Active Staff</h2>
<p class="muted">Default maintenance staff available for assignment.</p>
<?php if(count($staffList)===0): ?><p class="muted">No staff members configured.</p><?php endif; ?>
<?php foreach($staffList as $s): ?>
<div class="staff-row">
<div><b><?=e($s['name'])?></b><small><?=e($s['specialty'] ?: 'General maintenance')?></small></div>
</div>
<?php endforeach; ?>
</section>
</div>

<div class="detail-grid">
<section class="panel"><h2>Unassigned Requests</h2>
<?php if($pending->num_rows===0): ?><p class="muted">All requests are currently assigned.</p><?php endif; ?>
<?php while($r=$pending->fetch_assoc()): ?><div class="admin-request">
<div><b><?=e($r['request_code'])?></b> — <?=e($r['title'])?><br><small><?=e($r['category'])?> · <?=e($r['building'])?> / <?=e($r['room'])?></small></div>
<form method="post" class="inline"><input type="hidden" name="assign" value="1"><input type="hidden" name="request_id" value="<?=$r['id']?>">
<select name="staff_id" required><option value="">Assign staff</option><?php foreach($staffList as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach; ?></select><button class="btn small-btn">Assign</button></form>
</div><?php endwhile; ?>
</section>
</div>

<div class="detail-grid">
<section class="panel"><h2>Assigned / In-Progress Tasks</h2>
<p class="muted">Tasks currently with a technician. Mark a task as done once the work is finished.</p>
<?php if($ongoing->num_rows===0): ?><p class="muted">No tasks are currently in progress.</p><?php endif; ?>
<?php while($r=$ongoing->fetch_assoc()): ?><div class="admin-request">
<div><b><?=e($r['request_code'])?></b> — <?=e($r['title'])?><br><small><?=e($r['category'])?> · <?=e($r['building'])?> / <?=e($r['room'])?> · Assigned to <?=e($r['staff'] ?: 'Unknown')?> · <span class="badge status"><?=e($r['status'])?></span></small></div>
<form method="post" class="inline"><input type="hidden" name="mark_done" value="1"><input type="hidden" name="request_id" value="<?=$r['id']?>"><button class="btn primary small-btn">Mark Work Done</button></form>
</div><?php endwhile; ?>
</section>
</div>

</main></body></html>
