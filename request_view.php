<?php
require_once "config.php";
require_login();
$id=(int)($_GET['id']??0);
$stmt=$conn->prepare("SELECT r.*,c.name category,l.building,l.floor,l.room,u.name requester,u.email requester_email,s.name staff,
IFNULL(a.asset_name,'') asset_name,IFNULL(a.asset_code,'') asset_code
FROM requests r JOIN categories c ON r.category_id=c.id JOIN locations l ON r.location_id=l.id
JOIN users u ON r.user_id=u.id LEFT JOIN staff s ON r.assigned_to=s.id
LEFT JOIN assets a ON r.asset_id=a.id WHERE r.id=?");
$stmt->bind_param("i",$id);$stmt->execute();$r=$stmt->get_result()->fetch_assoc();
if(!$r){
    include "nav.php";
    echo "<main class='container narrow'><section class='panel'><h1>Request Not Found</h1><p class='muted'>This maintenance request does not exist.</p><a class='btn primary' href='dashboard.php'>Back to Dashboard</a></section></main>";
    exit;
}

$role=$_SESSION['user']['role'];
$isOwner=((int)$_SESSION['user']['id']===(int)$r['user_id']);
$isAdmin=($role==='admin');

// Strict data isolation: a student can open only their own requests, while
// admin can view everything. There is no staff login, so no staff branch.
if(!$isAdmin && !$isOwner){
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>Access Denied - CampusCare</title><link rel='stylesheet' href='style.css'></head><body>";
    include "nav.php";
    echo "<main class='container narrow'><section class='panel'><h1>Access Denied</h1><p class='muted'>You can only view your own requests.</p><a class='btn primary' href='dashboard.php'>Back to Dashboard</a></section></main></body></html>";
    exit;
}

$historyStmt=$conn->prepare("SELECT h.*,u.name FROM status_history h JOIN users u ON h.changed_by=u.id WHERE h.request_id=? ORDER BY h.changed_at");
$historyStmt->bind_param("i",$id);$historyStmt->execute();$history=$historyStmt->get_result();

$error="";
$success="";
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['admin_status']) && $isAdmin){
        $new=$_POST['admin_status'];
        $allowed=['New','Under Review','Assigned','In Progress','Waiting for Parts','Resolved','Verified','Closed','Rejected','Reopened'];
        if(!in_array($new,$allowed,true)){
            $error="Invalid request status.";
        } else {
            $notes=trim($_POST['resolution_notes']??'');
            $stmt=$conn->prepare("UPDATE requests SET status=?, resolution_notes=?, resolved_at=IF(?='Resolved',NOW(),resolved_at) WHERE id=?");
            $stmt->bind_param("sssi",$new,$notes,$new,$id);$stmt->execute();
            $stmt=$conn->prepare("INSERT INTO status_history(request_id,old_status,new_status,changed_by) VALUES(?,?,?,?)");
            $stmt->bind_param("issi",$id,$r['status'],$new,$_SESSION['user']['id']);$stmt->execute();
            $success="Request updated.";
        }
    } elseif(isset($_POST['owner_action']) && $isOwner){
        // The reporter can see progress and can confirm a resolved task
        // or reopen it, but cannot edit staff details.
        $action=$_POST['owner_action'];
        if($action==='verify' && $r['status']==='Resolved'){
            $new='Verified';
        } elseif($action==='reopen' && in_array($r['status'],['Resolved','Verified','Closed'],true)){
            $new='Reopened';
        } else {
            $new='';
        }
        if($new!==''){
            $stmt=$conn->prepare("UPDATE requests SET status=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sii",$new,$id,$_SESSION['user']['id']);$stmt->execute();
            $stmt=$conn->prepare("INSERT INTO status_history(request_id,old_status,new_status,changed_by) VALUES(?,?,?,?)");
            $stmt->bind_param("issi",$id,$r['status'],$new,$_SESSION['user']['id']);$stmt->execute();
            $success=$new==='Verified' ? "Resolution confirmed." : "Request reopened for further work.";
        }
    } elseif(isset($_POST['feedback']) && $isOwner && in_array($r['status'],['Resolved','Verified','Closed'],true)){
        $rating=(int)$_POST['rating'];$comment=trim($_POST['comment']??'');
        if($rating>=1 && $rating<=5){
            $stmt=$conn->prepare("INSERT INTO feedback(request_id,user_id,rating,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)");
            $stmt->bind_param("iiis",$id,$_SESSION['user']['id'],$rating,$comment);$stmt->execute();
            $success="Feedback saved.";
        }
    }
    if($success || $error){
        header("Location: request_view.php?id=$id" . ($error ? "&error=1" : "&updated=1"));exit;
    }
}

if(isset($_GET['updated'])) $success="Update saved.";
if(isset($_GET['error'])) $error="The requested update could not be completed.";
?>
<!DOCTYPE html><html><head><title><?=e($r['request_code'])?> - CampusCare</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body><?php include "nav.php"; ?><main class="container">
<div class="page-head"><div><h1><?=e($r['request_code'])?> · <?=e($r['title'])?></h1><p class="muted">Reported by <?=e($r['requester'])?> on <?=date('d M Y, h:i A',strtotime($r['created_at']))?></p></div></div>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
<div class="detail-grid">
<section class="panel">
<h2>Request Details</h2>
<div class="detail-list">
<div><span>Category</span><b><?=e($r['category'])?></b></div>
<div><span>Location</span><b><?=e($r['building'])?> · <?=e($r['floor'])?> · <?=e($r['room'])?></b></div>
<div><span>Asset</span><b><?=e($r['asset_name'] ?: 'None')?> <?=e($r['asset_code'])?></b></div>
<div><span>Priority</span><b class="badge <?=strtolower($r['priority'])?>"><?=e($r['priority'])?></b></div>
<div><span>Assigned Staff</span><b><?=e($r['staff'] ?: 'Not assigned')?></b></div>
<div><span>Current Progress</span><b class="badge status"><?=e($r['status'])?></b></div>
</div>
<hr><h3>Description</h3><p><?=nl2br(e($r['description']))?></p>
<?php if($r['resolution_notes']): ?><hr><h3>Work / Resolution Notes</h3><p><?=nl2br(e($r['resolution_notes']))?></p><?php endif; ?>
</section>
<section class="panel">
<h2>Request Timeline</h2>
<div class="timeline"><div class="timeline-item"><b>New</b><small><?=date('d M Y, h:i A',strtotime($r['created_at']))?></small></div>
<?php while($h=$history->fetch_assoc()): ?><div class="timeline-item"><b><?=e($h['new_status'])?></b><small>by <?=e($h['name'])?> · <?=date('d M Y, h:i A',strtotime($h['changed_at']))?></small></div><?php endwhile; ?></div>

<?php if($isAdmin): ?>
<hr><h3>Admin Request Control</h3>
<form method="post"><label>Status</label><select name="admin_status">
<?php foreach(['New','Under Review','Assigned','In Progress','Waiting for Parts','Resolved','Verified','Closed','Rejected','Reopened'] as $s): ?><option <?=$r['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
</select><label>Resolution / work notes</label><textarea name="resolution_notes" rows="4"><?=e($r['resolution_notes'])?></textarea><button class="btn primary full">Save Admin Update</button></form>
<?php endif; ?>

<?php if($isOwner && $r['status']==='Resolved'): ?>
<hr><h3>Check the Completed Work</h3>
<p class="muted">You can confirm that the reported problem is fixed, or reopen it if the problem is still present.</p>
<form method="post" class="actions"><button class="btn primary" name="owner_action" value="verify">Confirm Resolution</button><button class="btn danger-btn" name="owner_action" value="reopen">Reopen Request</button></form>
<?php elseif($isOwner && in_array($r['status'],['Verified','Closed'],true)): ?>
<hr><h3>Problem Still Not Fixed?</h3>
<form method="post"><button class="btn danger-btn" name="owner_action" value="reopen">Reopen Request</button></form>
<?php endif; ?>

<?php if($isOwner && in_array($r['status'],['Resolved','Verified','Closed'],true)): ?>
<hr><h3>Rate the Resolution</h3>
<form method="post"><input type="hidden" name="feedback" value="1"><label>Rating</label><select name="rating"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select><label>Comment</label><textarea name="comment" rows="3" placeholder="Was the problem properly resolved?"></textarea><button class="btn">Submit Feedback</button></form>
<?php endif; ?>
</section></div></main></body></html>
