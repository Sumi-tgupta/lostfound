<?php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['email'], $data['name'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$email = trim($data['email']);
$name = trim($data['name']);
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$password = $data['password'];
$role = isset($data['role']) ? $data['role'] : 'Student';

// check email
$stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already registered']);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$uid = bin2hex(random_bytes(16)); // 32-char id

$stmt = $pdo->prepare("INSERT INTO users(UserID, Name, Email, Phone, PasswordHash, Role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$uid, $name, $email, $phone, $hash, $role]);

echo json_encode(['success' => true, 'UserID' => $uid]);
?>
