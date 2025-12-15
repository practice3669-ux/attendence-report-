<?php
/**
 * Salary API Endpoint
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'preview':
        handlePreview();
        break;
    
    case 'generate':
        handleGenerate();
        break;
    
    case 'approve':
        handleApprove();
        break;
    
    case 'mark_paid':
        handleMarkPaid();
        break;
    
    case 'send_slip':
        handleSendSlip();
        break;
    
    case 'stats':
        handleStats();
        break;
    
    case 'export':
        handleExport();
        break;
    
    default:
        sendError('Invalid action');
}

function handlePreview() {
    global $db;
    
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $departmentId = intval($_POST['department_id'] ?? 0);
    $includeInactive = isset($_POST['include_inactive']);
    
    if (!$month || !$year) {
        sendError('Month and year are required');
    }
    
    // Build query
    $where = ["e.status = 'active'"];
    $params = [];
    
    if ($departmentId) {
        $where[] = "e.department_id = ?";
        $params[] = $departmentId;
    }
    
    if ($includeInactive) {
        $where = ["1=1"];
    }
    
    // Check existing salaries
    $where[] = "NOT EXISTS (
        SELECT 1 FROM salary_transactions st2 
        WHERE st2.employee_id = e.id 
        AND st2.month = ? 
        AND st2.year = ?
    )";
    $params[] = $month;
    $params[] = $year;
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    $stmt = $db->prepare("
        SELECT e.*, d.name as department_name, ss.*
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN salary_structures ss ON e.id = ss.employee_id
        $whereClause
        ORDER BY d.name, e.name
    ");
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
    
    $html = '<div class="table-responsive"><table class="table"><thead><tr>';
    $html .= '<th>Employee</th><th>Department</th><th>Basic</th><th>Net Salary</th></tr></thead><tbody>';
    
    $totalNet = 0;
    foreach ($employees as $emp) {
        $salary = calculateSalary($emp['basic_salary'] ?? 0, [
            'hra' => $emp['hra'] ?? 0,
            'da' => $emp['da'] ?? 0,
            'ta' => $emp['ta'] ?? 0,
            'medical_allowance' => $emp['medical_allowance'] ?? 0,
            'special_allowance' => $emp['special_allowance'] ?? 0,
            'provident_fund' => $emp['provident_fund'] ?? 0,
            'professional_tax' => $emp['professional_tax'] ?? 0,
            'income_tax' => $emp['income_tax'] ?? 0
        ]);
        
        $totalNet += $salary['net_salary'];
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($emp['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($emp['department_name'] ?? '-') . '</td>';
        $html .= '<td>' . formatCurrency($emp['basic_salary'] ?? 0) . '</td>';
        $html .= '<td><strong>' . formatCurrency($salary['net_salary']) . '</strong></td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    $html .= '<div style="margin-top: 20px; padding: 15px; background: var(--bg-color); border-radius: 8px;">';
    $html .= '<strong>Total Employees: ' . count($employees) . '</strong><br>';
    $html .= '<strong>Total Payroll: ' . formatCurrency($totalNet) . '</strong>';
    $html .= '</div>';
    
    sendSuccess('Preview generated', ['html' => $html, 'count' => count($employees), 'total' => $totalNet]);
}

function handleGenerate() {
    global $db;
    
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $departmentId = intval($_POST['department_id'] ?? 0);
    $includeInactive = isset($_POST['include_inactive']);
    $userId = getCurrentUserId();
    
    if (!$month || !$year) {
        sendError('Month and year are required');
    }
    
    try {
        $db->beginTransaction();
        
        // Build query
        $where = ["e.status = 'active'"];
        $params = [];
        
        if ($departmentId) {
            $where[] = "e.department_id = ?";
            $params[] = $departmentId;
        }
        
        if ($includeInactive) {
            $where = ["1=1"];
        }
        
        // Exclude already processed
        $where[] = "NOT EXISTS (
            SELECT 1 FROM salary_transactions st2 
            WHERE st2.employee_id = e.id 
            AND st2.month = ? 
            AND st2.year = ?
        )";
        $params[] = $month;
        $params[] = $year;
        
        $whereClause = "WHERE " . implode(" AND ", $where);
        
        $stmt = $db->prepare("
            SELECT e.*, ss.*
            FROM employees e
            LEFT JOIN salary_structures ss ON e.id = ss.employee_id
            $whereClause
        ");
        $stmt->execute($params);
        $employees = $stmt->fetchAll();
        
        $count = 0;
        foreach ($employees as $emp) {
            $salary = calculateSalary($emp['basic_salary'] ?? 0, [
                'hra' => $emp['hra'] ?? 0,
                'da' => $emp['da'] ?? 0,
                'ta' => $emp['ta'] ?? 0,
                'medical_allowance' => $emp['medical_allowance'] ?? 0,
                'special_allowance' => $emp['special_allowance'] ?? 0,
                'provident_fund' => $emp['provident_fund'] ?? 0,
                'professional_tax' => $emp['professional_tax'] ?? 0,
                'income_tax' => $emp['income_tax'] ?? 0
            ]);
            
            $stmt = $db->prepare("
                INSERT INTO salary_transactions (
                    employee_id, month, year, basic_salary, hra, da, ta,
                    medical_allowance, special_allowance, bonus, other_allowances,
                    total_earnings, provident_fund, professional_tax, income_tax,
                    other_deductions, total_deductions, net_salary, status, generated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([
                $emp['id'], $month, $year,
                $salary['basic'], $salary['hra'], $salary['da'], $salary['ta'],
                $salary['medical'], $salary['special'], 0, $salary['other_allowances'],
                $salary['total_earnings'], $salary['pf'], $salary['pt'], $salary['it'],
                $salary['other_deductions'], $salary['total_deductions'], $salary['net_salary'],
                $userId
            ]);
            
            $count++;
        }
        
        $db->commit();
        logActivity('generate_salary', 'salary_transactions', null, null, ['month' => $month, 'year' => $year, 'count' => $count]);
        sendSuccess("Salary generated for $count employees");
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Salary generation error: " . $e->getMessage());
        sendError('Failed to generate salary: ' . $e->getMessage());
    }
}

function handleApprove() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        sendError('Invalid salary ID');
    }
    
    try {
        $stmt = $db->prepare("UPDATE salary_transactions SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity('approve_salary', 'salary_transactions', $id);
        sendSuccess('Salary approved successfully');
        
    } catch (Exception $e) {
        error_log("Approve error: " . $e->getMessage());
        sendError('Failed to approve salary');
    }
}

function handleMarkPaid() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $paymentDate = sanitizeDB($_POST['payment_date'] ?? '');
    $paymentMethod = sanitizeDB($_POST['payment_method'] ?? 'bank_transfer');
    $transactionId = sanitizeDB($_POST['transaction_id'] ?? '');
    
    if (!$id || !$paymentDate) {
        sendError('Payment date is required');
    }
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            UPDATE salary_transactions 
            SET status = 'paid', payment_date = ?, payment_method = ?, transaction_id = ?, paid_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$paymentDate, $paymentMethod, $transactionId, $id]);
        
        // Create payment record
        $stmt = $db->prepare("SELECT net_salary FROM salary_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $salary = $stmt->fetch();
        
        $stmt = $db->prepare("
            INSERT INTO payments (salary_id, payment_method, transaction_id, amount, status, payment_date, processed_by)
            VALUES (?, ?, ?, ?, 'completed', ?, ?)
        ");
        $stmt->execute([$id, $paymentMethod, $transactionId, $salary['net_salary'], $paymentDate, getCurrentUserId()]);
        
        $db->commit();
        logActivity('mark_paid', 'salary_transactions', $id);
        sendSuccess('Salary marked as paid');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Mark paid error: " . $e->getMessage());
        sendError('Failed to mark as paid');
    }
}

function handleSendSlip() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        sendError('Invalid salary ID');
    }
    
    // In a real application, you would send an email here
    // For now, we'll just log it
    
    logActivity('send_slip', 'salary_transactions', $id);
    sendSuccess('Salary slip sent successfully');
}

function handleStats() {
    global $db;
    
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM salary_transactions WHERE month = ? AND year = ?");
    $stmt->execute([$currentMonth, $currentYear]);
    $processedCount = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM salary_transactions WHERE status = 'pending'");
    $pendingCount = $stmt->fetch()['total'];
    
    sendSuccess('Stats retrieved', [
        'total_employees' => $totalEmployees,
        'processed_count' => $processedCount,
        'pending_count' => $pendingCount
    ]);
}

function handleExport() {
    global $db;
    
    $month = intval($_GET['month'] ?? 0);
    $year = intval($_GET['year'] ?? 0);
    
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
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $db->prepare("
        SELECT e.employee_code, e.name, d.name as department, st.month, st.year,
               st.net_salary, st.status, st.payment_date
        FROM salary_transactions st
        JOIN employees e ON st.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        $whereClause
        ORDER BY st.year DESC, st.month DESC, e.name
    ");
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="salary_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Name', 'Department', 'Month', 'Year', 'Net Salary', 'Status', 'Payment Date']);
    
    foreach ($salaries as $salary) {
        fputcsv($output, [
            $salary['employee_code'],
            $salary['name'],
            $salary['department'],
            getMonthName($salary['month']),
            $salary['year'],
            $salary['net_salary'],
            $salary['status'],
            $salary['payment_date'] ?? ''
        ]);
    }
    
    fclose($output);
    exit();
}

