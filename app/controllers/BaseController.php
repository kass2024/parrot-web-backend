<?php

require_once __DIR__ . '/../config/config.php';

class BaseController {
    protected $layout = 'default';
    protected $data = [];
    protected $title = SITE_NAME;

    public function __construct() {
        $this->data['site_name'] = SITE_NAME;
        $this->data['base_url'] = SITE_URL;
        $this->data['asset_url'] = function($path) {
            return assetUrl($path);
        };
        $this->data['upload_url'] = function($path) {
            return uploadUrl($path);
        };
        $this->data['current_user'] = $this->getCurrentUser();
    }

    protected function view($view, $data = []) {
        $this->data = array_merge($this->data, $data);
        $this->data['title'] = $this->title;
        $this->data['content'] = $this->renderView($view, $this->data);
        
        echo $this->renderLayout($this->layout, $this->data);
    }

    protected function renderView($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        return ob_get_clean();
    }

    protected function renderLayout($layout, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/layouts/{$layout}.php";
        return ob_get_clean();
    }

    protected function json($data, $status_code = 200) {
        header('Content-Type: application/json');
        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }

    protected function redirect($path) {
        redirect($path);
    }

    protected function setTitle($title) {
        $this->title = $title . ' - ' . SITE_NAME;
    }

    protected function setLayout($layout) {
        $this->layout = $layout;
    }

    protected function getCurrentUser() {
        if (isLoggedIn()) {
            $admin_model = new Admin();
            return $admin_model->findById($_SESSION['admin_id']);
        }
        return null;
    }

    protected function validateCSRF() {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }

    protected function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = generateToken();
        }
        return $_SESSION['csrf_token'];
    }

    protected function validatePost($required_fields = []) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                $errors[$field] = "The {$field} field is required.";
            }
        }
        
        return $errors;
    }

    protected function sanitizePost($fields = []) {
        $data = [];
        
        if (empty($fields)) {
            foreach ($_POST as $key => $value) {
                $data[$key] = sanitize($value);
            }
        } else {
            foreach ($fields as $field) {
                $data[$field] = isset($_POST[$field]) ? sanitize($_POST[$field]) : '';
            }
        }
        
        return $data;
    }

    protected function handleFileUpload($field_name, $upload_path, $allowed_extensions = ALLOWED_EXTENSIONS) {
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error'];
        }

        return handleFileUpload($_FILES[$field_name], $upload_path, $allowed_extensions);
    }

    protected function setFlashMessage($type, $message) {
        flashMessage($type, $message);
    }

    protected function getFlashMessage($type) {
        return getFlashMessage($type);
    }

    protected function paginate($total_items, $current_page = 1, $items_per_page = ITEMS_PER_PAGE) {
        return paginate($total_items, $current_page, $items_per_page);
    }

    protected function validateAjax() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }
    }

    protected function requireLogin() {
        requireLogin();
    }

    protected function requireRole($role) {
        if (!isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'Authentication required'], 401);
        }
        
        $user = $this->getCurrentUser();
        if (!$user || $user['role'] !== $role) {
            $this->json(['success' => false, 'message' => 'Insufficient permissions'], 403);
        }
    }

    protected function logActivity($action, $details = '') {
        $user = $this->getCurrentUser();
        $log_entry = [
            'user_id' => $user['id'] ?? 0,
            'username' => $user['username'] ?? 'Unknown',
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log to file (you could also log to database)
        $log_file = UPLOAD_PATH . 'logs/activity.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_line = json_encode($log_entry) . PHP_EOL;
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }

    protected function sendEmail($to, $subject, $message, $from = ADMIN_EMAIL) {
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $from,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    protected function formatFileSize($bytes) {
        return formatBytes($bytes);
    }

    protected function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }

    protected function truncateText($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }

    protected function slugify($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    protected function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function getInput($key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function getAllInput() {
        return array_merge($_GET, $_POST);
    }
}
?>
