<?php
/**
 * Yearly Reports Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Get filter values
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// Get employees for filter
$stmt = $db->query("SELECT id, name, employee_code FROM employees WHERE status = 'active' ORDER BY name");
$employees = $stmt->fetchAll();

// Get yearly summary
$where = ["st.year = ?"];
$params = [$year];

if ($employeeId) {
    $where[] = "st.employee_id = ?";
    $params[] = $employeeId;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Monthly breakdown
$stmt = $db->prepare("
    SELECT st.month, COUNT(*) as count, SUM(st.net_salary) as total
    FROM salary_transactions st
    $whereClause
    GROUP BY st.month
    ORDER BY st.month
");
$stmt->execute($params);
$monthlyData = $stmt->fetchAll();

// Employee-wise summary
$stmt = $db->prepare("
    SELECT e.employee_code, e.name, d.name as department,
           COUNT(*) as months, SUM(st.net_salary) as total
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    $whereClause
    GROUP BY e.id, e.employee_code, e.name, d.name
    ORDER BY e.name
");
$stmt->execute($params);
$employeeSummary = $stmt->fetchAll();

// Calculate totals
$totalEmployees = count($employeeSummary);
$totalPayroll = array_sum(array_column($employeeSummary, 'total'));

$pageTitle = 'Yearly Report';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Yearly Salary Report</h1>
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
                <label for="report_employee">Employee (Optional)</label>
                <select id="report_employee" name="employee_id" onchange="this.form.submit()">
                    <option value="0">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $employeeId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['name']); ?>
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
            <h3><?php echo $totalEmployees; ?></h3>
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
            <h3><?php echo formatCurrency($totalPayroll); ?></h3>
            <p>Total Payroll (<?php echo $year; ?>)</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalPayroll / 12); ?></h3>
            <p>Average Monthly</p>
        </div>
    </div>
</div>

<!-- Monthly Breakdown Chart -->
<?php if (!empty($monthlyData)): ?>
<div class="dashboard-card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>Monthly Breakdown</h2>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Employee Summary -->
<div class="dashboard-card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>Employee-wise Summary</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Months</th>
                        <th>Total Salary</th>
                        <th>Average Monthly</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employeeSummary)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employeeSummary as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                                <td><?php echo $emp['months']; ?></td>
                                <td><strong><?php echo formatCurrency($emp['total']); ?></strong></td>
                                <td><?php echo formatCurrency($emp['total'] / max($emp['months'], 1)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($monthlyData)): ?>
const ctx = document.getElementById('monthlyChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($m) { return getMonthName($m['month']); }, $monthlyData)); ?>,
            datasets: [{
                label: 'Salary (₹)',
                data: <?php echo json_encode(array_column($monthlyData, 'total')); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.6)',
                borderColor: '#3498db',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString('en-IN');
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>

function exportReport() {
    const year = document.getElementById('report_year').value;
    const employee = document.getElementById('report_employee').value;
    
    window.location.href = `../api/reports.php?action=export_yearly&year=${year}&employee_id=${employee}`;
}
</script>

<?php include '../includes/footer.php'; ?>

