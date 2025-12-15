<?php
/**
 * User Profile Page
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$userId = getCurrentUserId();

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = sanitizeDB($_POST['full_name'] ?? '');
        $email = sanitizeDB($_POST['email'] ?? '');
        
        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $userId]);
            
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            
            $message = 'Profile updated successfully';
            $user['full_name'] = $fullName;
            $user['email'] = $email;
            
        } catch (Exception $e) {
            $error = 'Failed to update profile';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            try {
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                logActivity('change_password', 'users', $userId);
                $message = 'Password changed successfully';
                
            } catch (Exception $e) {
                $error = 'Failed to change password';
            }
        }
    }
}

$pageTitle = 'My Profile';
include 'includes/header.php';
?>

<div class="page-header">
    <h1>My Profile</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Profile Information -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Profile Information</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small style="color: var(--text-secondary);">Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Last Login</label>
                    <input type="text" value="<?php echo $user['last_login'] ? formatDate($user['last_login'], DISPLAY_DATETIME_FORMAT) : 'Never'; ?>" disabled>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Change Password</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" 
                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                    <small style="color: var(--text-secondary);">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

