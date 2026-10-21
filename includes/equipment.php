<?php

class Equipment
{
private $conn;

public const VALID_STATUSES = [
    'Working',
    'Under Maintenance',
    'Damaged',
];

public function __construct($db)
{
$this->conn = $db;
}

public static function validateEquipmentInput($name, $status, $purchaseDate)
{
    $cleanName = trim((string) $name);

    if ($cleanName === '' || mb_strlen($cleanName) > 150) {
        return [
            'ok' => false,
            'message' => 'Enter an equipment name between 1 and 150 characters.',
        ];
    }

    if (!in_array($status, self::VALID_STATUSES, true)) {
        return [
            'ok' => false,
            'message' => 'Choose a valid equipment status.',
        ];
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $purchaseDate)) {
        return [
            'ok' => false,
            'message' => 'Choose a valid purchase date.',
        ];
    }

    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $purchaseDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $purchaseDate) {
        return [
            'ok' => false,
            'message' => 'Choose a valid purchase date.',
        ];
    }

    return [
        'ok' => true,
        'name' => $cleanName,
        'status' => $status,
        'purchase_date' => $purchaseDate,
    ];
}

// ADD EQUIPMENT
public function addEquipment($name, $status, $purchaseDate)
{
try {
$sql = "INSERT INTO equipment
(Equipment_Name, Status, Purchase_Date)
VALUES (:name, :status, :purchaseDate)";

$stmt = $this->conn->prepare($sql);

$stmt->bindParam(":name", $name);
$stmt->bindParam(":status", $status);
$stmt->bindParam(":purchaseDate", $purchaseDate);

return $stmt->execute();

} catch (PDOException $e) {
error_log('Error adding equipment: ' . $e->getMessage());
return false;
}
}

// GET ALL EQUIPMENT
public function getEquipments()
{
try {
$sql = "SELECT * FROM equipment
ORDER BY Equipment_ID DESC";

$stmt = $this->conn->prepare($sql);
$stmt->execute();

return $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
echo "Error fetching equipment: " . $e->getMessage();
return [];
}
}

// UPDATE EQUIPMENT
public function updateEquipment($id, $name, $status, $purchaseDate)
{
try {
$sql = "UPDATE equipment
SET Equipment_Name = :name,
Status = :status,
Purchase_Date = :purchaseDate
WHERE Equipment_ID = :id";

$stmt = $this->conn->prepare($sql);

$stmt->bindParam(":id", $id);
$stmt->bindParam(":name", $name);
$stmt->bindParam(":status", $status);
$stmt->bindParam(":purchaseDate", $purchaseDate);

return $stmt->execute();

} catch (PDOException $e) {
echo "Error updating equipment: " . $e->getMessage();
return false;
}
}

// DELETE EQUIPMENT
public function deleteEquipment($id)
{
try {
$sql = "DELETE FROM equipment
WHERE Equipment_ID = :id";

$stmt = $this->conn->prepare($sql);

$stmt->bindParam(":id", $id);

return $stmt->execute();

} catch (PDOException $e) {
echo "Error deleting equipment: " . $e->getMessage();
return false;
}
}

// SEARCH EQUIPMENT
public function searchEquipments($keyword)
{
try {
$sql = "SELECT * FROM equipment
WHERE Equipment_ID LIKE :keyword
OR Equipment_Name LIKE :keyword
OR Status LIKE :keyword
OR Purchase_Date LIKE :keyword
ORDER BY Equipment_ID DESC";

$stmt = $this->conn->prepare($sql);

$search = "%" . $keyword . "%";

$stmt->bindParam(":keyword", $search);

$stmt->execute();

return $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
echo "Error searching equipment: " . $e->getMessage();
return [];
}
}
}
?>