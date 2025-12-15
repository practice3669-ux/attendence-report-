<?php
/**
 * Test Login - Debug Script
 * This will help diagnose login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Login</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#004085;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>Login Test & Debug</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    
    $db = getDB();
    echo "<div class='success'>✓ Database connection successful!</div>";
    
    // Check admin user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div class='info'><h3>Admin User Found:</h3>";
        echo "<pre>";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Password Hash: " . $admin['password'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "Email: " . ($admin['email'] ?? 'N/A') . "\n";
        echo "</pre></div>";
        
        // Test password verification
        $testPassword = 'Password123';
        $verify = password_verify($testPassword, $admin['password']);
        
        if ($verify) {
            echo "<div class='success'>✓ Password 'admin123' VERIFIES CORRECTLY!</div>";
        } else {
            echo "<div class='error'>✗ Password 'admin123' DOES NOT VERIFY!</div>";
            echo "<div class='info'>The password hash in database doesn't match 'admin123'. Run fix_admin.php to fix it.</div>";
        }
        
        // Check status
        if ($admin['status'] !== 'active') {
            echo "<div class='error'>✗ User status is '{$admin['status']}' - should be 'active'!</div>";
        } else {
            echo "<div class='success'>✓ User status is 'active'</div>";
        }
        
    } else {
        echo "<div class='error'>✗ Admin user NOT FOUND in database!</div>";
        echo "<div class='info'>Run fix_admin.php to create the admin user.</div>";
    }
    
    // List all users
    $stmt = $db->query("SELECT id, username, role, status FROM users");
    $allUsers = $stmt->fetchAll();
    
    if (!empty($allUsers)) {
        echo "<div class='info'><h3>All Users in Database:</h3><pre>";
        foreach ($allUsers as $user) {
            echo "ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}, Status: {$user['status']}\n";
        }
        echo "</pre></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "<div style='margin-top:30px;'>";
echo "<a href='fix_admin.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-right:10px;'>Fix Admin Password</a>";
echo "<a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Login</a>";
echo "</div>";

echo "</body></html>";

