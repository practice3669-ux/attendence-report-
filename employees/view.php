<?php
/**
 * View Employee Details
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: list.php');
    exit();
}

$stmt = $db->prepare("
    SELECT e.*, d.name as department_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: list.php');
    exit();
}

// Get salary structure
$stmt = $db->prepare("SELECT * FROM salary_structures WHERE employee_id = ? ORDER BY effective_from DESC LIMIT 1");
$stmt->execute([$id]);
$salaryStructure = $stmt->fetch();

// Get salary history
$stmt = $db->prepare("
    SELECT * FROM salary_transactions
    WHERE employee_id = ?
    ORDER BY year DESC, month DESC
    LIMIT 12
");
$stmt->execute([$id]);
$salaryHistory = $stmt->fetchAll();

$pageTitle = 'Employee Details';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Employee Details</h1>
    <div style="display: flex; gap: 10px;">
        <a href="add.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary">Edit</a>
        <a href="list.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Employee Information -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Personal Information</h2>
        </div>
        <div class="card-body">
            <?php if ($employee['photo']): ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="../uploads/employees/<?php echo htmlspecialchars($employee['photo']); ?>" 
                         alt="Photo" style="max-width: 200px; border-radius: 8px;">
                </div>
            <?php endif; ?>
            
            <table class="table">
                <tr>
                    <th width="40%">Employee Code</th>
                    <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td><?php echo htmlspecialchars($employee['phone'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td><?php echo htmlspecialchars($employee['designation'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Join Date</th>
                    <td><?php echo formatDate($employee['join_date']); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge badge-<?php echo $employee['status']; ?>">
                            <?php echo ucfirst($employee['status']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Salary Structure -->
    <?php if ($salaryStructure): ?>
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Current Salary Structure</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <th width="40%">Basic Salary</th>
                    <td><?php echo formatCurrency($salaryStructure['basic_salary']); ?></td>
                </tr>
                <tr>
                    <th>HRA</th>
                    <td><?php echo formatCurrency($salaryStructure['hra']); ?></td>
                </tr>
                <tr>
                    <th>DA</th>
                    <td><?php echo formatCurrency($salaryStructure['da']); ?></td>
                </tr>
                <tr>
                    <th>TA</th>
                    <td><?php echo formatCurrency($salaryStructure['ta']); ?></td>
                </tr>
                <tr>
                    <th>Medical Allowance</th>
                    <td><?php echo formatCurrency($salaryStructure['medical_allowance']); ?></td>
                </tr>
                <tr>
                    <th>Special Allowance</th>
                    <td><?php echo formatCurrency($salaryStructure['special_allowance']); ?></td>
                </tr>
                <tr>
                    <th>Total Earnings</th>
                    <td><strong><?php 
                        $earnings = $salaryStructure['basic_salary'] + $salaryStructure['hra'] + 
                                   $salaryStructure['da'] + $salaryStructure['ta'] + 
                                   $salaryStructure['medical_allowance'] + $salaryStructure['special_allowance'];
                        echo formatCurrency($earnings);
                    ?></strong></td>
                </tr>
                <tr>
                    <th>PF</th>
                    <td><?php echo formatCurrency($salaryStructure['provident_fund']); ?></td>
                </tr>
                <tr>
                    <th>Professional Tax</th>
                    <td><?php echo formatCurrency($salaryStructure['professional_tax']); ?></td>
                </tr>
                <tr>
                    <th>Income Tax</th>
                    <td><?php echo formatCurrency($salaryStructure['income_tax']); ?></td>
                </tr>
                <tr>
                    <th>Total Deductions</th>
                    <td><strong><?php 
                        $deductions = $salaryStructure['provident_fund'] + $salaryStructure['professional_tax'] + 
                                     $salaryStructure['income_tax'];
                        echo formatCurrency($deductions);
                    ?></strong></td>
                </tr>
                <tr>
                    <th>Net Salary</th>
                    <td><strong style="font-size: 18px; color: var(--success-color);"><?php 
                        echo formatCurrency($earnings - $deductions);
                    ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Salary History -->
<?php if (!empty($salaryHistory)): ?>
<div class="dashboard-card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>Salary History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Month/Year</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salaryHistory as $salary): ?>
                        <tr>
                            <td><?php echo getMonthName($salary['month']) . ' ' . $salary['year']; ?></td>
                            <td><?php echo formatCurrency($salary['net_salary']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $salary['status']; ?>">
                                    <?php echo ucfirst($salary['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $salary['payment_date'] ? formatDate($salary['payment_date']) : '-'; ?></td>
                            <td>
                                <a href="../salary/slips.php?id=<?php echo $salary['id']; ?>" 
                                   class="btn btn-sm btn-outline" target="_blank">View Slip</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

