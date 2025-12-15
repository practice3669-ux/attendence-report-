-- Salary Sheet Management System Database Schema
-- PHP 7+ and MySQL Compatible

CREATE DATABASE IF NOT EXISTS salary_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE salary_management;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr') DEFAULT 'hr',
    email VARCHAR(100),
    full_name VARCHAR(100),
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    manager_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    designation VARCHAR(100),
    department_id INT,
    join_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    photo VARCHAR(255),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_ifsc VARCHAR(20),
    pan_number VARCHAR(20),
    aadhar_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_employee_code (employee_code),
    INDEX idx_email (email),
    INDEX idx_department (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salary components configuration
CREATE TABLE IF NOT EXISTS salary_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('earning', 'deduction') NOT NULL,
    is_taxable BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salary structures (templates for employees)
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    hra DECIMAL(10,2) DEFAULT 0,
    da DECIMAL(10,2) DEFAULT 0,
    ta DECIMAL(10,2) DEFAULT 0,
    medical_allowance DECIMAL(10,2) DEFAULT 0,
    special_allowance DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    other_allowances DECIMAL(10,2) DEFAULT 0,
    provident_fund DECIMAL(10,2) DEFAULT 0,
    professional_tax DECIMAL(10,2) DEFAULT 0,
    income_tax DECIMAL(10,2) DEFAULT 0,
    other_deductions DECIMAL(10,2) DEFAULT 0,
    ot_rate_per_hour DECIMAL(10,2) DEFAULT 0,
    effective_from DATE,
    effective_to DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee (employee_id),
    INDEX idx_effective_from (effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salary transactions (monthly salary records)
CREATE TABLE IF NOT EXISTS salary_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL CHECK (month BETWEEN 1 AND 12),
    year INT NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0,
    hra DECIMAL(10,2) DEFAULT 0,
    da DECIMAL(10,2) DEFAULT 0,
    ta DECIMAL(10,2) DEFAULT 0,
    medical_allowance DECIMAL(10,2) DEFAULT 0,
    special_allowance DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    other_allowances DECIMAL(10,2) DEFAULT 0,
    ot_hours DECIMAL(5,2) DEFAULT 0,
    ot_amount DECIMAL(10,2) DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    provident_fund DECIMAL(10,2) DEFAULT 0,
    professional_tax DECIMAL(10,2) DEFAULT 0,
    income_tax DECIMAL(10,2) DEFAULT 0,
    other_deductions DECIMAL(10,2) DEFAULT 0,
    total_deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    payment_date DATE,
    payment_method ENUM('bank_transfer', 'cash', 'cheque') DEFAULT 'bank_transfer',
    transaction_id VARCHAR(100),
    notes TEXT,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    UNIQUE KEY unique_salary (employee_id, month, year),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_month_year (month, year),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salary earnings breakdown (for detailed salary slips)
CREATE TABLE IF NOT EXISTS salary_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    salary_id INT NOT NULL,
    component_id INT,
    component_name VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (salary_id) REFERENCES salary_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE SET NULL,
    INDEX idx_salary (salary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salary deductions breakdown
CREATE TABLE IF NOT EXISTS salary_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    salary_id INT NOT NULL,
    component_id INT,
    component_name VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (salary_id) REFERENCES salary_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE SET NULL,
    INDEX idx_salary (salary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments tracking
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    salary_id INT NOT NULL,
    payment_method ENUM('bank_transfer', 'cash', 'cheque') NOT NULL,
    transaction_id VARCHAR(100),
    cheque_number VARCHAR(50),
    cheque_date DATE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    payment_date DATE,
    notes TEXT,
    processed_by INT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (salary_id) REFERENCES salary_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_salary (salary_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company settings
CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(200),
    pan_number VARCHAR(20),
    gst_number VARCHAR(20),
    tan_number VARCHAR(20),
    logo VARCHAR(255),
    salary_payment_day INT DEFAULT 1 CHECK (salary_payment_day BETWEEN 1 AND 31),
    financial_year_start_month INT DEFAULT 4 CHECK (financial_year_start_month BETWEEN 1 AND 12),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax configuration
CREATE TABLE IF NOT EXISTS tax_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_type ENUM('income_tax', 'professional_tax', 'pf', 'esi') NOT NULL,
    min_amount DECIMAL(10,2) DEFAULT 0,
    max_amount DECIMAL(10,2),
    percentage DECIMAL(5,2) DEFAULT 0,
    fixed_amount DECIMAL(10,2) DEFAULT 0,
    effective_from DATE,
    effective_to DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tax_type (tax_type),
    INDEX idx_effective (effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Holiday calendar
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(200) NOT NULL,
    holiday_type ENUM('national', 'regional', 'company') DEFAULT 'national',
    is_recurring BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_holiday (holiday_date, holiday_name),
    INDEX idx_date (holiday_date),
    INDEX idx_type (holiday_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('salary_due', 'payment_confirmed', 'salary_credited', 'system_alert', 'error') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (support password: Password123)
-- IMPORTANT: If login fails, run fix_admin.php to set a fresh hash
INSERT INTO users (username, password, role, email, full_name, status) VALUES
('admin', '$2y$10$q.m9jiWvH0I1GtIsfRSAjeiPPYU.GOCa/MEGLjmVnTQ3kLBXjg5eW', 'admin', 'admin@company.com', 'System Administrator', 'active');

-- Insert default salary components
INSERT INTO salary_components (name, type, is_taxable, is_default, display_order) VALUES
('Basic Salary', 'earning', TRUE, TRUE, 1),
('HRA', 'earning', TRUE, TRUE, 2),
('DA', 'earning', TRUE, TRUE, 3),
('TA', 'earning', FALSE, TRUE, 4),
('Medical Allowance', 'earning', FALSE, TRUE, 5),
('Special Allowance', 'earning', TRUE, TRUE, 6),
('Bonus', 'earning', TRUE, FALSE, 7),
('Overtime', 'earning', TRUE, FALSE, 8),
('Provident Fund', 'deduction', FALSE, TRUE, 9),
('Professional Tax', 'deduction', FALSE, TRUE, 10),
('Income Tax', 'deduction', FALSE, TRUE, 11),
('ESI', 'deduction', FALSE, FALSE, 12);

-- Insert default departments
INSERT INTO departments (name, description) VALUES
('HR', 'Human Resources Department'),
('IT', 'Information Technology Department'),
('Finance', 'Finance and Accounts Department'),
('Sales', 'Sales and Marketing Department'),
('Operations', 'Operations Department');

-- Insert default company settings
INSERT INTO company_settings (company_name, address, city, state, pincode, email, phone, salary_payment_day) VALUES
('Your Company Name', 'Company Address', 'City', 'State', '123456', 'info@company.com', '+91-1234567890', 1);

