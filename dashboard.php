<?php
/**
 * Dashboard Page
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();

// Get statistics
$stats = [];

// Total employees
$stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
$stats['total_employees'] = $stmt->fetch()['total'];

// Total payroll (current month)
$currentMonth = date('n');
$currentYear = date('Y');
$stmt = $db->prepare("SELECT SUM(net_salary) as total FROM salary_transactions WHERE month = ? AND year = ? AND status = 'paid'");
$stmt->execute([$currentMonth, $currentYear]);
$stats['total_payroll'] = $stmt->fetch()['total'] ?? 0;

// Pending approvals
$stmt = $db->query("SELECT COUNT(*) as total FROM salary_transactions WHERE status = 'pending'");
$stats['pending_approvals'] = $stmt->fetch()['total'];

// Recent transactions
$stmt = $db->prepare("
    SELECT st.*, e.name as employee_name, e.department_id, d.name as department_name
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY st.generated_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_transactions = $stmt->fetchAll();

// Monthly salary distribution (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('n', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $stmt = $db->prepare("SELECT SUM(net_salary) as total FROM salary_transactions WHERE month = ? AND year = ? AND status = 'paid'");
    $stmt->execute([$month, $year]);
    $result = $stmt->fetch();
    $monthlyData[] = [
        'month' => getMonthName($month),
        'total' => $result['total'] ?? 0
    ];
}

// Upcoming salary dates
$stmt = $db->query("SELECT salary_payment_day FROM company_settings LIMIT 1");
$paymentDay = $stmt->fetch()['salary_payment_day'] ?? 1;
$today = date('d');
$upcomingDate = date('Y-m-' . str_pad($paymentDay, 2, '0', STR_PAD_LEFT));
if ($today > $paymentDay) {
    $upcomingDate = date('Y-m-' . str_pad($paymentDay, 2, '0', STR_PAD_LEFT), strtotime('+1 month'));
}

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="dashboard">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</p>
    </div>

    <!-- Statistics Cards -->
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
                <h3><?php echo number_format($stats['total_employees']); ?></h3>
                <p>Total Employees</p>
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
                <h3><?php echo formatCurrency($stats['total_payroll']); ?></h3>
                <p>Total Payroll (This Month)</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['pending_approvals']); ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo formatDate($upcomingDate); ?></h3>
                <p>Next Salary Date</p>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Transactions -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h2>Monthly Salary Distribution</h2>
            </div>
            <div class="card-body">
                <canvas id="salaryChart"></canvas>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2>Recent Salary Transactions</h2>
                <a href="salary/history.php" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No transactions found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['employee_name']); ?></td>
                                        <td><?php echo getMonthName($transaction['month']) . ' ' . $transaction['year']; ?></td>
                                        <td><?php echo formatCurrency($transaction['net_salary']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $transaction['status']; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Salary Chart
    const ctx = document.getElementById('salaryChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Salary (₹)',
                    data: <?php echo json_encode(array_column($monthlyData, 'total')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
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
</script>

<?php include 'includes/footer.php'; ?>

