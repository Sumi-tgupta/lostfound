<?php
header('Content-Type: application/json');
require 'db_connect.php';

// require login
if (!isset($_SESSION['user'])) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }

$reportType = $data['reportType']; // 'lost' or 'found'
$itemName = trim($data['itemName']);
$description = $data['description'] ?? '';
$category = $data['category'] ?? 'Other';
$location = $data['location'] ?? '';
$reportDate = $data['reportDate'] ?? date('Y-m-d');
$userId = $_SESSION['user']['UserID'];

// create item
$itemId = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("INSERT INTO items(ItemID, ItemName, Description, Category) VALUES (?, ?, ?, ?)");
$stmt->execute([$itemId, $itemName, $description, $category]);

if ($reportType === 'lost') {
    $lostId = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO lost_report(LostID, UserID, ItemID, Location, ReportDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$lostId, $userId, $itemId, $location, $reportDate]);
    echo json_encode(['success'=>true, 'LostID'=>$lostId]);
    exit;
} else {
    $foundId = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO found_report(FoundID, UserID, ItemID, Location, ReportDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$foundId, $userId, $itemId, $location, $reportDate]);
    echo json_encode(['success'=>true, 'FoundID'=>$foundId]);
    exit;
}
?>
