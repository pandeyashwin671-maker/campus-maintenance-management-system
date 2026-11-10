<?php
require_once "config.php";
require_login();
if ($_SESSION['user']['role']!=='student') { header("Location: requests.php"); exit; }

$cats=$conn->query("SELECT * FROM categories ORDER BY name");
$locs=$conn->query("SELECT * FROM locations ORDER BY building,floor,room");
$assets=$conn->query("SELECT a.*,l.building,l.room FROM assets a LEFT JOIN locations l ON a.location_id=l.id WHERE a.status!='Retired' ORDER BY a.asset_name");

$error="";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $cat=(int)$_POST['category_id']; $loc=(int)$_POST['location_id'];
    $asset=!empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null;
    $title=trim($_POST['title']); $desc=trim($_POST['description']);
    $priority=$_POST['priority'];
    $code="MR-".rand(1000,9999)."-".time();
    $stmt=$conn->prepare("INSERT INTO requests(request_code,user_id,category_id,location_id,asset_id,title,description,priority) VALUES(?,?,?,?,?,?,?,?)");
    $stmt->bind_param("siiiisss",$code,$_SESSION['user']['id'],$cat,$loc,$asset,$title,$desc,$priority);
    if($stmt->execute()){
        header("Location: request_view.php?id=".$stmt->insert_id);
        exit;
    }
    $error="Could not create request.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Report Problem - CampusCare</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body><?php include "nav.php"; ?>
<main class="container narrow">
<div class="page-head"><div><h1>Report a Maintenance Problem</h1><p class="muted">Provide enough detail so the right staff member can solve it quickly.</p></div></div>
<div class="panel">
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<form method="post" class="form-grid">
<div><label>Problem Category</label><select name="category_id" required><option value="">Select category</option><?php while($c=$cats->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endwhile; ?></select></div>
<div><label>Location</label><select name="location_id" required><option value="">Select location</option><?php while($l=$locs->fetch_assoc()): ?><option value="<?=$l['id']?>"><?=e($l['building'])?> · <?=e($l['floor'])?> · <?=e($l['room'])?></option><?php endwhile; ?></select></div>
<div><label>Related Asset (optional)</label><select name="asset_id"><option value="">No specific asset</option><?php while($a=$assets->fetch_assoc()): ?><option value="<?=$a['id']?>"><?=e($a['asset_name'])?> (<?=e($a['asset_code'])?>)</option><?php endwhile; ?></select></div>
<div><label>Priority</label><select name="priority"><option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option></select></div>
<div class="full-row"><label>Problem Title</label><input name="title" required maxlength="150" placeholder="e.g. Projector is not working"></div>
<div class="full-row"><label>Description</label><textarea name="description" required rows="6" placeholder="Describe what happened, when it started, and anything useful for the technician."></textarea></div>
<div class="full-row actions"><button class="btn primary">Submit Maintenance Request</button><a class="btn" href="requests.php">Cancel</a></div>
</form>
</div>
</main></body></html>
