<?php
require_once "config.php";
require_login();
$u=$_SESSION['user'];
$where="";
if($u['role']==='student') $where="WHERE r.user_id=".(int)$u['id'];

$sql="SELECT r.*,c.name category,l.building,l.room,u.name requester,s.name staff
      FROM requests r JOIN categories c ON r.category_id=c.id
      JOIN locations l ON r.location_id=l.id JOIN users u ON r.user_id=u.id
      LEFT JOIN staff s ON r.assigned_to=s.id
      $where ORDER BY FIELD(r.priority,'Critical','High','Medium','Low'),r.created_at DESC";
$res=$conn->query($sql);
?>
<!DOCTYPE html><html><head><title>Requests - CampusCare</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body><?php include "nav.php"; ?><main class="container">
<div class="page-head"><div><h1><?= $u['role']==='student' ? 'My Maintenance Requests' : 'Maintenance Requests' ?></h1><p class="muted"><?= $u['role']==='student' ? 'Only your reported problems are shown here.' : 'Track and manage all reported campus problems.' ?></p></div><?php if($u['role']==='student'): ?><a class="btn primary" href="new_request.php">+ New Request</a><?php endif; ?></div>
<div class="panel"><div class="table-wrap"><table>
<tr><th>ID</th><th>Problem</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th>Assigned To</th></tr>
<?php if($res->num_rows===0): ?><tr><td colspan="7" class="muted">Nothing to show here yet.</td></tr><?php endif; ?>
<?php while($r=$res->fetch_assoc()): ?><tr>
<td><a href="request_view.php?id=<?=$r['id']?>"><?=e($r['request_code'])?></a></td>
<td><?=e($r['title'])?></td><td><?=e($r['category'])?></td>
<td><?=e($r['building'])?> / <?=e($r['room'])?></td>
<td><span class="badge <?=strtolower($r['priority'])?>"><?=e($r['priority'])?></span></td>
<td><span class="badge status"><?=e($r['status'])?></span></td>
<td><?=e($r['staff'] ?: 'Not assigned')?></td>
</tr><?php endwhile; ?>
</table></div></div></main></body></html>
