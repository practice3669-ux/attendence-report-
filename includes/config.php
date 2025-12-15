<?php
/**
 * Configuration File for Salary Management System
 * Update these values according to your environment
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', 3306); // MAMP default on Windows
define('DB_NAME', 'salary_management');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMP default password
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Salary Management System');
define('APP_URL', 'http://localhost/birgunjsite222');
define('BASE_PATH', dirname(dirname(__FILE__)));

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'SALARY_SESSION');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_NAME', 'csrf_token');

// File Upload Configuration
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y');
define('DISPLAY_DATETIME_FORMAT', 'd M Y, h:i A');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    mkdir(UPLOAD_DIR . 'employees/', 0755, true);
}

