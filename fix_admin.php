<?php
/**
 * Fix Admin Password - Enhanced Version
 * This script will fix the admin password issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Fix Admin Password</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#004085;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>Fix Admin Password</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    
    $db = getDB();
    echo "<div class='success'>✓ Database connection successful!</div>";
    
    // Generate correct password hash for the support password 'Password123'
    $password = 'Password123';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    echo "<div class='info'><strong>Generated Password Hash:</strong><br><pre>" . htmlspecialchars($hashedPassword) . "</pre></div>";
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id, username, password, status FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div class='info'>Found existing admin user (ID: {$admin['id']}, Status: {$admin['status']})</div>";
        
        // Update existing admin password
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$hashedPassword]);
        
        // Also ensure status is active
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE username = 'admin'");
        $stmt->execute();
        
        echo "<div class='success'><strong>✓ Admin password updated successfully!</strong></div>";
        
        // Verify the password works
        $stmt = $db->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $updated = $stmt->fetch();
        
        if (password_verify($password, $updated['password'])) {
            echo "<div class='success'>✓ Password verification successful! The hash is correct.</div>";
        } else {
            echo "<div class='error'>✗ Password verification failed. Something went wrong.</div>";
        }
        
    } else {
        // Create admin user if it doesn't exist
        echo "<div class='info'>Admin user not found. Creating new admin user...</div>";
        
        $stmt = $db->prepare("
            INSERT INTO users (username, password, role, email, full_name, status) 
            VALUES ('admin', ?, 'admin', 'admin@company.com', 'System Administrator', 'active')
        ");
        $stmt->execute([$hashedPassword]);
        
        echo "<div class='success'><strong>✓ Admin user created successfully!</strong></div>";
    }
    
    echo "<div class='success' style='margin-top:20px;padding:15px;'>";
    echo "<h3>Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123</p>";
    echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Login Page</a></p>";
    echo "</div>";
    
    // Show SQL command for manual fix
    echo "<div class='info' style='margin-top:20px;'>";
    echo "<h3>If you prefer to fix manually via SQL:</h3>";
    echo "<pre>UPDATE users SET password = '" . htmlspecialchars($hashedPassword) . "' WHERE username = 'admin';</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
    
    // Provide manual SQL solution
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    echo "<div class='info' style='margin-top:20px;'>";
    echo "<h3>Manual SQL Fix:</h3>";
    echo "<p>Run this SQL command in phpMyAdmin or MySQL:</p>";
    echo "<pre>UPDATE users SET password = '" . htmlspecialchars($hashedPassword) . "' WHERE username = 'admin';</pre>";
    echo "<p>Or if the user doesn't exist:</p>";
    echo "<pre>INSERT INTO users (username, password, role, email, full_name, status) VALUES ('admin', '" . htmlspecialchars($hashedPassword) . "', 'admin', 'admin@company.com', 'System Administrator', 'active');</pre>";
    echo "</div>";
}

echo "<div style='margin-top:30px;padding:15px;background:#fff3cd;border:1px solid #ffc107;border-radius:5px;'>";
echo "<strong>⚠️ Security Note:</strong> Delete this file (fix_admin.php) after fixing the password!";
echo "</div>";

echo "</body></html>";

