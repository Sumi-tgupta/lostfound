<?php
// db_connect.php
session_start();

/*
|--------------------------------------------------------------------------
| CORS HEADERS (Required for Vercel → Localhost communication)
|--------------------------------------------------------------------------
| These headers allow your Vercel frontend to make requests to your
| backend running on localhost/XAMPP.
|
| VERY IMPORTANT: If you deploy the backend online later,
| update Allowed-Origin to your domain for security.
|--------------------------------------------------------------------------
*/

header("Access-Control-Allow-Origin: *");  // Allow any frontend (Vercel)
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle CORS preflight requests from browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION (XAMPP Localhost)
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$db   = 'lost_found_db';
$user = 'root';
$pass = ''; // Default for XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'DB connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}
?>
