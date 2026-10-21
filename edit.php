<?php

require_once __DIR__ . "/includes/auth.php";
requireLogin();
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/equipment.php";

$equipment = new Equipment($conn);

if (!isset($_GET["id"])) {
header("Location: index.php");
exit;
}

$id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
header("Location: index.php");
exit;
}

$records = $equipment->getEquipments();

$selectedEquipment = null;

foreach ($records as $record) {

if ($record["Equipment_ID"] == $id) {

$selectedEquipment = $record;
break;

}
}

if ($selectedEquipment === null) {

die("Equipment not found.");

}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (!validCsrfToken($_POST["csrf_token"] ?? null)) {
http_response_code(419);
$message = "Your form expired. Refresh the page and try again.";
} else {
$name = trim($_POST["equipment_name"] ?? "");
$status = $_POST["status"] ?? "";
$purchaseDate = $_POST["purchase_date"] ?? "";

$validated = Equipment::validateEquipmentInput($name, $status, $purchaseDate);

if (!$validated["ok"]) {
$message = $validated["message"];
} else {
$result = $equipment->updateEquipment(
$id,
$validated["name"],
$validated["status"],
$validated["purchase_date"]
);

if ($result) {

header("Location: index.php");
exit;

} else {

$message = "Failed to update equipment.";

}
}
}
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Edit Equipment</title>

<style>

body {
font-family: Arial, sans-serif;
background: #f4f4f4;
margin: 40px;
}

.container {
max-width: 500px;
margin: auto;
background: white;
padding: 25px;
border-radius: 8px;
}

input, select {
width: 100%;
padding: 10px;
margin: 8px 0 15px;
box-sizing: border-box;
}

button {
padding: 10px 20px;
cursor: pointer;
}

.back {
margin-left: 10px;
}

.error {
color: red;
}

</style>

</head>

<body>

<div class="container">

<h1>Edit Equipment</h1>

<?php if ($message != ""): ?>

<p class="error">
<?php echo $message; ?>
</p>

<?php endif; ?>

<form method="POST">

<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8"); ?>">

<label>Equipment Name</label>

<input
type="text"
name="equipment_name"
value="<?php echo htmlspecialchars($selectedEquipment["Equipment_Name"]); ?>"
required
>

<label>Status</label>

<select name="status" required>

<option value="Working"
<?php if ($selectedEquipment["Status"] == "Working") echo "selected"; ?>>
Working
</option>

<option value="Under Maintenance"
<?php if ($selectedEquipment["Status"] == "Under Maintenance") echo "selected"; ?>>
Under Maintenance
</option>

<option value="Damaged"
<?php if ($selectedEquipment["Status"] == "Damaged") echo "selected"; ?>>
Damaged
</option>

</select>

<label>Purchase Date</label>

<input
type="date"
name="purchase_date"
value="<?php echo htmlspecialchars($selectedEquipment["Purchase_Date"]); ?>"
required
>

<button type="submit">
Update Equipment
</button>

<a href="index.php" class="back">
Cancel
</a>

</form>

</div>

</body>
</html>
