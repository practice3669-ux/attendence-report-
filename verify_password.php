<?php
/**
 * Password Verification Script
 * This will check if the admin password is set correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Verify Admin Password</title>";
echo "<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        padding: 30px;
    }
    h1 {
        color: #2c3e50;
        margin-bottom: 10px;
        border-bottom: 3px solid #3498db;
        padding-bottom: 10px;
    }
    .test-section {
        margin: 20px 0;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        background: #f8f9fa;
    }
    .success {
        color: #155724;
        background: #d4edda;
        border-left-color: #28a745;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
    }
    .error {
        color: #721c24;
        background: #f8d7da;
        border-left-color: #dc3545;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
    }
    .info {
        color: #004085;
        background: #d1ecf1;
        border-left-color: #17a2b8;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
    }
    .warning {
        color: #856404;
        background: #fff3cd;
        border-left-color: #ffc107;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
    }
    pre {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        margin: 10px 0;
        font-size: 13px;
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 10px 10px 0;
        transition: background 0.3s;
    }
    .btn:hover {
        background: #2980b9;
    }
    .btn-success {
        background: #27ae60;
    }
    .btn-success:hover {
        background: #229954;
    }
    .btn-danger {
        background: #e74c3c;
    }
    .btn-danger:hover {
        background: #c0392b;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    table th, table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
    }
    .status-icon {
        font-size: 20px;
        margin-right: 5px;
    }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔐 Admin Password Verification</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    
    $db = getDB();
    echo "<div class='success'><span class='status-icon'>✓</span> Database connection successful!</div>";
    
    // Test 1: Check if admin user exists
    echo "<div class='test-section'>";
    echo "<h2>Test 1: Check Admin User Exists</h2>";
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div class='success'><span class='status-icon'>✓</span> Admin user found in database!</div>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$admin['id']}</td></tr>";
        echo "<tr><td>Username</td><td><strong>{$admin['username']}</strong></td></tr>";
        echo "<tr><td>Role</td><td>{$admin['role']}</td></tr>";
        echo "<tr><td>Status</td><td><strong style='color:" . ($admin['status'] === 'active' ? 'green' : 'red') . ";'>{$admin['status']}</strong></td></tr>";
        echo "<tr><td>Email</td><td>" . ($admin['email'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Password Hash</td><td><pre style='font-size:11px;'>" . htmlspecialchars($admin['password']) . "</pre></td></tr>";
        echo "</table>";
        
        if ($admin['status'] !== 'active') {
            echo "<div class='error'><span class='status-icon'>✗</span> WARNING: User status is '{$admin['status']}' - should be 'active'!</div>";
        }
    } else {
        echo "<div class='error'><span class='status-icon'>✗</span> Admin user NOT FOUND in database!</div>";
        echo "<div class='info'>You need to create the admin user first.</div>";
    }
    echo "</div>";
    
    if ($admin) {
        // Test 2: Verify password 'admin123'
        echo "<div class='test-section'>";
        echo "<h2>Test 2: Verify Password 'Password123'</h2>";
        
        $testPassword = 'Password123';
        $passwordHash = $admin['password'];
        $verifyResult = password_verify($testPassword, $passwordHash);
        
        if ($verifyResult) {
            echo "<div class='success'><span class='status-icon'>✓</span> <strong>PASSWORD IS CORRECT!</strong></div>";
            echo "<div class='info'>";
            echo "<p><strong>Test Password:</strong> Password123</p>";
            echo "<p><strong>Result:</strong> Password verification SUCCESSFUL ✓</p>";
            echo "<p>You should be able to login with:</p>";
            echo "<ul style='margin-left:20px;margin-top:10px;'>";
            echo "<li>Username: <strong>admin</strong></li>";
            echo "<li>Password: <strong>admin123</strong></li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='error'><span class='status-icon'>✗</span> <strong>PASSWORD IS INCORRECT!</strong></div>";
            echo "<div class='warning'>";
            echo "<p><strong>Test Password:</strong> Password123</p>";
            echo "<p><strong>Result:</strong> Password verification FAILED ✗</p>";
            echo "<p>The password hash in the database does not match 'admin123'.</p>";
            echo "<p><strong>Solution:</strong> Run fix_admin.php to update the password.</p>";
            echo "</div>";
        }
        echo "</div>";
        
        // Test 3: Test actual login function
        echo "<div class='test-section'>";
        echo "<h2>Test 3: Test Login Function</h2>";
        
        require_once 'includes/auth.php';
        
        $loginResult = login('admin', 'admin123');
        
        if ($loginResult) {
            echo "<div class='success'><span class='status-icon'>✓</span> Login function works correctly!</div>";
            echo "<div class='info'>The login() function successfully authenticated the credentials.</div>";
        } else {
            echo "<div class='error'><span class='status-icon'>✗</span> Login function FAILED!</div>";
            echo "<div class='warning'>The login() function could not authenticate with admin/admin123.</div>";
        }
        echo "</div>";
        
        // Test 4: Check password hash format
        echo "<div class='test-section'>";
        echo "<h2>Test 4: Password Hash Format Check</h2>";
        
        $hashLength = strlen($passwordHash);
        $hashPrefix = substr($passwordHash, 0, 4);
        
        echo "<table>";
        echo "<tr><th>Check</th><th>Result</th></tr>";
        echo "<tr><td>Hash Length</td><td>{$hashLength} characters " . ($hashLength === 60 ? "✓ (Correct)" : "✗ (Should be 60)") . "</td></tr>";
        echo "<tr><td>Hash Prefix</td><td>{$hashPrefix} " . ($hashPrefix === '$2y$' || $hashPrefix === '$2a$' ? "✓ (BCrypt format)" : "✗ (Invalid format)") . "</td></tr>";
        echo "<tr><td>Hash Format</td><td>" . (preg_match('/^\$2[ay]\$\d{2}\$/', $passwordHash) ? "✓ Valid BCrypt" : "✗ Invalid format") . "</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Test 5: Generate new hash for comparison
        echo "<div class='test-section'>";
        echo "<h2>Test 5: Generate Fresh Password Hash</h2>";
        
        $newHash = password_hash('Password123', PASSWORD_BCRYPT);
        $newHashVerify = password_verify('Password123', $newHash);
        
        echo "<div class='info'>";
        echo "<p><strong>New Generated Hash for 'Password123':</strong></p>";
        echo "<pre>" . htmlspecialchars($newHash) . "</pre>";
        echo "<p>Verification of new hash: " . ($newHashVerify ? "<strong style='color:green;'>✓ SUCCESS</strong>" : "<strong style='color:red;'>✗ FAILED</strong>") . "</p>";
        echo "</div>";
        
        if (!$verifyResult) {
            echo "<div class='warning'>";
            echo "<p><strong>To fix the password, run this SQL:</strong></p>";
            echo "<pre>UPDATE users SET password = '" . htmlspecialchars($newHash) . "' WHERE username = 'admin';</pre>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Summary
    echo "<div class='test-section' style='background:#e8f5e9;border-left-color:#4caf50;'>";
    echo "<h2>📋 Summary</h2>";
    
    if ($admin) {
        $allTests = [
            'User Exists' => true,
            'User Status Active' => ($admin['status'] === 'active'),
            'Password Correct' => (isset($verifyResult) && $verifyResult),
            'Login Function Works' => (isset($loginResult) && $loginResult)
        ];
        
        echo "<table>";
        echo "<tr><th>Test</th><th>Status</th></tr>";
        foreach ($allTests as $test => $passed) {
            $icon = $passed ? '✓' : '✗';
            $color = $passed ? 'green' : 'red';
            echo "<tr><td>{$test}</td><td style='color:{$color};font-weight:bold;'>{$icon} " . ($passed ? 'PASS' : 'FAIL') . "</td></tr>";
        }
        echo "</table>";
        
        if ($allTests['Password Correct'] && $allTests['User Status Active']) {
            echo "<div class='success' style='margin-top:15px;'>";
            echo "<strong>✓ All tests passed! You should be able to login successfully.</strong>";
            echo "</div>";
        } else {
            echo "<div class='error' style='margin-top:15px;'>";
            echo "<strong>✗ Some tests failed. Please run fix_admin.php to fix the issues.</strong>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>Admin user does not exist. Run fix_admin.php to create it.</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "<div class='info'><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "<div style='margin-top:30px;padding:20px;background:#fff3cd;border-radius:5px;border-left:4px solid #ffc107;'>";
echo "<strong>⚠️ Security Note:</strong> Delete this file (verify_password.php) after checking!";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='fix_admin.php' class='btn btn-success'>Fix Admin Password</a>";
echo "<a href='test_login.php' class='btn'>Test Login Details</a>";
echo "<a href='index.php' class='btn'>Go to Login Page</a>";
echo "</div>";

echo "</div></body></html>";

