/**
 * Employee Management JavaScript
 */

const EMP_API = (window.APP_URL ? `${window.APP_URL}/api/employees.php` : '../api/employees.php');
const EMP_LIST_URL = (window.APP_URL ? `${window.APP_URL}/employees/list.php` : '../employees/list.php');

document.addEventListener('DOMContentLoaded', function() {
    initEmployeeList();
    initEmployeeForm();
});

// Employee List Functions
function initEmployeeList() {
    // Search functionality
    const searchInput = document.getElementById('employeeSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            filterEmployees();
        }, 300));
    }
    
    // Filter functionality
    const filters = document.querySelectorAll('.filter-select');
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            filterEmployees();
        });
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete-employee');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const employeeId = this.dataset.id;
            const employeeName = this.dataset.name;
            
            confirmAction(
                `Are you sure you want to delete ${employeeName}? This action cannot be undone.`,
                function() {
                    deleteEmployee(employeeId);
                }
            );
        });
    });
    
    // Status toggle
    const statusToggles = document.querySelectorAll('.status-toggle');
    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const employeeId = this.dataset.id;
            const status = this.checked ? 'active' : 'inactive';
            updateEmployeeStatus(employeeId, status);
        });
    });
}

function filterEmployees() {
    const search = document.getElementById('employeeSearch')?.value.toLowerCase() || '';
    const department = document.getElementById('filterDepartment')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    
    const rows = document.querySelectorAll('.employee-row');
    rows.forEach(row => {
        const name = row.dataset.name?.toLowerCase() || '';
        const dept = row.dataset.department || '';
        const empStatus = row.dataset.status || '';
        
        const matchesSearch = !search || name.includes(search);
        const matchesDept = !department || dept === department;
        const matchesStatus = !status || empStatus === status;
        
        row.style.display = (matchesSearch && matchesDept && matchesStatus) ? '' : 'none';
    });
}

function deleteEmployee(id) {
    setLoading(event.target, true);
    
    ajaxRequest(`${EMP_API}?action=delete&id=${id}`, {
        method: 'DELETE'
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
            setLoading(event.target, false);
        }
    })
    .catch(error => {
        showToast('Failed to delete employee', 'error');
        setLoading(event.target, false);
    });
}

function updateEmployeeStatus(id, status) {
    ajaxRequest(EMP_API, {
        method: 'POST',
        body: {
            action: 'update_status',
            id: id,
            status: status
        }
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
            // Revert toggle
            const toggle = document.querySelector(`.status-toggle[data-id="${id}"]`);
            if (toggle) toggle.checked = !toggle.checked;
        }
    })
    .catch(error => {
        showToast('Failed to update status', 'error');
        const toggle = document.querySelector(`.status-toggle[data-id="${id}"]`);
        if (toggle) toggle.checked = !toggle.checked;
    });
}

// Employee Form Functions
function initEmployeeForm() {
    const form = document.getElementById('employeeForm');
    if (!form) return;
    
    // Auto-generate employee code
    const departmentSelect = document.getElementById('department_id');
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            generateEmployeeCode(this.value);
        });
    }
    
    // Calculate salary preview
    const basicSalaryInput = document.getElementById('basic_salary');
    if (basicSalaryInput) {
        basicSalaryInput.addEventListener('input', function() {
            calculateSalaryPreview();
        });
    }
    
    // Photo preview
    const photoInput = document.getElementById('photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            previewPhoto(e.target.files[0]);
        });
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitEmployeeForm(form);
    });
}

function generateEmployeeCode(departmentId) {
    if (!departmentId) return;
    
    ajaxRequest(`${EMP_API}?action=generate_code&department_id=${departmentId}`)
        .then(data => {
            if (data.success && data.data.code) {
                const codeInput = document.getElementById('employee_code');
                if (codeInput) {
                    codeInput.value = data.data.code;
                }
            }
        });
}

function calculateSalaryPreview() {
    const basic = parseFloat(document.getElementById('basic_salary')?.value || 0);
    if (!basic) return;
    
    const hra = basic * 0.4;
    const da = basic * 0.2;
    const pf = basic * 0.12;
    
    const totalEarnings = basic + hra + da;
    const totalDeductions = pf;
    const netSalary = totalEarnings - totalDeductions;
    
    // Update preview if element exists
    const preview = document.getElementById('salaryPreview');
    if (preview) {
        preview.innerHTML = `
            <div class="salary-preview">
                <h4>Salary Preview</h4>
                <div class="preview-row">
                    <span>Basic:</span>
                    <strong>${formatCurrency(basic)}</strong>
                </div>
                <div class="preview-row">
                    <span>HRA (40%):</span>
                    <strong>${formatCurrency(hra)}</strong>
                </div>
                <div class="preview-row">
                    <span>DA (20%):</span>
                    <strong>${formatCurrency(da)}</strong>
                </div>
                <div class="preview-row">
                    <span>PF (12%):</span>
                    <strong>${formatCurrency(pf)}</strong>
                </div>
                <div class="preview-row total">
                    <span>Net Salary:</span>
                    <strong>${formatCurrency(netSalary)}</strong>
                </div>
            </div>
        `;
    }
}

function previewPhoto(file) {
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        showToast('Please select an image file', 'error');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('photoPreview');
        if (preview) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Photo Preview" style="max-width: 200px; border-radius: 8px;">`;
        }
    };
    reader.readAsDataURL(file);
}

function submitEmployeeForm(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    setLoading(submitBtn, true);
    
    const formData = new FormData(form);
    
    fetch(EMP_API, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        setLoading(submitBtn, false);
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = EMP_LIST_URL;
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        setLoading(submitBtn, false);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

