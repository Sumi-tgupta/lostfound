<?php
header('Content-Type: application/json');
require 'db_connect.php';

if (!isset($_SESSION['user'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['FoundID'])) { http_response_code(400); echo json_encode(['error'=>'Missing FoundID']); exit; }

$foundId = $data['FoundID'];
$proof = $data['proof'] ?? '';
$claimer = $_SESSION['user']['UserID'];

// check found item exists and is 'Found'
$stmt = $pdo->prepare("SELECT * FROM found_report WHERE FoundID = ? AND Status = 'Found'");
$stmt->execute([$foundId]);
$found = $stmt->fetch();
if (!$found) { http_response_code(400); echo json_encode(['error'=>'Found item not available for claims']); exit; }

// Prevent claiming your own found report
if ($found['UserID'] === $claimer) { http_response_code(403); echo json_encode(['error'=>'Cannot claim your own found report']); exit; }

$claimId = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("INSERT INTO claims(ClaimID, FoundID, ClaimerID, ProofDetails) VALUES (?, ?, ?, ?)");
$stmt->execute([$claimId, $foundId, $claimer, $proof]);

// Optionally update found_report status to 'Claimed' once admin verifies — keep as 'Found' until admin verifies
echo json_encode(['success'=>true, 'ClaimID'=>$claimId]);
?>
