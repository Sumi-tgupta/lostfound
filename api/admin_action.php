<?php
header('Content-Type: application/json');
require 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') { http_response_code(403); echo json_encode(['error'=>'Only admins']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['ClaimID'], $data['action'])) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }

$claimId = $data['ClaimID'];
$action = $data['action']; // 'Verified' or 'Rejected' or 'UnderReview'
$remarks = $data['remarks'] ?? '';
$adminId = $_SESSION['user']['UserID'];

// validate claim exists
$stmt = $pdo->prepare("SELECT * FROM claims WHERE ClaimID=?");
$stmt->execute([$claimId]);
$claim = $stmt->fetch();
if (!$claim) { http_response_code(400); echo json_encode(['error'=>'Claim not found']); exit; }

// insert admin action
$actionId = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("INSERT INTO admin_action(ActionID, ClaimID, AdminID, ActionTaken, Remarks) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$actionId, $claimId, $adminId, $action, $remarks]);

// update claim status
$stmt = $pdo->prepare("UPDATE claims SET Status = ? WHERE ClaimID = ?");
$stmt->execute([$action === 'Verified' ? 'Verified' : ($action === 'Rejected' ? 'Rejected' : 'Pending'), $claimId]);

// if verified, update found_report status to 'Returned'
if ($action === 'Verified') {
    $stmt = $pdo->prepare("UPDATE found_report SET Status = 'Returned' WHERE FoundID = ?");
    $stmt->execute([$claim['FoundID']]);
}

echo json_encode(['success'=>true, 'ActionID'=>$actionId]);
?>
