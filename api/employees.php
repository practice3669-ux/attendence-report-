<?php
/**
 * Employees API Endpoint
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
    case 'update':
        handleCreateUpdate();
        break;
    
    case 'delete':
        handleDelete();
        break;
    
    case 'update_status':
        handleUpdateStatus();
        break;
    
    case 'generate_code':
        handleGenerateCode();
        break;
    
    case 'export':
        handleExport();
        break;
    
    default:
        sendError('Invalid action');
}

function handleCreateUpdate() {
    global $db;
    
    $isEdit = ($_POST['action'] ?? '') === 'update';
    $employeeId = $isEdit ? intval($_POST['id'] ?? 0) : 0;
    
    // Validate required fields
    $required = ['employee_code', 'name', 'email', 'phone', 'department_id', 'designation', 'join_date', 'basic_salary'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            sendError("Field $field is required");
        }
    }
    
    // Sanitize inputs
    $data = [
        'employee_code' => sanitizeDB($_POST['employee_code']),
        'name' => sanitizeDB($_POST['name']),
        'email' => sanitizeDB($_POST['email']),
        'phone' => sanitizeDB($_POST['phone']),
        'department_id' => intval($_POST['department_id']),
        'designation' => sanitizeDB($_POST['designation']),
        'join_date' => sanitizeDB($_POST['join_date']),
        'address' => sanitizeDB($_POST['address'] ?? ''),
        'city' => sanitizeDB($_POST['city'] ?? ''),
        'state' => sanitizeDB($_POST['state'] ?? ''),
        'pincode' => sanitizeDB($_POST['pincode'] ?? ''),
        'bank_name' => sanitizeDB($_POST['bank_name'] ?? ''),
        'bank_account_number' => sanitizeDB($_POST['bank_account_number'] ?? ''),
        'bank_ifsc' => sanitizeDB($_POST['bank_ifsc'] ?? ''),
        'pan_number' => sanitizeDB($_POST['pan_number'] ?? ''),
        'aadhar_number' => sanitizeDB($_POST['aadhar_number'] ?? ''),
    ];
    
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], 'employees');
        if ($uploadResult['success']) {
            $data['photo'] = $uploadResult['filename'];
            
            // Delete old photo if updating
            if ($isEdit && $employeeId) {
                $stmt = $db->prepare("SELECT photo FROM employees WHERE id = ?");
                $stmt->execute([$employeeId]);
                $oldEmployee = $stmt->fetch();
                if ($oldEmployee && $oldEmployee['photo']) {
                    deleteFile($oldEmployee['photo'], 'employees');
                }
            }
        }
    }
    
    try {
        $db->beginTransaction();
        
        if ($isEdit) {
            // Update employee
            $stmt = $db->prepare("
                UPDATE employees SET
                    name = ?, email = ?, phone = ?, department_id = ?, designation = ?,
                    join_date = ?, address = ?, city = ?, state = ?, pincode = ?,
                    bank_name = ?, bank_account_number = ?, bank_ifsc = ?,
                    pan_number = ?, aadhar_number = ?
                    " . (isset($data['photo']) ? ", photo = ?" : "") . "
                WHERE id = ?
            ");
            
            $params = [
                $data['name'], $data['email'], $data['phone'], $data['department_id'],
                $data['designation'], $data['join_date'], $data['address'], $data['city'],
                $data['state'], $data['pincode'], $data['bank_name'], $data['bank_account_number'],
                $data['bank_ifsc'], $data['pan_number'], $data['aadhar_number']
            ];
            
            if (isset($data['photo'])) {
                $params[] = $data['photo'];
            }
            
            $params[] = $employeeId;
            
            $stmt->execute($params);
            
            logActivity('update', 'employees', $employeeId, null, $data);
            $message = 'Employee updated successfully';
        } else {
            // Check if employee code exists
            $stmt = $db->prepare("SELECT id FROM employees WHERE employee_code = ?");
            $stmt->execute([$data['employee_code']]);
            if ($stmt->fetch()) {
                sendError('Employee code already exists');
            }
            
            // Insert employee
            $stmt = $db->prepare("
                INSERT INTO employees (
                    employee_code, name, email, phone, department_id, designation,
                    join_date, address, city, state, pincode, bank_name,
                    bank_account_number, bank_ifsc, pan_number, aadhar_number, photo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['employee_code'], $data['name'], $data['email'], $data['phone'],
                $data['department_id'], $data['designation'], $data['join_date'],
                $data['address'], $data['city'], $data['state'], $data['pincode'],
                $data['bank_name'], $data['bank_account_number'], $data['bank_ifsc'],
                $data['pan_number'], $data['aadhar_number'], $data['photo'] ?? null
            ]);
            
            $employeeId = $db->lastInsertId();
            logActivity('create', 'employees', $employeeId, null, $data);
            $message = 'Employee added successfully';
        }
        
        // Handle salary structure
        $salaryData = [
            'basic_salary' => floatval($_POST['basic_salary']),
            'hra' => floatval($_POST['hra'] ?? 0),
            'da' => floatval($_POST['da'] ?? 0),
            'ta' => floatval($_POST['ta'] ?? 0),
            'medical_allowance' => floatval($_POST['medical_allowance'] ?? 0),
            'special_allowance' => floatval($_POST['special_allowance'] ?? 0),
            'bonus' => floatval($_POST['bonus'] ?? 0),
            'other_allowances' => floatval($_POST['other_allowances'] ?? 0),
            'provident_fund' => floatval($_POST['provident_fund'] ?? 0),
            'professional_tax' => floatval($_POST['professional_tax'] ?? 0),
            'income_tax' => floatval($_POST['income_tax'] ?? 0),
            'other_deductions' => floatval($_POST['other_deductions'] ?? 0),
            'ot_rate_per_hour' => floatval($_POST['ot_rate_per_hour'] ?? 0),
        ];
        
        if ($isEdit) {
            // Update or insert salary structure
            $stmt = $db->prepare("SELECT id FROM salary_structures WHERE employee_id = ? ORDER BY effective_from DESC LIMIT 1");
            $stmt->execute([$employeeId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $db->prepare("
                    UPDATE salary_structures SET
                        basic_salary = ?, hra = ?, da = ?, ta = ?, medical_allowance = ?,
                        special_allowance = ?, bonus = ?, other_allowances = ?,
                        provident_fund = ?, professional_tax = ?, income_tax = ?,
                        other_deductions = ?, ot_rate_per_hour = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $salaryData['basic_salary'], $salaryData['hra'], $salaryData['da'],
                    $salaryData['ta'], $salaryData['medical_allowance'], $salaryData['special_allowance'],
                    $salaryData['bonus'], $salaryData['other_allowances'], $salaryData['provident_fund'],
                    $salaryData['professional_tax'], $salaryData['income_tax'], $salaryData['other_deductions'],
                    $salaryData['ot_rate_per_hour'], $existing['id']
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO salary_structures (
                        employee_id, basic_salary, hra, da, ta, medical_allowance,
                        special_allowance, bonus, other_allowances, provident_fund,
                        professional_tax, income_tax, other_deductions, ot_rate_per_hour, effective_from
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $stmt->execute([
                    $employeeId, $salaryData['basic_salary'], $salaryData['hra'], $salaryData['da'],
                    $salaryData['ta'], $salaryData['medical_allowance'], $salaryData['special_allowance'],
                    $salaryData['bonus'], $salaryData['other_allowances'], $salaryData['provident_fund'],
                    $salaryData['professional_tax'], $salaryData['income_tax'], $salaryData['other_deductions'],
                    $salaryData['ot_rate_per_hour']
                ]);
            }
        } else {
            // Insert new salary structure
            $stmt = $db->prepare("
                INSERT INTO salary_structures (
                    employee_id, basic_salary, hra, da, ta, medical_allowance,
                    special_allowance, bonus, other_allowances, provident_fund,
                    professional_tax, income_tax, other_deductions, ot_rate_per_hour, effective_from
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([
                $employeeId, $salaryData['basic_salary'], $salaryData['hra'], $salaryData['da'],
                $salaryData['ta'], $salaryData['medical_allowance'], $salaryData['special_allowance'],
                $salaryData['bonus'], $salaryData['other_allowances'], $salaryData['provident_fund'],
                $salaryData['professional_tax'], $salaryData['income_tax'], $salaryData['other_deductions'],
                $salaryData['ot_rate_per_hour']
            ]);
        }
        
        $db->commit();
        sendSuccess($message, ['id' => $employeeId]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Employee create/update error: " . $e->getMessage());
        sendError('Failed to save employee: ' . $e->getMessage());
    }
}

function handleDelete() {
    global $db;
    
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendError('Invalid employee ID');
    }
    
    try {
        // Get employee photo
        $stmt = $db->prepare("SELECT photo FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $employee = $stmt->fetch();
        
        if (!$employee) {
            sendError('Employee not found');
        }
        
        // Delete employee (cascade will handle related records)
        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete photo if exists
        if ($employee['photo']) {
            deleteFile($employee['photo'], 'employees');
        }
        
        logActivity('delete', 'employees', $id);
        sendSuccess('Employee deleted successfully');
        
    } catch (Exception $e) {
        error_log("Employee delete error: " . $e->getMessage());
        sendError('Failed to delete employee');
    }
}

function handleUpdateStatus() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $status = sanitizeDB($_POST['status'] ?? '');
    
    if (!$id || !in_array($status, ['active', 'inactive'])) {
        sendError('Invalid parameters');
    }
    
    try {
        $stmt = $db->prepare("UPDATE employees SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        logActivity('update_status', 'employees', $id, null, ['status' => $status]);
        sendSuccess('Status updated successfully');
        
    } catch (Exception $e) {
        error_log("Status update error: " . $e->getMessage());
        sendError('Failed to update status');
    }
}

function handleGenerateCode() {
    global $db;
    
    $departmentId = intval($_GET['department_id'] ?? 0);
    $code = generateEmployeeCode($departmentId);
    
    sendSuccess('Code generated', ['code' => $code]);
}

function handleExport() {
    global $db;
    
    $stmt = $db->query("
        SELECT e.employee_code, e.name, e.email, e.phone, d.name as department,
               e.designation, e.join_date, e.status
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY e.created_at DESC
    ");
    $employees = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Name', 'Email', 'Phone', 'Department', 'Designation', 'Join Date', 'Status']);
    
    foreach ($employees as $emp) {
        fputcsv($output, [
            $emp['employee_code'],
            $emp['name'],
            $emp['email'],
            $emp['phone'],
            $emp['department'],
            $emp['designation'],
            $emp['join_date'],
            $emp['status']
        ]);
    }
    
    fclose($output);
    exit();
}

