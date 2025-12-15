<?php
/**
 * Setup Script - Fix Admin Password
 * Run this once to set the correct admin password
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

$db = getDB();

// Generate correct password hash for the default password 'admin123'
$password = 'admin123';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

echo "Generated password hash: " . $hashedPassword . "\n\n";

// Check if admin user exists
$stmt = $db->prepare("SELECT id, username FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    // Update existing admin password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    echo "✓ Admin password updated successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
} else {
    // Create admin user if it doesn't exist
    $stmt = $db->prepare("
        INSERT INTO users (username, password, role, email, full_name, status) 
        VALUES ('admin', ?, 'admin', 'admin@company.com', 'System Administrator', 'active')
    ");
    $stmt->execute([$hashedPassword]);
    echo "✓ Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
}

echo "\nYou can now login with:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "\n⚠️  IMPORTANT: Delete this file (setup_admin.php) after running it for security!\n";

