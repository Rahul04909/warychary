<?php
/**
 * Admin User Setup Script
 * This script creates the admin_users table and inserts default admin credentials
 * Run this script once to set up the admin authentication system
 */

require_once 'config.php';

try {
    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    // Create admin_users table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            created_by INT NULL,
            profile_image VARCHAR(255) NULL,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            permissions JSON NULL
        )
    ";
    
    $pdo->exec($createTableSQL);
    echo "✅ Admin users table created successfully!\n";
    
    // Check if any admin users already exist
    $checkExisting = $pdo->query("SELECT COUNT(*) FROM admin_users");
    $existingCount = $checkExisting->fetchColumn();
    
    if ($existingCount > 0) {
        echo "⚠️  Admin users already exist in the database.\n";
        echo "Current admin count: " . $existingCount . "\n";
        
        // Display existing admins
        $existingAdmins = $pdo->query("SELECT id, username, full_name, email, role, status, created_at FROM admin_users ORDER BY created_at ASC");
        echo "\n📋 Existing Admin Users:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s %-15s %-25s %-25s %-12s %-10s\n", "ID", "Username", "Full Name", "Email", "Role", "Status");
        echo str_repeat("-", 80) . "\n";
        
        while ($admin = $existingAdmins->fetch(PDO::FETCH_ASSOC)) {
            printf("%-5d %-15s %-25s %-25s %-12s %-10s\n", 
                $admin['id'], 
                $admin['username'], 
                $admin['full_name'], 
                $admin['email'], 
                $admin['role'], 
                $admin['status']
            );
        }
        echo str_repeat("-", 80) . "\n";
        
        // Ask if user wants to add another admin
        echo "\nDo you want to add another admin user? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) !== 'y' && strtolower($response) !== 'yes') {
            echo "❌ Operation cancelled. No new admin user added.\n";
            exit();
        }
    }
    
    // Default admin credentials
    $defaultAdmins = [
        [
            'username' => 'admin',
            'password' => 'admin123', // This will be hashed
            'full_name' => 'System Administrator',
            'email' => 'admin@warycharycare.com',
            'role' => 'super_admin',
            'status' => 'active'
        ],
        [
            'username' => 'moderator',
            'password' => 'mod123', // This will be hashed
            'full_name' => 'Content Moderator',
            'email' => 'moderator@warycharycare.com',
            'role' => 'moderator',
            'status' => 'active'
        ]
    ];
    
    // Insert default admin users
    $insertSQL = "
        INSERT INTO admin_users (username, password, full_name, email, role, status, permissions) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($insertSQL);
    $insertedCount = 0;
    
    foreach ($defaultAdmins as $admin) {
        // Check if username already exists
        $checkUser = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $checkUser->execute([$admin['username'], $admin['email']]);
        
        if ($checkUser->fetch()) {
            echo "⚠️  User '{$admin['username']}' or email '{$admin['email']}' already exists. Skipping...\n";
            continue;
        }
        
        // Hash the password
        $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
        
        // Set permissions based on role
        $permissions = json_encode([
            'dashboard' => true,
            'users' => $admin['role'] === 'super_admin',
            'products' => true,
            'orders' => true,
            'reports' => $admin['role'] !== 'moderator',
            'settings' => $admin['role'] === 'super_admin',
            'admin_management' => $admin['role'] === 'super_admin'
        ]);
        
        try {
            $stmt->execute([
                $admin['username'],
                $hashedPassword,
                $admin['full_name'],
                $admin['email'],
                $admin['role'],
                $admin['status'],
                $permissions
            ]);
            
            $insertedCount++;
            echo "✅ Admin user '{$admin['username']}' created successfully!\n";
            echo "   📧 Email: {$admin['email']}\n";
            echo "   🔑 Password: {$admin['password']} (Please change after first login)\n";
            echo "   👤 Role: {$admin['role']}\n\n";
            
        } catch (PDOException $e) {
            echo "❌ Error creating user '{$admin['username']}': " . $e->getMessage() . "\n";
        }
    }
    
    if ($insertedCount > 0) {
        echo "🎉 Successfully created {$insertedCount} admin user(s)!\n\n";
        
        echo "📋 Login Instructions:\n";
        echo str_repeat("-", 50) . "\n";
        echo "1. Navigate to: http://localhost:8000/admin/login.php\n";
        echo "2. Use the credentials shown above to login\n";
        echo "3. Change default passwords after first login\n";
        echo "4. Configure additional settings in the dashboard\n\n";
        
        echo "🔐 Security Recommendations:\n";
        echo str_repeat("-", 50) . "\n";
        echo "• Change default passwords immediately\n";
        echo "• Enable two-factor authentication if available\n";
        echo "• Regularly review admin user permissions\n";
        echo "• Monitor login attempts and activities\n";
        echo "• Use strong, unique passwords\n\n";
        
        // Create a simple admin management interface
        echo "🛠️  Admin Management:\n";
        echo str_repeat("-", 50) . "\n";
        echo "To manage admin users programmatically, you can use:\n";
        echo "• Add user: INSERT INTO admin_users (...)\n";
        echo "• Update user: UPDATE admin_users SET ... WHERE id = ?\n";
        echo "• Deactivate user: UPDATE admin_users SET status = 'inactive' WHERE id = ?\n";
        echo "• Reset password: UPDATE admin_users SET password = ? WHERE id = ?\n\n";
        
    } else {
        echo "ℹ️  No new admin users were created.\n";
    }
    
    // Display final statistics
    $finalCount = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    $activeCount = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE status = 'active'")->fetchColumn();
    
    echo "📊 Final Statistics:\n";
    echo str_repeat("-", 30) . "\n";
    echo "Total admin users: {$finalCount}\n";
    echo "Active admin users: {$activeCount}\n";
    echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    echo "Table: admin_users\n\n";
    
    echo "✨ Admin setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Function to generate secure random password
function generateSecurePassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $charactersLength = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $password;
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to validate username
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}
?>