<?php

require_once __DIR__ . "/includes/auth.php";
requireLogin();
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/equipment.php";

$equipment = new Equipment($conn);

$keyword = "";

$records = [];

if (isset($_GET["keyword"])) {

$keyword = $_GET["keyword"];

$records = $equipment->searchEquipments($keyword);

}

?>

<!DOCTYPE html>
<html>

<head>

<title>Search Equipment</title>

<style>

body {
font-family: Arial, sans-serif;
margin: 40px;
background: #f4f4f4;
}

.container {
max-width: 900px;
margin: auto;
background: white;
padding: 25px;
}

input {
padding: 10px;
width: 300px;
}

button {
padding: 10px 15px;
}

table {
width: 100%;
border-collapse: collapse;
margin-top: 20px;
}

th, td {
padding: 10px;
border: 1px solid #ddd;
}

th {
background: #333;
color: white;
}

</style>

</head>

<body>

<div class="container">

<h1>Search Equipment</h1>

<form method="GET">

<input
type="text"
name="keyword"
placeholder="Enter keyword..."
value="<?php echo htmlspecialchars($keyword); ?>"
required
>

<button type="submit">
Search
</button>

<a href="index.php">
Back
</a>

</form>

<?php if ($keyword != ""): ?>

<h3>
Search results for:
<?php echo htmlspecialchars($keyword); ?>
</h3>

<table>

<tr>
<th>ID</th>
<th>Equipment Name</th>
<th>Status</th>
<th>Purchase Date</th>
</tr>

<?php if (count($records) > 0): ?>

<?php foreach ($records as $record): ?>

<tr>

<td>
<?php echo htmlspecialchars($record["Equipment_ID"]); ?>
</td>

<td>
<?php echo htmlspecialchars($record["Equipment_Name"]); ?>
</td>

<td>
<?php echo htmlspecialchars($record["Status"]); ?>
</td>

<td>
<?php echo htmlspecialchars($record["Purchase_Date"]); ?>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="4">
No matching equipment found.
</td>
</tr>

<?php endif; ?>

</table>

<?php endif; ?>

</div>

</body>
</html>