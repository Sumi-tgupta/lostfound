<?php
// api/get_items.php
header('Content-Type: application/json');
require 'db_connect.php';

// default response
$response = ['success' => false];

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

try {
    if ($action === 'categories') {
        $stmt = $pdo->query("SELECT DISTINCT Category FROM items ORDER BY Category");
        $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'categories' => $cats]);
        exit;
    }

    if ($action === 'byid') {
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            exit;
        }
        $id = $_GET['id'];

        // Get item details
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   COALESCE(f.FoundID, l.LostID) AS related_report_id
            FROM items i
            LEFT JOIN found_report f ON f.ItemID = i.ItemID
            LEFT JOIN lost_report l ON l.ItemID = i.ItemID
            WHERE i.ItemID = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }

        // Fetch related found reports (if any)
        $stmt = $pdo->prepare("
            SELECT f.*, u.Name AS FinderName 
            FROM found_report f
            JOIN users u ON f.UserID = u.UserID
            WHERE f.ItemID = ?
            ORDER BY f.CreatedAt DESC
        ");
        $stmt->execute([$id]);
        $found = $stmt->fetchAll();

        // Fetch related lost reports (if any)
        $stmt = $pdo->prepare("
            SELECT l.*, u.Name AS ReporterName 
            FROM lost_report l
            JOIN users u ON l.UserID = u.UserID
            WHERE l.ItemID = ?
            ORDER BY l.CreatedAt DESC
        ");
        $stmt->execute([$id]);
        $lost = $stmt->fetchAll();

        echo json_encode(['success' => true, 'item' => $item, 'found_reports' => $found, 'lost_reports' => $lost]);
        exit;
    }

    // default: list items optionally filtered by category or search
    if ($action === 'list') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        $where = [];
        $params = [];

        if ($category !== '') {
            $where[] = "i.Category = ?";
            $params[] = $category;
        }
        if ($search !== '') {
            $where[] = "(i.ItemName LIKE ? OR i.Description LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT i.ItemID, i.ItemName, i.Category, i.Description, i.CreatedAt,
                   f.FoundID, f.Location AS FoundLocation, f.ReportDate AS FoundDate, f.Status AS FoundStatus,
                   u.Name as FinderName
            FROM items i
            LEFT JOIN found_report f ON f.ItemID = i.ItemID
            LEFT JOIN users u ON f.UserID = u.UserID
            $where_sql
            ORDER BY i.CreatedAt DESC
            LIMIT ? OFFSET ?
        ";

        // append limit/offset params
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $ex->getMessage()]);
    exit;
}
