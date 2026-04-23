<?php

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../../.env');

// Site Configuration
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/backend/');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Parrot Canada Visa Consultant - Admin');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@parrotvisa.com');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'parrot_visa_cms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Upload Configuration
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: 'public/backend/uploads/');
define('MAX_FILE_SIZE', getenv('MAX_FILE_SIZE') ?: 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Session Configuration
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600); // 1 hour

// Security
define('HASH_ALGO', getenv('HASH_ALGO') ?: PASSWORD_DEFAULT);
define('BCRYPT_COST', getenv('BCRYPT_COST') ?: 12);
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'default_jwt_secret_change_this');
define('APP_KEY', getenv('APP_KEY') ?: 'default_app_key_change_this');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('Africa/Kigali');

// Error Reporting
$debug = getenv('DEBUG') ?: 'true';
if ($debug === 'false') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Start Session
session_start();

// Helper Functions
function baseUrl($path = '') {
    return SITE_URL . $path;
}

function assetUrl($path) {
    return SITE_URL . 'public/backend/assets/' . $path;
}

function uploadUrl($path) {
    return SITE_URL . 'public/backend/uploads/' . $path;
}

function redirect($path) {
    header('Location: ' . baseUrl($path));
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('auth/login');
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function hashPassword($password) {
    return password_hash($password, HASH_ALGO, ['cost' => BCRYPT_COST]);
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function paginate($total_items, $current_page = 1, $items_per_page = ITEMS_PER_PAGE) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'total_items' => $total_items,
        'has_next' => $current_page < $total_pages,
        'has_prev' => $current_page > 1
    ];
}

function handleFileUpload($file, $upload_path, $allowed_extensions = ALLOWED_EXTENSIONS) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $filename = uniqid() . '.' . $file_extension;
    $upload_path = rtrim($upload_path, '/') . '/';
    
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}
?>
