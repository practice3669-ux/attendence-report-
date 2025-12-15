<?php
/**
 * Employee List Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = RECORDS_PER_PAGE;

// Get total count
$stmt = $db->query("SELECT COUNT(*) as total FROM employees");
$totalRecords = $stmt->fetch()['total'];
$pagination = getPagination($page, $totalRecords, $perPage);

// Get employees
$stmt = $db->prepare("
    SELECT e.*, d.name as department_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY e.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $pagination['offset']]);
$employees = $stmt->fetchAll();

// Get departments for filter
$stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

$pageTitle = 'Employees';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Employees</h1>
    <a href="add.php" class="btn btn-primary">Add New Employee</a>
</div>

<!-- Search and Filters -->
<div class="search-filters">
    <div class="filter-row">
        <div class="form-group">
            <label for="employeeSearch">Search</label>
            <input type="text" id="employeeSearch" placeholder="Search by name, code, email..." class="form-control">
        </div>
        <div class="form-group">
            <label for="filterDepartment">Department</label>
            <select id="filterDepartment" class="filter-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="filterStatus">Status</label>
            <select id="filterStatus" class="filter-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-outline" onclick="exportEmployees()">Export</button>
        </div>
    </div>
</div>

<!-- Employees Table -->
<div class="dashboard-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No employees found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr class="employee-row" 
                                data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                data-department="<?php echo $employee['department_id']; ?>"
                                data-status="<?php echo $employee['status']; ?>">
                                <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($employee['designation'] ?? '-'); ?></td>
                                <td><?php echo formatDate($employee['join_date']); ?></td>
                                <td>
                                    <label class="checkbox-label">
                                        <input type="checkbox" 
                                               class="status-toggle" 
                                               data-id="<?php echo $employee['id']; ?>"
                                               <?php echo $employee['status'] === 'active' ? 'checked' : ''; ?>>
                                        <span class="badge badge-<?php echo $employee['status']; ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="view.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="add.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <button class="btn btn-sm btn-danger btn-delete-employee" 
                                                data-id="<?php echo $employee['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($employee['name']); ?>">
                                            Delete
                                        </button>
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
                    <a href="?page=<?php echo $pagination['current_page'] - 1; ?>">&laquo; Previous</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php elseif (abs($i - $pagination['current_page']) == 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?php echo $pagination['current_page'] + 1; ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/employees.js"></script>
<script>
function exportEmployees() {
    window.location.href = '../api/employees.php?action=export';
}
</script>

<?php include '../includes/footer.php'; ?>

