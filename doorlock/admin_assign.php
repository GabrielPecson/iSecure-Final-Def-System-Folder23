<?php
date_default_timezone_set("Asia/Manila");

// DB connection
$mysqli = new mysqli("localhost", "root", "", "doorlock");
if ($mysqli->connect_errno) { die("DB ERROR: " . $mysqli->connect_error); }

// Clean output
function safe($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Handle assign
$msg="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $uid   = trim($_POST['uid']);
    $vfrom = $_POST['valid_from'];
    $vto   = $_POST['valid_to'];

    // Door access logic
    $doors = [];
    if (isset($_POST['door1'])) $doors[] = "DOOR1";
    if (isset($_POST['door2'])) $doors[] = "DOOR2";
    if (count($doors) == 0 || count($doors) == 2) {
        $doorAccess = "ALL";
    } else {
        $doorAccess = implode(",", $doors);
    }

    // Insert holder if new
    $stmt = $mysqli->prepare(
        "INSERT INTO card_holders(first_name, last_name)
         VALUES(?, ?)"
    );
    $stmt->bind_param('ss', $fname, $lname);
    $stmt->execute();
    $holder_id = $stmt->insert_id;

    // Update card mapping
    $stmt2 = $mysqli->prepare(
        "UPDATE registered_cards
         SET holder_id=?, door=?, valid_from=?, valid_to=?
         WHERE uid=?"
    );
    $stmt2->bind_param('issss', $holder_id, $doorAccess, $vfrom, $vto, $uid);
    $stmt2->execute();

    $msg = "✅ Card assigned to $fname $lname";
}

// Fetch cards table
$cards = [];
$res = $mysqli->query("
    SELECT rc.uid, rc.door, rc.valid_from, rc.valid_to, rc.status,
           ch.first_name, ch.last_name
    FROM registered_cards rc
    LEFT JOIN card_holders ch ON rc.holder_id = ch.holder_id
    ORDER BY rc.card_id DESC
");
while ($row = $res->fetch_assoc()) $cards[] = $row;

// Fetch available UIDs
$uids = [];
$u = $mysqli->query("SELECT uid FROM registered_cards ORDER BY card_id DESC");
while ($x = $u->fetch_assoc()) $uids[] = $x;
?>
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial; margin: 20px; }
.container { width: 500px; padding: 15px; border: 1px solid #ccc; border-radius: 8px; }
label { font-weight: bold; display: block; margin-top: 10px; }
input, select { width: 100%; padding: 8px; margin-top: 5px; }
button { margin-top:15px; padding: 10px; width: 100%; }
table { margin-top: 30px; border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ccc; padding: 6px; }
</style>
</head>
<body>

<h2>Assign Card</h2>

<?php if ($msg): ?>
<p style="color:green"><?php echo $msg; ?></p>
<?php endif; ?>

<div class="container">
<form method="post">

<label>First Name</label>
<input type="text" name="first_name" required>

<label>Last Name</label>
<input type="text" name="last_name" required>

<label>Select UID</label>
<select name="uid" required>
    <option value="">-- select card --</option>
    <?php foreach($uids as $u): ?>
    <option value="<?php echo safe($u['uid']); ?>"><?php echo safe($u['uid']); ?></option>
    <?php endforeach; ?>
</select>

<label>Valid From</label>
<input type="datetime-local" name="valid_from" required>

<label>Valid To</label>
<input type="datetime-local" name="valid_to" required>

<label>Door Access</label>
<input type="checkbox" name="door1"> Door 1<br>
<input type="checkbox" name="door2"> Door 2<br>
<small>Check none OR both = ALL</small>

<button type="submit">Assign Card</button>

</form>
</div>

<h2>Registered Cards</h2>
<table>
<tr>
<th>UID</th>
<th>Holder</th>
<th>Door</th>
<th>Valid From</th>
<th>Valid To</th>
<th>Status</th>
</tr>
<?php foreach($cards as $c): ?>
<tr>
<td><?php echo safe($c['uid']); ?></td>
<td><?php echo safe($c['first_name']." ".$c['last_name']); ?></td>
<td><?php echo safe($c['door']); ?></td>
<td><?php echo safe($c['valid_from']); ?></td>
<td><?php echo safe($c['valid_to']); ?></td>
<td><?php echo safe($c['status']); ?></td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
