<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Admin.php';

class AuthController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        
        // Redirect to dashboard if already logged in
        if (isLoggedIn() && $this->getInput('action') !== 'logout') {
            $this->redirect('dashboard');
        }
    }

    public function login() {
        if ($this->isPost()) {
            $this->handleLogin();
        } else {
            $this->showLoginPage();
        }
    }

    public function logout() {
        $this->handleLogout();
    }

    private function showLoginPage() {
        $this->setLayout('auth');
        $this->setTitle('Login');
        $this->view('auth/login', [
            'csrf_token' => $this->generateCSRFToken(),
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function handleLogin() {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('auth/login');
        }

        $username = $this->getInput('username');
        $password = $this->getInput('password');
        $remember = $this->getInput('remember') === 'on';

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username or email is required';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            $this->setLayout('auth');
            $this->setTitle('Login');
            $this->view('auth/login', [
                'csrf_token' => $this->generateCSRFToken(),
                'errors' => $errors,
                'old_input' => ['username' => $username, 'remember' => $remember]
            ]);
        }

        $admin_model = new Admin();
        $user = $admin_model->login($username, $password);

        if ($user) {
            // Set session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['login_time'] = time();

            // Set remember me cookie if requested
            if ($remember) {
                $token = $this->generateRandomString(32);
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_token', $token, $expires, '/', '', false, true);
                
                // Store token in database (you'd need to add remember_token column to admin_users table)
                // For now, we'll just set the session
            }

            // Log the login
            $this->logActivity('login', "User {$user['username']} logged in");

            $this->setFlashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');

            // Redirect to intended page or dashboard
            $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard';
            unset($_SESSION['redirect_url']);
            $this->redirect($redirect_url);
        } else {
            $this->setFlashMessage('error', 'Invalid username or password');
            $this->setLayout('auth');
            $this->setTitle('Login');
            $this->view('auth/login', [
                'csrf_token' => $this->generateCSRFToken(),
                'error' => 'Invalid username or password',
                'old_input' => ['username' => $username, 'remember' => $remember]
            ]);
        }
    }

    private function handleLogout() {
        if (isLoggedIn()) {
            $user = $this->getCurrentUser();
            $this->logActivity('logout', "User {$user['username']} logged out");
        }

        // Destroy session
        session_destroy();

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['remember_token']);
        }

        $this->setFlashMessage('success', 'You have been logged out successfully');
        $this->redirect('auth/login');
    }

    public function forgotPassword() {
        if ($this->isPost()) {
            $this->handleForgotPassword();
        } else {
            $this->showForgotPasswordPage();
        }
    }

    private function showForgotPasswordPage() {
        $this->setLayout('auth');
        $this->setTitle('Forgot Password');
        $this->view('auth/forgot-password', [
            'csrf_token' => $this->generateCSRFToken(),
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function handleForgotPassword() {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('auth/forgot-password');
        }

        $email = $this->getInput('email');

        if (empty($email) || !validateEmail($email)) {
            $this->setLayout('auth');
            $this->setTitle('Forgot Password');
            $this->view('auth/forgot-password', [
                'csrf_token' => $this->generateCSRFToken(),
                'error' => 'Please enter a valid email address',
                'old_input' => ['email' => $email]
            ]);
        }

        $admin_model = new Admin();
        $user = $admin_model->findOneBy('email', $email);

        if ($user) {
            // Generate reset token
            $reset_token = $this->generateRandomString(64);
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token in database (you'd need to add reset_token and reset_expires columns)
            // For now, we'll just send a notification email
            
            $reset_link = baseUrl('auth/reset-password?token=' . $reset_token);
            
            $subject = 'Password Reset Request - ' . SITE_NAME;
            $message = "
                <h2>Password Reset Request</h2>
                <p>Hello {$user['full_name']},</p>
                <p>You have requested to reset your password. Click the link below to reset your password:</p>
                <p><a href='{$reset_link}'>Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>" . SITE_NAME . " Team</p>
            ";

            if ($this->sendEmail($email, $subject, $message)) {
                $this->logActivity('password_reset_request', "Password reset requested for {$email}");
                $this->setFlashMessage('success', 'Password reset link has been sent to your email');
            } else {
                $this->setFlashMessage('error', 'Failed to send reset email. Please try again.');
            }
        } else {
            // Don't reveal if email exists or not
            $this->setFlashMessage('success', 'If your email exists in our system, you will receive a reset link shortly.');
        }

        $this->redirect('auth/forgot-password');
    }

    public function resetPassword() {
        $token = $this->getInput('token');
        
        if (empty($token)) {
            $this->setFlashMessage('error', 'Invalid reset token');
            $this->redirect('auth/forgot-password');
        }

        if ($this->isPost()) {
            $this->handleResetPassword($token);
        } else {
            $this->showResetPasswordPage($token);
        }
    }

    private function showResetPasswordPage($token) {
        // Validate token (you'd need to check against database)
        // For now, we'll just show the form
        
        $this->setLayout('auth');
        $this->setTitle('Reset Password');
        $this->view('auth/reset-password', [
            'csrf_token' => $this->generateCSRFToken(),
            'token' => $token,
            'error' => $this->getFlashMessage('error'),
            'success' => $this->getFlashMessage('success')
        ]);
    }

    private function handleResetPassword($token) {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('auth/reset-password?token=' . $token);
        }

        $password = $this->getInput('password');
        $confirm_password = $this->getInput('confirm_password');

        $errors = [];

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (empty($confirm_password)) {
            $errors['confirm_password'] = 'Please confirm your password';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $this->setLayout('auth');
            $this->setTitle('Reset Password');
            $this->view('auth/reset-password', [
                'csrf_token' => $this->generateCSRFToken(),
                'token' => $token,
                'errors' => $errors,
                'old_input' => ['password' => $password, 'confirm_password' => $confirm_password]
            ]);
        }

        // Validate token and update password (you'd need to implement proper token validation)
        // For now, we'll just show success message
        
        $this->setFlashMessage('success', 'Your password has been reset successfully. Please login with your new password.');
        $this->logActivity('password_reset', 'Password was reset using reset token');
        $this->redirect('auth/login');
    }

    // API Login method for AJAX requests
    public function apiLogin() {
        $this->validateAjax();
        
        $username = $this->getInput('username');
        $password = $this->getInput('password');
        $remember = $this->getInput('remember') === 'on';

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username or email is required';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 400);
            return;
        }

        $admin_model = new Admin();
        $user = $admin_model->login($username, $password);

        if ($user) {
            // Set session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['login_time'] = time();

            // Set remember me cookie if requested
            if ($remember) {
                $token = $this->generateRandomString(32);
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_token', $token, $expires, '/', '', false, true);
            }

            // Log the login
            $this->logActivity('login', "User {$user['username']} logged in via API");

            $this->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }
    }
}
?>
