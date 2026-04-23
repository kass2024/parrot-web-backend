<?php

require_once __DIR__ . '/BaseModel.php';

class Admin extends BaseModel {
    protected $table = 'admin_users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'email', 'password', 'full_name', 'role', 'is_active'
    ];
    protected $hidden = ['password'];

    public function login($username, $password) {
        $sql = "SELECT * FROM {$this->table} WHERE (username = :username OR email = :username) AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Update last login
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }

        return false;
    }

    public function createAdmin($data) {
        $validation_rules = [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'full_name' => 'max:100',
            'role' => 'required'
        ];

        $errors = $this->validate($data, $validation_rules);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if username or email already exists
        if ($this->findOneBy('username', $data['username'])) {
            return ['success' => false, 'errors' => ['username' => ['Username already exists']]];
        }

        if ($this->findOneBy('email', $data['email'])) {
            return ['success' => false, 'errors' => ['email' => ['Email already exists']]];
        }

        $admin_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => hashPassword($data['password']),
            'full_name' => $data['full_name'] ?? '',
            'role' => $data['role'],
            'is_active' => 1
        ];

        $admin_id = $this->create($admin_data);
        
        if ($admin_id) {
            return ['success' => true, 'admin_id' => $admin_id];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to create admin user']]];
    }

    public function updateAdmin($id, $data) {
        $admin = $this->findById($id);
        if (!$admin) {
            return ['success' => false, 'errors' => ['general' => ['Admin user not found']]];
        }

        $validation_rules = [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'full_name' => 'max:100',
            'role' => 'required'
        ];

        // Add password validation if password is being updated
        if (!empty($data['password'])) {
            $validation_rules['password'] = 'min:6';
        }

        $errors = $this->validate($data, $validation_rules);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if username or email already exists (excluding current user)
        $existing_user = $this->findOneBy('username', $data['username']);
        if ($existing_user && $existing_user['id'] != $id) {
            return ['success' => false, 'errors' => ['username' => ['Username already exists']]];
        }

        $existing_email = $this->findOneBy('email', $data['email']);
        if ($existing_email && $existing_email['id'] != $id) {
            return ['success' => false, 'errors' => ['email' => ['Email already exists']]];
        }

        $admin_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'] ?? '',
            'role' => $data['role']
        ];

        // Update password if provided
        if (!empty($data['password'])) {
            $admin_data['password'] = hashPassword($data['password']);
        }

        if ($this->update($id, $admin_data)) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update admin user']]];
    }

    public function changePassword($id, $current_password, $new_password) {
        $admin = $this->findById($id);
        if (!$admin) {
            return ['success' => false, 'errors' => ['general' => ['Admin user not found']]];
        }

        if (!verifyPassword($current_password, $admin['password'])) {
            return ['success' => false, 'errors' => ['current_password' => ['Current password is incorrect']]];
        }

        if (strlen($new_password) < 6) {
            return ['success' => false, 'errors' => ['new_password' => ['New password must be at least 6 characters']]];
        }

        if ($this->update($id, ['password' => hashPassword($new_password)])) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update password']]];
    }

    public function toggleStatus($id) {
        $admin = $this->findById($id);
        if (!$admin) {
            return ['success' => false, 'errors' => ['general' => ['Admin user not found']]];
        }

        $new_status = $admin['is_active'] ? 0 : 1;
        
        if ($this->update($id, ['is_active' => $new_status])) {
            return ['success' => true, 'status' => $new_status];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update status']]];
    }

    public function getAdminStats() {
        $sql = "SELECT 
                    COUNT(*) as total_admins,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_admins,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as super_admins,
                    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_logins
                FROM {$this->table}";
        
        return $this->queryOne($sql);
    }
}
?>
