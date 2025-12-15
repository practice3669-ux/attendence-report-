<?php
/**
 * Quick Fix - One-Click Password Reset
 * This will immediately fix the admin password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    
    $db = getDB();
    
    // Generate correct password hash for default admin password
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Update or create admin user
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update existing
        $stmt = $db->prepare("UPDATE users SET password = ?, status = 'active' WHERE username = 'admin'");
        $stmt->execute([$hashedPassword]);
        $action = "updated";
    } else {
        // Create new
        $stmt = $db->prepare("
            INSERT INTO users (username, password, role, email, full_name, status) 
            VALUES ('admin', ?, 'admin', 'admin@company.com', 'System Administrator', 'active')
        ");
        $stmt->execute([$hashedPassword]);
        $action = "created";
    }
    
    // Verify it works
    $stmt = $db->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $updated = $stmt->fetch();
    $verified = password_verify($password, $updated['password']);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Fixed!</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 100px auto;
                padding: 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
            }
            .success {
                color: #27ae60;
                font-size: 48px;
                margin-bottom: 20px;
            }
            h1 {
                color: #2c3e50;
                margin-bottom: 20px;
            }
            .credentials {
                background: #ecf0f1;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .credentials strong {
                color: #2c3e50;
                font-size: 18px;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
                font-size: 16px;
            }
            .btn:hover {
                background: #2980b9;
            }
            .info {
                color: #7f8c8d;
                margin-top: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="success">✓</div>
            <h1>Password Fixed Successfully!</h1>
            <p>The admin user has been <?php echo $action; ?> and the password has been set.</p>
            
            <div class="credentials">
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
            
            <?php if ($verified): ?>
                <p style="color: #27ae60; font-weight: bold;">✓ Password verification successful!</p>
            <?php else: ?>
                <p style="color: #e74c3c;">✗ Password verification failed - please run verify_password.php</p>
            <?php endif; ?>
            
            <a href="index.php" class="btn">Go to Login Page</a>
            
            <p class="info">⚠️ Delete this file (quick_fix.php) after logging in for security!</p>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 100px auto;
                padding: 30px;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 20px;
                border-radius: 5px;
                border-left: 4px solid #dc3545;
            }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p>Please check your database configuration in includes/config.php</p>
        </div>
    </body>
    </html>
    <?php
}

