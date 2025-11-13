

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

// SECURITY: Always set role to 'Student' for public registration
// Ignore any role parameter sent from the frontend
$role = 'Student';

// ✅ Allow only @nsut.ac.in domain for student registration
if (!preg_match('/^[A-Za-z0-9._%+-]+@nsut\.ac\.in$/', $email)) {
    http_response_code(403);
    echo json_encode(['error' => 'Only @nsut.ac.in email addresses are allowed for registration.']);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already registered']);
    exit;
}

// Hash password securely
$hash = password_hash($password, PASSWORD_BCRYPT);
$uid = bin2hex(random_bytes(16)); // unique 32-character ID

// Insert new user record
$stmt = $pdo->prepare("INSERT INTO users(UserID, Name, Email, Phone, PasswordHash, Role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$uid, $name, $email, $phone, $hash, $role]);

echo json_encode(['success' => true, 'UserID' => $uid]);
?>