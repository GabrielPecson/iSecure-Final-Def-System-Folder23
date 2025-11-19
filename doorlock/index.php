<?php
// Simplified Admin Page - Register card only

// DB connection
$DB_HOST="localhost";$DB_USER="root";$DB_PASS="";$DB_NAME="doorlock";
$mysqli=new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if($mysqli->connect_errno){die('DB ERROR:'.$mysqli->connect_error);} 

date_default_timezone_set('Asia/Manila');

function safe($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

// HEX converters
function decToLEHex($dec){$h=strtoupper(dechex((int)$dec));if(strlen($h)%2)$h='0'.$h;$b=array_reverse(str_split($h,2));return implode('',$b);} 
function hexToLE($hex){$hex=strtoupper(preg_replace('/[^0-9A-F]/','',$hex));if(strlen($hex)%2)$hex='0'.$hex;$b=array_reverse(str_split($hex,2));return implode('',$b);} 

$msg="";

// Handle register
if(isset($_POST['register_card'])){
  $raw=trim($_POST['raw']);
  if($raw===''){$msg="Invalid UID";} else {
    if(ctype_digit($raw)) $uid=decToLEHex($raw);
    else $uid=hexToLE($raw);
    $stmt=$mysqli->prepare("INSERT INTO registered_cards(uid,status) VALUES(?, 'ACTIVE') ON DUPLICATE KEY UPDATE status='ACTIVE'");
    $stmt->bind_param('s',$uid);
    $stmt->execute();
    $msg="Card registered: $uid";
  }
}

// Fetch list
$cards=[];
$rc=$mysqli->query("SELECT uid,status FROM registered_cards ORDER BY card_id DESC");while($c=$rc->fetch_assoc())$cards[]=$c;
?>
<!DOCTYPE html>
<html>
<head>
<title>Register Cards</title>
<style>
body{font-family:Arial;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ccc;padding:6px;}input{padding:6px;width:100%;margin:6px 0;}button{padding:8px 12px;}
</style>
</head>
<body>
<h2>Register Cards</h2>
<?php if($msg): ?><p style="color:green;font-weight:bold;"><?php echo safe($msg);?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="register_card" value="1"/>
  <label>Raw UID (Decimal or HEX)</label>
  <input name="raw" required />
  <button>Register Card</button>
</form>
<table>
<tr><th>UID</th><th>Status</th></tr>
<?php foreach($cards as $c): ?>
<tr>
  <td><?php echo safe($c['uid']);?></td>
  <td><?php echo safe($c['status']);?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
