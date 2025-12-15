<?php
/**
 * Common Utility Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize for database (for prepared statements, minimal sanitization)
 */
function sanitizeDB($data) {
    return trim($data);
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format number
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

/**
 * Get month name
 */
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return isset($months[$month]) ? $months[$month] : '';
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 */
function isValidPhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', preg_replace('/[^0-9]/', '', $phone));
}

/**
 * Generate employee code
 */
function generateEmployeeCode($departmentId = null) {
    $db = getDB();
    $prefix = 'EMP';
    
    if ($departmentId) {
        $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch();
        if ($dept) {
            $prefix = strtoupper(substr($dept['name'], 0, 3));
        }
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE employee_code LIKE ?");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Calculate salary components
 */
function calculateSalary($basic, $structure = []) {
    $result = [
        'basic' => $basic,
        'hra' => isset($structure['hra']) ? $structure['hra'] : ($basic * 0.4), // 40% of basic
        'da' => isset($structure['da']) ? $structure['da'] : ($basic * 0.2), // 20% of basic
        'ta' => isset($structure['ta']) ? $structure['ta'] : 0,
        'medical' => isset($structure['medical_allowance']) ? $structure['medical_allowance'] : 0,
        'special' => isset($structure['special_allowance']) ? $structure['special_allowance'] : 0,
        'bonus' => isset($structure['bonus']) ? $structure['bonus'] : 0,
        'other_allowances' => isset($structure['other_allowances']) ? $structure['other_allowances'] : 0,
    ];
    
    $result['total_earnings'] = array_sum([
        $result['basic'],
        $result['hra'],
        $result['da'],
        $result['ta'],
        $result['medical'],
        $result['special'],
        $result['bonus'],
        $result['other_allowances']
    ]);
    
    // Deductions
    $result['pf'] = isset($structure['provident_fund']) ? $structure['provident_fund'] : ($basic * 0.12); // 12% of basic
    $result['pt'] = isset($structure['professional_tax']) ? $structure['professional_tax'] : 0;
    $result['it'] = isset($structure['income_tax']) ? $structure['income_tax'] : 0;
    $result['other_deductions'] = isset($structure['other_deductions']) ? $structure['other_deductions'] : 0;
    
    $result['total_deductions'] = $result['pf'] + $result['pt'] + $result['it'] + $result['other_deductions'];
    $result['net_salary'] = $result['total_earnings'] - $result['total_deductions'];
    
    return $result;
}

/**
 * Get pagination data
 */
function getPagination($page, $totalRecords, $perPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Send JSON response
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 400) {
    sendJSON(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Send success response
 */
function sendSuccess($message, $data = []) {
    sendJSON(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * Log activity
 */
function logActivity($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    $db = getDB();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $ipAddress,
        $userAgent
    ]);
}

/**
 * Create notification
 */
function createNotification($userId, $type, $title, $message, $link = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

/**
 * Upload file
 */
function uploadFile($file, $directory = 'employees') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $directory . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $uploadPath];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file
 */
function deleteFile($filename, $directory = 'employees') {
    $filePath = UPLOAD_DIR . $directory . '/' . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

