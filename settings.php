<?php
/**
 * Settings Page (Admin Only)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireRole('admin');

$db = getDB();

// Get current settings
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$settings = $stmt->fetch();

if (!$settings) {
    // Create default settings
    $stmt = $db->prepare("INSERT INTO company_settings (company_name) VALUES (?)");
    $stmt->execute([APP_NAME]);
    $stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
    $settings = $stmt->fetch();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'company_name' => sanitizeDB($_POST['company_name'] ?? ''),
        'address' => sanitizeDB($_POST['address'] ?? ''),
        'city' => sanitizeDB($_POST['city'] ?? ''),
        'state' => sanitizeDB($_POST['state'] ?? ''),
        'pincode' => sanitizeDB($_POST['pincode'] ?? ''),
        'phone' => sanitizeDB($_POST['phone'] ?? ''),
        'email' => sanitizeDB($_POST['email'] ?? ''),
        'website' => sanitizeDB($_POST['website'] ?? ''),
        'pan_number' => sanitizeDB($_POST['pan_number'] ?? ''),
        'gst_number' => sanitizeDB($_POST['gst_number'] ?? ''),
        'tan_number' => sanitizeDB($_POST['tan_number'] ?? ''),
        'salary_payment_day' => intval($_POST['salary_payment_day'] ?? 1),
    ];
    
    try {
        $stmt = $db->prepare("
            UPDATE company_settings SET
                company_name = ?, address = ?, city = ?, state = ?, pincode = ?,
                phone = ?, email = ?, website = ?, pan_number = ?, gst_number = ?,
                tan_number = ?, salary_payment_day = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['company_name'], $data['address'], $data['city'], $data['state'],
            $data['pincode'], $data['phone'], $data['email'], $data['website'],
            $data['pan_number'], $data['gst_number'], $data['tan_number'],
            $data['salary_payment_day'], $settings['id']
        ]);
        
        logActivity('update', 'company_settings', $settings['id']);
        $message = 'Settings updated successfully';
        $settings = array_merge($settings, $data);
        
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        $error = 'Failed to update settings';
    }
}

$pageTitle = 'Settings';
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Company Settings</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="dashboard-card">
    <div class="card-header">
        <h2>Company Information</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <h3 style="margin-bottom: 20px;">Basic Details</h3>
            <div class="form-group">
                <label for="company_name">Company Name *</label>
                <input type="text" id="company_name" name="company_name" 
                       value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" 
                           value="<?php echo htmlspecialchars($settings['city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" 
                           value="<?php echo htmlspecialchars($settings['state'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" 
                           value="<?php echo htmlspecialchars($settings['pincode'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" 
                           value="<?php echo htmlspecialchars($settings['website'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Tax Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="pan_number">PAN Number</label>
                    <input type="text" id="pan_number" name="pan_number" 
                           value="<?php echo htmlspecialchars($settings['pan_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="gst_number">GST Number</label>
                    <input type="text" id="gst_number" name="gst_number" 
                           value="<?php echo htmlspecialchars($settings['gst_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="tan_number">TAN Number</label>
                    <input type="text" id="tan_number" name="tan_number" 
                           value="<?php echo htmlspecialchars($settings['tan_number'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Salary Settings</h3>
            <div class="form-group">
                <label for="salary_payment_day">Salary Payment Day (1-31) *</label>
                <input type="number" id="salary_payment_day" name="salary_payment_day" 
                       min="1" max="31" 
                       value="<?php echo $settings['salary_payment_day'] ?? 1; ?>" required>
                <small style="color: var(--text-secondary);">Day of the month when salaries are paid</small>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

