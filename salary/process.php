<?php
/**
 * Salary Processing Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Get departments
$stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

$pageTitle = 'Salary Processing';
include '../includes/header.php';
?>

<div class="page-header">
    <h1>Salary Processing</h1>
</div>

<div class="dashboard-grid">
    <!-- Generate Salary Form -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Generate Monthly Salary</h2>
        </div>
        <div class="card-body">
            <form id="salaryProcessForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_month">Month *</label>
                        <select id="salary_month" name="month" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                    <?php echo getMonthName($i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="salary_year">Year *</label>
                        <select id="salary_year" name="year" required>
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="department_filter">Department (Optional)</label>
                    <select id="department_filter" name="department_id">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_inactive" id="include_inactive">
                        <span>Include Inactive Employees</span>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="button" class="btn btn-primary" onclick="previewSalary()">Preview</button>
                    <button type="button" class="btn btn-success" onclick="generateSalary()">Generate Salary</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="dashboard-card" id="previewSection" style="display: none;">
        <div class="card-header">
            <h2>Salary Preview</h2>
        </div>
        <div class="card-body">
            <div id="previewContent"></div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid" style="margin-top: 20px;">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3 id="totalEmployees">0</h3>
            <p>Total Employees</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <h3 id="processedCount">0</h3>
            <p>Processed This Month</p>
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
            <h3 id="pendingCount">0</h3>
            <p>Pending Approvals</p>
        </div>
    </div>
</div>

<script src="../assets/js/salary.js"></script>
<script>
function previewSalary() {
    const form = document.getElementById('salaryProcessForm');
    const formData = new FormData(form);
    formData.append('action', 'preview');
    
    setLoading(event.target, true);
    
    fetch('../api/salary.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        setLoading(event.target, false);
        if (data.success) {
            document.getElementById('previewSection').style.display = 'block';
            document.getElementById('previewContent').innerHTML = data.data.html;
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        setLoading(event.target, false);
        showToast('An error occurred', 'error');
    });
}

function generateSalary() {
    if (!confirm('Are you sure you want to generate salaries for the selected month? This action cannot be undone.')) {
        return;
    }
    
    const form = document.getElementById('salaryProcessForm');
    const formData = new FormData(form);
    formData.append('action', 'generate');
    
    setLoading(event.target, true);
    
    fetch('../api/salary.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        setLoading(event.target, false);
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'history.php';
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        setLoading(event.target, false);
        showToast('An error occurred', 'error');
    });
}

// Load stats on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSalaryStats();
});

function loadSalaryStats() {
    ajaxRequest('../api/salary.php?action=stats')
        .then(data => {
            if (data.success) {
                document.getElementById('totalEmployees').textContent = data.data.total_employees || 0;
                document.getElementById('processedCount').textContent = data.data.processed_count || 0;
                document.getElementById('pendingCount').textContent = data.data.pending_count || 0;
            }
        });
}
</script>

<?php include '../includes/footer.php'; ?>

