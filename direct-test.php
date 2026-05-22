<?php
// Direct test file to bypass routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Test database connection
    $pdo = new PDO('mysql:host=localhost;dbname=parrot_visa_cms', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test admin_users table
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    $adminCount = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Direct PHP backend test successful!',
        'database' => 'Connected',
        'admin_users' => $adminCount,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'server_name' => $_SERVER['SERVER_NAME'],
        'document_root' => $_SERVER['DOCUMENT_ROOT']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Direct PHP backend error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);
}
?>
