<?php
// Simple admin setup script
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/models/BaseModel.php';
require_once __DIR__ . '/app/models/Admin.php';

echo "Setting up admin user...\n";

try {
    $admin = new Admin();
    
    // Check if admin already exists
    $existing = $admin->findOneBy('username', 'admin');
    if ($existing) {
        echo "Admin user already exists.\n";
    } else {
        // Create default admin user
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@parrotvisa.com',
            'password' => 'admin123',
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'is_active' => 1
        ];
        
        $result = $admin->createAdmin($adminData);
        
        if ($result['success']) {
            echo "Admin user created successfully!\n";
            echo "Username: admin\n";
            echo "Password: admin123\n";
        } else {
            echo "Failed to create admin user:\n";
            print_r($result['errors']);
        }
    }
    
    // Test database connection
    echo "\nTesting database connection...\n";
    $test = $admin->conn->query("SELECT 1")->fetch();
    if ($test) {
        echo "Database connection: OK\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nSetup complete!\n";
?>
