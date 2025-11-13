<?php
date_default_timezone_set("Asia/Manila");

// DB connection
$mysqli = new mysqli("localhost", "root", "", "doorlock");
if ($mysqli->connect_errno) { die("DB ERROR: " . $mysqli->connect_error); }

// HEX converter helpers
function decToLEHex($dec){
    $h=strtoupper(dechex((int)$dec));
    if(strlen($h)%2)$h='0'.$h;
    return implode('', array_reverse(str_split($h,2)));
}

function hexToLE($hex){
    $hex=strtoupper(preg_replace('/[^0-9A-F]/','',$hex));
    if(strlen($hex)%2)$hex='0'.$hex;
    return implode('', array_reverse(str_split($hex,2)));
}

$msg="";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $raw = trim($_POST['raw']);

    if ($raw === "") { $msg="Invalid input"; }
    else {
        if (ctype_digit($raw)) $uid = decToLEHex($raw);
        else $uid = hexToLE($raw);

        $stmt = $mysqli->prepare("INSERT INTO registered_cards(uid,status) VALUES(?, 'ACTIVE') ON DUPLICATE KEY UPDATE status='ACTIVE'");
        $stmt->bind_param('s', $uid);
        $stmt->execute();

        $msg = "✅ Registered HEX UID: $uid";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
body{font-family:Arial;margin:20px;}
input,button{padding:8px;width:100%;margin-top:10px;}
</style>
</head>
<body>

<h2>Register Raw UID</h2>

<?php if($msg): ?>
<p style="color:green"><?php echo $msg; ?></p>
<?php endif; ?>

<form method="post">
<label>Scan/Enter Raw UID (Decimal or HEX)</label>
<input type="text" name="raw" autofocus required>
<button type="submit">Register UID</button>
</form>

</body>
</html>
