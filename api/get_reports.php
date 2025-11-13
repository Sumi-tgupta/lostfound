

<?php
header('Content-Type: application/json');
require 'db_connect.php';

// optional query param: ?type=found|lost|all
$type = $_GET['type'] ?? 'all';

if ($type === 'found' || $type === 'all') {
    $stmt = $pdo->query("
        SELECT f.*, i.ItemName, i.Description, u.Name as FinderName, u.Email as FinderEmail
        FROM found_report f
        JOIN items i ON f.ItemID = i.ItemID
        JOIN users u ON f.UserID = u.UserID
        ORDER BY f.CreatedAt DESC
    ");
    $found = $stmt->fetchAll();
} else $found = [];

if ($type === 'lost' || $type === 'all') {
    $stmt = $pdo->query("
        SELECT l.*, i.ItemName, i.Description, u.Name as ReporterName, u.Email as ReporterEmail
        FROM lost_report l
        JOIN items i ON l.ItemID = i.ItemID
        JOIN users u ON l.UserID = u.UserID
        ORDER BY l.CreatedAt DESC
    ");
    $lost = $stmt->fetchAll();
} else $lost = [];

echo json_encode(['found'=>$found, 'lost'=>$lost]);
?>