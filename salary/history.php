<?php
/**
 * Salary History Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = RECORDS_PER_PAGE;

// Filters
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$status = isset($_GET['status']) ? sanitizeDB($_GET['status']) : '';

// Build query
$where = [];
$params = [];

if ($month) {
    $where[] = "st.month = ?";
    $params[] = $month;
}
if ($year) {
    $where[] = "st.year = ?";
    $params[] = $year;
}
if ($status) {
    $where[] = "st.status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM salary_transactions st $whereClause");
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$pagination = getPagination($page, $totalRecords, $perPage);

// Get salary transactions
$params[] = $perPage;
$params[] = $pagination['offset'];

$stmt = $db->prepare("
    SELECT st.*, e.name as employee_name, e.employee_code, e.email, d.name as department_name
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    $whereClause
    ORDER BY st.year DESC, st.month DESC, e.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get departments for filter
$stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

$pageTitle = 'Salary History';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Salary History</h1>
    <div style="display: flex; gap: 10px;">
        <a href="process.php" class="btn btn-primary">Process Salary</a>
        <button type="button" class="btn btn-success" onclick="exportSalary()">Export</button>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-filters">
    <div class="filter-row">
        <div class="form-group">
            <label for="salarySearch">Search</label>
            <input type="text" id="salarySearch" placeholder="Search by employee name..." class="form-control">
        </div>
        <div class="form-group">
            <label for="filterMonth">Month</label>
            <select id="filterMonth" class="salary-filter">
                <option value="">All Months</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                        <?php echo getMonthName($i); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="filterYear">Year</label>
            <select id="filterYear" class="salary-filter">
                <option value="">All Years</option>
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="filterStatus">Status</label>
            <select id="filterStatus" class="salary-filter">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
        </div>
    </div>
</div>

<!-- Salary Transactions Table -->
<div class="dashboard-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month/Year</th>
                        <th>Department</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No salary records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr class="salary-row"
                                data-name="<?php echo htmlspecialchars($transaction['employee_name']); ?>"
                                data-month="<?php echo $transaction['month']; ?>"
                                data-year="<?php echo $transaction['year']; ?>"
                                data-status="<?php echo $transaction['status']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['employee_name']); ?></strong><br>
                                    <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($transaction['employee_code']); ?></small>
                                </td>
                                <td><?php echo getMonthName($transaction['month']) . ' ' . $transaction['year']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['department_name'] ?? '-'); ?></td>
                                <td><strong><?php echo formatCurrency($transaction['net_salary']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['payment_date'] ? formatDate($transaction['payment_date']) : '-'; ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="slips.php?id=<?php echo $transaction['id']; ?>" 
                                           class="btn btn-sm btn-outline" target="_blank">View Slip</a>
                                        <?php if ($transaction['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="approveSalary(<?php echo $transaction['id']; ?>)">
                                                Approve
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($transaction['status'] === 'approved'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="markAsPaid(<?php echo $transaction['id']; ?>)">
                                                Mark Paid
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($transaction['status'] === 'paid' && $transaction['email']): ?>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="sendSalarySlip(<?php echo $transaction['id']; ?>, '<?php echo htmlspecialchars($transaction['email']); ?>')">
                                                Send Slip
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $month ? '&month=' . $month : ''; ?><?php echo $year ? '&year=' . $year : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>">&laquo; Previous</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $month ? '&month=' . $month : ''; ?><?php echo $year ? '&year=' . $year : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>"><?php echo $i; ?></a>
                    <?php elseif (abs($i - $pagination['current_page']) == 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $month ? '&month=' . $month : ''; ?><?php echo $year ? '&year=' . $year : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/salary.js"></script>

<?php include '../includes/footer.php'; ?>

