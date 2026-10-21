<?php

require_once __DIR__ . "/includes/auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST"
    || !validCsrfToken($_POST["csrf_token"] ?? null)) {
    http_response_code(400);
    exit("Invalid delete request.");
}

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/equipment.php";

$equipment = new Equipment($conn);

if (isset($_POST["id"])) {

$id = filter_var($_POST["id"], FILTER_VALIDATE_INT);

if ($id) {
$transactionCheck = $conn->prepare(
    "SELECT Transaction_ID FROM equipment_transactions WHERE Equipment_ID = :id LIMIT 1"
);
$transactionCheck->execute(["id" => $id]);

if ($transactionCheck->fetch()) {
header("Location: index.php?delete_blocked=1");
exit;
}

$result = $equipment->deleteEquipment($id);

if ($result) {
header("Location: index.php?deleted=1");
exit;
}
}

}

header("Location: index.php?deleted=0");
exit;