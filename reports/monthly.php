<?php
/**
 * Monthly Reports Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Get filter values
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

// Build query
$where = ["st.month = ?", "st.year = ?"];
$params = [$month, $year];

if ($departmentId) {
    $where[] = "e.department_id = ?";
    $params[] = $departmentId;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Get salary report
$stmt = $db->prepare("
    SELECT e.employee_code, e.name, d.name as department, st.*
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    $whereClause
    ORDER BY d.name, e.name
");
$stmt->execute($params);
$salaries = $stmt->fetchAll();

// Calculate totals
$totalEarnings = 0;
$totalDeductions = 0;
$totalNet = 0;
foreach ($salaries as $salary) {
    $totalEarnings += $salary['total_earnings'];
    $totalDeductions += $salary['total_deductions'];
    $totalNet += $salary['net_salary'];
}

// Get departments
$stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

// Department-wise summary
$stmt = $db->prepare("
    SELECT d.name as department, COUNT(*) as count, SUM(st.net_salary) as total
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    $whereClause
    GROUP BY d.id, d.name
    ORDER BY d.name
");
$stmt->execute($params);
$deptSummary = $stmt->fetchAll();

$pageTitle = 'Monthly Report';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Monthly Salary Report</h1>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-success" onclick="exportReport()">Export to Excel</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
</div>

<!-- Filters -->
<div class="search-filters">
    <form method="GET" action="" style="display: contents;">
        <div class="filter-row">
            <div class="form-group">
                <label for="report_month">Month</label>
                <select id="report_month" name="month" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                            <?php echo getMonthName($i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="report_year">Year</label>
                <select id="report_year" name="year" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="report_department">Department</label>
                <select id="report_department" name="department_id" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $departmentId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo count($salaries); ?></h3>
            <p>Employees</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalNet); ?></h3>
            <p>Total Payroll</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalEarnings); ?></h3>
            <p>Total Earnings</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalDeductions); ?></h3>
            <p>Total Deductions</p>
        </div>
    </div>
</div>

<!-- Department Summary -->
<?php if (!empty($deptSummary)): ?>
<div class="dashboard-card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>Department-wise Summary</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Total Payroll</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deptSummary as $dept): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dept['department'] ?? 'N/A'); ?></td>
                            <td><?php echo $dept['count']; ?></td>
                            <td><strong><?php echo formatCurrency($dept['total']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Report -->
<div class="dashboard-card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>Detailed Report</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Basic</th>
                        <th>Earnings</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salaries)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($salaries as $salary): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($salary['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($salary['name']); ?></td>
                                <td><?php echo htmlspecialchars($salary['department'] ?? '-'); ?></td>
                                <td><?php echo formatCurrency($salary['basic_salary']); ?></td>
                                <td><?php echo formatCurrency($salary['total_earnings']); ?></td>
                                <td><?php echo formatCurrency($salary['total_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($salary['net_salary']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $salary['status']; ?>">
                                        <?php echo ucfirst($salary['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: var(--bg-color); font-weight: 600;">
                            <td colspan="3"><strong>Total</strong></td>
                            <td><?php echo formatCurrency(array_sum(array_column($salaries, 'basic_salary'))); ?></td>
                            <td><?php echo formatCurrency($totalEarnings); ?></td>
                            <td><?php echo formatCurrency($totalDeductions); ?></td>
                            <td><strong><?php echo formatCurrency($totalNet); ?></strong></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportReport() {
    const month = document.getElementById('report_month').value;
    const year = document.getElementById('report_year').value;
    const dept = document.getElementById('report_department').value;
    
    window.location.href = `../api/reports.php?action=export_monthly&month=${month}&year=${year}&department_id=${dept}`;
}
</script>

<?php include '../includes/footer.php'; ?>

