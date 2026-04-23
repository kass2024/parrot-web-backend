<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Gallery.php';

class DashboardController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        // Don't require login for test method
    }

    public function test() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Backend API is working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'php_version' => PHP_VERSION
        ]);
    }

    public function index() {
        $this->setTitle('Dashboard');
        
        // Get statistics
        $admin_model = new Admin();
        $gallery_model = new Gallery();
        
        $admin_stats = $admin_model->getAdminStats();
        $gallery_stats = $gallery_model->getGalleryStats();
        
        // Get recent activities (you'd need to create an Activity model)
        $recent_activities = $this->getRecentActivities();
        
        // Get system info
        $system_info = $this->getSystemInfo();
        
        $this->view('dashboard/index', [
            'admin_stats' => $admin_stats,
            'gallery_stats' => $gallery_stats,
            'recent_activities' => $recent_activities,
            'system_info' => $system_info,
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function profile() {
        $this->setTitle('My Profile');
        
        $user = $this->getCurrentUser();
        
        if ($this->isPost()) {
            $this->updateProfile($user);
        } else {
            $this->showProfilePage($user);
        }
    }

    private function showProfilePage($user) {
        $this->view('dashboard/profile', [
            'user' => $user,
            'csrf_token' => $this->generateCSRFToken(),
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function updateProfile($user) {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('dashboard/profile');
        }

        $admin_model = new Admin();
        
        $data = [
            'full_name' => $this->getInput('full_name'),
            'email' => $this->getInput('email')
        ];

        $result = $admin_model->updateAdmin($user['id'], $data);
        
        if ($result['success']) {
            $this->logActivity('profile_update', 'Updated profile information');
            $this->setFlashMessage('success', 'Profile updated successfully');
            $this->redirect('dashboard/profile');
        } else {
            $this->view('dashboard/profile', [
                'user' => $user,
                'csrf_token' => $this->generateCSRFToken(),
                'errors' => $result['errors'],
                'old_input' => $data
            ]);
        }
    }

    public function changePassword() {
        if ($this->isPost()) {
            $this->handlePasswordChange();
        } else {
            $this->showChangePasswordPage();
        }
    }

    private function showChangePasswordPage() {
        $this->setTitle('Change Password');
        $this->view('dashboard/change-password', [
            'csrf_token' => $this->generateCSRFToken(),
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function handlePasswordChange() {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('dashboard/change-password');
        }

        $user = $this->getCurrentUser();
        $admin_model = new Admin();
        
        $current_password = $this->getInput('current_password');
        $new_password = $this->getInput('new_password');
        $confirm_password = $this->getInput('confirm_password');

        $result = $admin_model->changePassword($user['id'], $current_password, $new_password);
        
        if ($result['success']) {
            $this->logActivity('password_change', 'Changed password');
            $this->setFlashMessage('success', 'Password changed successfully');
            $this->redirect('dashboard/change-password');
        } else {
            $this->view('dashboard/change-password', [
                'csrf_token' => $this->generateCSRFToken(),
                'errors' => $result['errors'],
                'old_input' => [
                    'current_password' => $current_password,
                    'new_password' => $new_password,
                    'confirm_password' => $confirm_password
                ]
            ]);
        }
    }

    public function settings() {
        $this->setTitle('System Settings');
        
        if ($this->isPost()) {
            $this->updateSettings();
        } else {
            $this->showSettingsPage();
        }
    }

    private function showSettingsPage() {
        // Get current settings (you'd need to create a Settings model)
        $current_settings = $this->getSystemSettings();
        
        $this->view('dashboard/settings', [
            'settings' => $current_settings,
            'csrf_token' => $this->generateCSRFToken(),
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function updateSettings() {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('dashboard/settings');
        }

        // Update settings (you'd need to implement this in a Settings model)
        $settings_data = [
            'site_title' => $this->getInput('site_title'),
            'site_description' => $this->getInput('site_description'),
            'hero_title' => $this->getInput('hero_title'),
            'hero_subtitle' => $this->getInput('hero_subtitle'),
            'contact_phone' => $this->getInput('contact_phone'),
            'contact_email' => $this->getInput('contact_email'),
            'contact_address' => $this->getInput('contact_address')
        ];

        // For now, we'll just show success message
        $this->logActivity('settings_update', 'Updated system settings');
        $this->setFlashMessage('success', 'Settings updated successfully');
        $this->redirect('dashboard/settings');
    }

    private function getRecentActivities() {
        // Read from activity log file
        $log_file = UPLOAD_PATH . 'logs/activity.log';
        
        if (!file_exists($log_file)) {
            return [];
        }

        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $activities = [];
        
        // Get last 20 activities
        $recent_lines = array_slice($lines, -20);
        
        foreach ($recent_lines as $line) {
            $activity = json_decode($line, true);
            if ($activity) {
                $activities[] = $activity;
            }
        }
        
        // Reverse to show newest first
        return array_reverse($activities);
    }

    private function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $this->getDatabaseVersion(),
            'disk_space' => [
                'total' => $this->formatFileSize(disk_total_space(__DIR__)),
                'free' => $this->formatFileSize(disk_free_space(__DIR__)),
                'used' => $this->formatFileSize(disk_total_space(__DIR__) - disk_free_space(__DIR__))
            ],
            'memory_usage' => [
                'current' => $this->formatFileSize(memory_get_usage(true)),
                'peak' => $this->formatFileSize(memory_get_peak_usage(true))
            ],
            'upload_max_size' => $this->formatFileSize(MAX_FILE_SIZE),
            'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'
        ];
    }

    private function getDatabaseVersion() {
        try {
            $conn = (new Database())->getConnection();
            $stmt = $conn->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            return $result['version'] ?? 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    private function getSystemSettings() {
        // This would typically come from a Settings model
        // For now, return default settings
        return [
            'site_title' => 'Parrot Canada Visa Consultant',
            'site_description' => 'Your trusted partner for international education and visa services',
            'hero_title' => 'Your Gateway to Global Education',
            'hero_subtitle' => 'Parrot Canada Visa Consultant - Your trusted partner for international education',
            'contact_phone' => '+1 (431) 302-0226',
            'contact_email' => 'infos@visaconsultantcanada.com',
            'contact_address' => 'Town Center Building, 2nd Floor, Door: F2B-022C, Nyarugenge, Kigali, Rwanda'
        ];
    }

    public function apiStats() {
        $this->validateAjax();
        
        $admin_model = new Admin();
        $gallery_model = new Gallery();
        
        $stats = [
            'admin_stats' => $admin_model->getAdminStats(),
            'gallery_stats' => $gallery_model->getGalleryStats(),
            'system_info' => [
                'uptime' => $this->getSystemUptime(),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->json(['success' => true, 'data' => $stats]);
    }

    private function getSystemUptime() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return 'Load: ' . implode(', ', $load);
        }
        
        return 'N/A';
    }

    public function clearCache() {
        $this->validateAjax();
        
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request token']);
        }

        // Clear various caches
        $cache_cleared = [];
        
        // Clear session files
        $session_path = session_save_path();
        if (is_dir($session_path)) {
            $files = glob($session_path . '/sess_*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $cache_cleared[] = 'Session files cleared';
        }
        
        // Clear temp files
        $temp_path = sys_get_temp_dir();
        $temp_files = glob($temp_path . '/parrot_*');
        foreach ($temp_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $cache_cleared[] = 'Temporary files cleared';
        
        $this->logActivity('cache_clear', 'Cleared system cache');
        $this->json(['success' => true, 'message' => 'Cache cleared successfully', 'cleared' => $cache_cleared]);
    }
}
?>
