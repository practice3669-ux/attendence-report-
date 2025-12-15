<?php
/**
 * Reports API Endpoint
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'export_monthly':
        handleExportMonthly();
        break;
    
    case 'export_yearly':
        handleExportYearly();
        break;
    
    default:
        sendError('Invalid action');
}

function handleExportMonthly() {
    global $db;
    
    $month = intval($_GET['month'] ?? 0);
    $year = intval($_GET['year'] ?? 0);
    $departmentId = intval($_GET['department_id'] ?? 0);
    
    if (!$month || !$year) {
        sendError('Month and year are required');
    }
    
    $where = ["st.month = ?", "st.year = ?"];
    $params = [$month, $year];
    
    if ($departmentId) {
        $where[] = "e.department_id = ?";
        $params[] = $departmentId;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    $stmt = $db->prepare("
        SELECT e.employee_code, e.name, d.name as department,
               st.basic_salary, st.total_earnings, st.total_deductions, st.net_salary, st.status
        FROM salary_transactions st
        JOIN employees e ON st.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        $whereClause
        ORDER BY d.name, e.name
    ");
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="monthly_report_' . getMonthName($month) . '_' . $year . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Name', 'Department', 'Basic', 'Earnings', 'Deductions', 'Net Salary', 'Status']);
    
    foreach ($salaries as $salary) {
        fputcsv($output, [
            $salary['employee_code'],
            $salary['name'],
            $salary['department'] ?? '',
            $salary['basic_salary'],
            $salary['total_earnings'],
            $salary['total_deductions'],
            $salary['net_salary'],
            $salary['status']
        ]);
    }
    
    fclose($output);
    exit();
}

function handleExportYearly() {
    global $db;
    
    $year = intval($_GET['year'] ?? 0);
    $employeeId = intval($_GET['employee_id'] ?? 0);
    
    if (!$year) {
        sendError('Year is required');
    }
    
    $where = ["st.year = ?"];
    $params = [$year];
    
    if ($employeeId) {
        $where[] = "st.employee_id = ?";
        $params[] = $employeeId;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    $stmt = $db->prepare("
        SELECT e.employee_code, e.name, d.name as department,
               st.month, st.year, st.net_salary, st.status
        FROM salary_transactions st
        JOIN employees e ON st.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        $whereClause
        ORDER BY e.name, st.month
    ");
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="yearly_report_' . $year . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Name', 'Department', 'Month', 'Year', 'Net Salary', 'Status']);
    
    foreach ($salaries as $salary) {
        fputcsv($output, [
            $salary['employee_code'],
            $salary['name'],
            $salary['department'] ?? '',
            getMonthName($salary['month']),
            $salary['year'],
            $salary['net_salary'],
            $salary['status']
        ]);
    }
    
    fclose($output);
    exit();
}

