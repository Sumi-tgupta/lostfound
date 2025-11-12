<?php
header('Content-Type: application/json');
require 'db_connect.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') { http_response_code(403); echo json_encode(['error'=>'Only admins']); exit; }

$stmt = $pdo->query("
  SELECT c.ClaimID, c.FoundID, c.ClaimerID, c.ProofDetails, c.Status as ClaimStatus, c.ClaimDate,
         u.Name as ClaimerName, i.ItemName, i.Description
  FROM claims c
  JOIN users u ON c.ClaimerID = u.UserID
  JOIN found_report f ON c.FoundID = f.FoundID
  JOIN items i ON f.ItemID = i.ItemID
  WHERE c.Status = 'Pending'
  ORDER BY c.ClaimDate DESC
");
$claims = $stmt->fetchAll();
echo json_encode(['claims'=>$claims]);
?>
