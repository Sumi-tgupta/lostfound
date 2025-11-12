<?php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['email'], $data['password'])) {
    http_response_code(400); echo json_encode(['error'=>'missing']); exit;
}

$email = $data['email'];
$password = $data['password'];

$stmt = $pdo->prepare("SELECT UserID, PasswordHash, Role, Name FROM users WHERE Email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['PasswordHash'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Invalid credentials']);
    exit;
}

// set session
$_SESSION['user'] = [
    'UserID' => $user['UserID'],
    'Role' => $user['Role'],
    'Name' => $user['Name'],
    'Email' => $email
];

echo json_encode(['success'=>true, 'user'=>$_SESSION['user']]);
?>
