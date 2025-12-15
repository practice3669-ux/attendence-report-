<?php
/**
 * Add/Edit Employee Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();
$employee = null;
$isEdit = false;

// Get employee if editing
if (isset($_GET['id'])) {
    $isEdit = true;
    $employeeId = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Location: list.php');
        exit();
    }
}

// Get departments
$stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

// Get salary structure if editing
$salaryStructure = null;
if ($isEdit && $employee) {
    $stmt = $db->prepare("SELECT * FROM salary_structures WHERE employee_id = ? ORDER BY effective_from DESC LIMIT 1");
    $stmt->execute([$employee['id']]);
    $salaryStructure = $stmt->fetch();
}

$pageTitle = $isEdit ? 'Edit Employee' : 'Add Employee';
include '../includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit Employee' : 'Add New Employee'; ?></h1>
    <a href="list.php" class="btn btn-outline">Back to List</a>
</div>

<div class="dashboard-card">
    <div class="card-body">
        <form id="employeeForm" method="POST" action="../api/employees.php" enctype="multipart/form-data" data-autosave>
            <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'create'; ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
            <?php endif; ?>

            <h3 style="margin-bottom: 20px;">Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="employee_code">Employee Code *</label>
                    <input type="text" id="employee_code" name="employee_code" 
                           value="<?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?>" 
                           required <?php echo $isEdit ? 'readonly' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="department_id">Department *</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo ($employee && $employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="designation">Designation *</label>
                    <input type="text" id="designation" name="designation" 
                           value="<?php echo htmlspecialchars($employee['designation'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="join_date">Join Date *</label>
                    <input type="date" id="join_date" name="join_date" 
                           value="<?php echo $employee['join_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="photo">Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    <div id="photoPreview" style="margin-top: 10px;">
                        <?php if ($isEdit && $employee['photo']): ?>
                            <img src="../uploads/employees/<?php echo htmlspecialchars($employee['photo']); ?>" 
                                 alt="Current Photo" style="max-width: 200px; border-radius: 8px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Address Information</h3>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" 
                           value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" 
                           value="<?php echo htmlspecialchars($employee['state'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" 
                           value="<?php echo htmlspecialchars($employee['pincode'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Bank Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name" 
                           value="<?php echo htmlspecialchars($employee['bank_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="bank_account_number">Account Number</label>
                    <input type="text" id="bank_account_number" name="bank_account_number" 
                           value="<?php echo htmlspecialchars($employee['bank_account_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="bank_ifsc">IFSC Code</label>
                    <input type="text" id="bank_ifsc" name="bank_ifsc" 
                           value="<?php echo htmlspecialchars($employee['bank_ifsc'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 30px 0 20px;">Salary Structure</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="basic_salary">Basic Salary *</label>
                    <input type="number" id="basic_salary" name="basic_salary" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['basic_salary'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="hra">HRA</label>
                    <input type="number" id="hra" name="hra" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['hra'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="da">DA</label>
                    <input type="number" id="da" name="da" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['da'] ?? ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ta">TA</label>
                    <input type="number" id="ta" name="ta" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['ta'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="medical_allowance">Medical Allowance</label>
                    <input type="number" id="medical_allowance" name="medical_allowance" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['medical_allowance'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="special_allowance">Special Allowance</label>
                    <input type="number" id="special_allowance" name="special_allowance" step="0.01" min="0" 
                           value="<?php echo $salaryStructure['special_allowance'] ?? ''; ?>">
                </div>
            </div>

            <div id="salaryPreview" style="margin-top: 20px;"></div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update Employee' : 'Add Employee'; ?></button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/employees.js"></script>

<?php include '../includes/footer.php'; ?>

