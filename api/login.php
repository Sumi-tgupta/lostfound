<?php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Missing credentials']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

$stmt = $pdo->prepare("SELECT UserID, PasswordHash, Role, Name FROM users WHERE Email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error'=>'Invalid email or password']);
    exit;
}

// ✅ Allow admins with any domain, restrict others
if ($user['Role'] !== 'Admin' && !preg_match('/^[A-Za-z0-9._%+-]+@nsut\.ac\.in$/', $email)) {
    http_response_code(403);
    echo json_encode(['error'=>'Only @nsut.ac.in email addresses can log in']);
    exit;
}

// Verify password
if (!password_verify($password, $user['PasswordHash'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Invalid email or password']);
    exit;
}

// Start session
$_SESSION['user'] = [
    'UserID' => $user['UserID'],
    'Role' => $user['Role'],
    'Name' => $user['Name'],
    'Email' => $email
];

echo json_encode(['success'=>true, 'user'=>$_SESSION['user']]);
?>
