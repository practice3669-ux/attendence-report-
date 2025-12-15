/**
 * Salary Processing JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initSalaryHistory();
    initSalaryForm();
});

// Salary History Functions
function initSalaryHistory() {
    // Search functionality
    const searchInput = document.getElementById('salarySearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            filterSalaries();
        }, 300));
    }
    
    // Filter functionality
    const filters = document.querySelectorAll('.salary-filter');
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            filterSalaries();
        });
    });
}

function filterSalaries() {
    const search = document.getElementById('salarySearch')?.value.toLowerCase() || '';
    const month = document.getElementById('filterMonth')?.value || '';
    const year = document.getElementById('filterYear')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    
    const rows = document.querySelectorAll('.salary-row');
    rows.forEach(row => {
        const employeeName = row.dataset.name?.toLowerCase() || '';
        const salaryMonth = row.dataset.month || '';
        const salaryYear = row.dataset.year || '';
        const salaryStatus = row.dataset.status || '';
        
        const matchesSearch = !search || employeeName.includes(search);
        const matchesMonth = !month || salaryMonth === month;
        const matchesYear = !year || salaryYear === year;
        const matchesStatus = !status || salaryStatus === status;
        
        row.style.display = (matchesSearch && matchesMonth && matchesYear && matchesStatus) ? '' : 'none';
    });
}

// Salary Form Functions
function initSalaryForm() {
    // Calculate salary on input
    const basicInput = document.getElementById('basic_salary');
    if (basicInput) {
        basicInput.addEventListener('input', calculateNetSalary);
    }
    
    const hraInput = document.getElementById('hra');
    if (hraInput) {
        hraInput.addEventListener('input', calculateNetSalary);
    }
    
    const daInput = document.getElementById('da');
    if (daInput) {
        daInput.addEventListener('input', calculateNetSalary);
    }
}

function calculateNetSalary() {
    const basic = parseFloat(document.getElementById('basic_salary')?.value || 0);
    const hra = parseFloat(document.getElementById('hra')?.value || 0);
    const da = parseFloat(document.getElementById('da')?.value || 0);
    const ta = parseFloat(document.getElementById('ta')?.value || 0);
    const medical = parseFloat(document.getElementById('medical_allowance')?.value || 0);
    const special = parseFloat(document.getElementById('special_allowance')?.value || 0);
    const bonus = parseFloat(document.getElementById('bonus')?.value || 0);
    const ot = parseFloat(document.getElementById('ot_amount')?.value || 0);
    
    const pf = parseFloat(document.getElementById('provident_fund')?.value || 0);
    const pt = parseFloat(document.getElementById('professional_tax')?.value || 0);
    const it = parseFloat(document.getElementById('income_tax')?.value || 0);
    const otherDeductions = parseFloat(document.getElementById('other_deductions')?.value || 0);
    
    const totalEarnings = basic + hra + da + ta + medical + special + bonus + ot;
    const totalDeductions = pf + pt + it + otherDeductions;
    const netSalary = totalEarnings - totalDeductions;
    
    // Update display
    const netSalaryInput = document.getElementById('net_salary');
    if (netSalaryInput) {
        netSalaryInput.value = netSalary.toFixed(2);
    }
    
    const preview = document.getElementById('salaryCalculationPreview');
    if (preview) {
        preview.innerHTML = `
            <div class="salary-calculation">
                <h4>Salary Calculation</h4>
                <div class="calc-row">
                    <span>Total Earnings:</span>
                    <strong>${formatCurrency(totalEarnings)}</strong>
                </div>
                <div class="calc-row">
                    <span>Total Deductions:</span>
                    <strong>${formatCurrency(totalDeductions)}</strong>
                </div>
                <div class="calc-row total">
                    <span>Net Salary:</span>
                    <strong style="font-size: 18px; color: var(--success-color);">${formatCurrency(netSalary)}</strong>
                </div>
            </div>
        `;
    }
}

// Approve Salary
function approveSalary(id) {
    if (!confirm('Are you sure you want to approve this salary?')) {
        return;
    }
    
    ajaxRequest('../api/salary.php', {
        method: 'POST',
        body: {
            action: 'approve',
            id: id
        }
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

// Mark as Paid
function markAsPaid(id) {
    openModal('markPaidModal', {
        title: 'Mark as Paid',
        body: `
            <form id="markPaidForm">
                <input type="hidden" name="id" value="${id}">
                <div class="form-group">
                    <label for="payment_date">Payment Date *</label>
                    <input type="date" id="payment_date" name="payment_date" value="${new Date().toISOString().split('T')[0]}" required>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transaction_id">Transaction ID / Reference</label>
                    <input type="text" id="transaction_id" name="transaction_id">
                </div>
            </form>
        `,
        footer: `
            <button type="button" class="btn btn-primary" onclick="submitMarkPaid()">Mark as Paid</button>
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        `
    });
}

function submitMarkPaid() {
    const form = document.getElementById('markPaidForm');
    const formData = new FormData(form);
    formData.append('action', 'mark_paid');
    
    ajaxRequest('../api/salary.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

// Send Salary Slip
function sendSalarySlip(id, email) {
    if (!confirm(`Send salary slip to ${email}?`)) {
        return;
    }
    
    setLoading(event.target, true);
    
    ajaxRequest('../api/salary.php', {
        method: 'POST',
        body: {
            action: 'send_slip',
            id: id
        }
    })
    .then(data => {
        setLoading(event.target, false);
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    });
}

// Export Salary
function exportSalary() {
    const month = document.getElementById('filterMonth')?.value || '';
    const year = document.getElementById('filterYear')?.value || '';
    
    let url = '../api/salary.php?action=export';
    if (month) url += '&month=' + month;
    if (year) url += '&year=' + year;
    
    window.location.href = url;
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

