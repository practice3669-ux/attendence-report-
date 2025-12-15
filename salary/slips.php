<?php
/**
 * Salary Slip Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: history.php');
    exit();
}

// Get salary transaction with employee details
$stmt = $db->prepare("
    SELECT st.*, e.*, d.name as department_name,
           cs.company_name, cs.address as company_address, cs.city as company_city,
           cs.state as company_state, cs.pincode as company_pincode,
           cs.phone as company_phone, cs.email as company_email
    FROM salary_transactions st
    JOIN employees e ON st.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN company_settings cs ON 1=1
    WHERE st.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$salary = $stmt->fetch();

if (!$salary) {
    header('Location: history.php');
    exit();
}

$pageTitle = 'Salary Slip';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .salary-slip { box-shadow: none; border: none; }
        }
        .salary-slip {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: var(--shadow-lg);
        }
        .slip-header {
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .slip-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .slip-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted var(--border-color);
        }
        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
        }
        .info-value {
            font-weight: 600;
        }
        .salary-breakdown {
            margin: 30px 0;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
        }
        .breakdown-table th,
        .breakdown-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .breakdown-table th {
            background: var(--bg-color);
            font-weight: 600;
            color: var(--primary-color);
        }
        .breakdown-table .total-row {
            background: var(--bg-color);
            font-weight: 600;
            font-size: 16px;
        }
        .breakdown-table .total-row td {
            border-top: 2px solid var(--primary-color);
            padding-top: 15px;
        }
        .net-salary {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .net-salary h2 {
            margin-bottom: 10px;
        }
        .net-salary .amount {
            font-size: 32px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="salary-slip">
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <a href="history.php" class="btn btn-outline">Back</a>
        </div>

        <!-- Header -->
        <div class="slip-header">
            <h1><?php echo htmlspecialchars($salary['company_name'] ?? APP_NAME); ?></h1>
            <p><?php 
                echo htmlspecialchars($salary['company_address'] ?? '');
                if ($salary['company_city']) echo ', ' . htmlspecialchars($salary['company_city']);
                if ($salary['company_state']) echo ', ' . htmlspecialchars($salary['company_state']);
                if ($salary['company_pincode']) echo ' - ' . htmlspecialchars($salary['company_pincode']);
            ?></p>
            <h2 style="margin-top: 20px;">SALARY SLIP</h2>
            <p><?php echo getMonthName($salary['month']) . ' ' . $salary['year']; ?></p>
        </div>

        <!-- Employee & Company Info -->
        <div class="slip-info">
            <div class="info-section">
                <h3>Employee Information</h3>
                <div class="info-row">
                    <span class="info-label">Employee Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($salary['employee_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($salary['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Designation:</span>
                    <span class="info-value"><?php echo htmlspecialchars($salary['designation'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value"><?php echo htmlspecialchars($salary['department_name'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Join Date:</span>
                    <span class="info-value"><?php echo formatDate($salary['join_date']); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h3>Payment Details</h3>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value"><?php echo $salary['payment_date'] ? formatDate($salary['payment_date']) : 'Pending'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $salary['payment_method'] ?? 'Bank Transfer')); ?></span>
                </div>
                <?php if ($salary['transaction_id']): ?>
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($salary['transaction_id']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="badge badge-<?php echo $salary['status']; ?>">
                            <?php echo ucfirst($salary['status']); ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Salary Breakdown -->
        <div class="salary-breakdown">
            <h3 style="color: var(--primary-color); margin-bottom: 15px;">Earnings</h3>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th style="text-align: right;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['basic_salary']); ?></td>
                    </tr>
                    <?php if ($salary['hra'] > 0): ?>
                    <tr>
                        <td>HRA</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['hra']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['da'] > 0): ?>
                    <tr>
                        <td>DA</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['da']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['ta'] > 0): ?>
                    <tr>
                        <td>TA</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['ta']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['medical_allowance'] > 0): ?>
                    <tr>
                        <td>Medical Allowance</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['medical_allowance']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['special_allowance'] > 0): ?>
                    <tr>
                        <td>Special Allowance</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['special_allowance']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['bonus'] > 0): ?>
                    <tr>
                        <td>Bonus</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['bonus']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['ot_amount'] > 0): ?>
                    <tr>
                        <td>Overtime (<?php echo $salary['ot_hours']; ?> hrs)</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['ot_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td><strong>Total Earnings</strong></td>
                        <td style="text-align: right;"><strong><?php echo formatCurrency($salary['total_earnings']); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: var(--primary-color); margin: 30px 0 15px;">Deductions</h3>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th style="text-align: right;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($salary['provident_fund'] > 0): ?>
                    <tr>
                        <td>Provident Fund</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['provident_fund']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['professional_tax'] > 0): ?>
                    <tr>
                        <td>Professional Tax</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['professional_tax']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['income_tax'] > 0): ?>
                    <tr>
                        <td>Income Tax</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['income_tax']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($salary['other_deductions'] > 0): ?>
                    <tr>
                        <td>Other Deductions</td>
                        <td style="text-align: right;"><?php echo formatCurrency($salary['other_deductions']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td><strong>Total Deductions</strong></td>
                        <td style="text-align: right;"><strong><?php echo formatCurrency($salary['total_deductions']); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Net Salary -->
        <div class="net-salary">
            <h2>Net Salary</h2>
            <div class="amount"><?php echo formatCurrency($salary['net_salary']); ?></div>
        </div>

        <div style="margin-top: 40px; text-align: center; color: var(--text-secondary); font-size: 12px;">
            <p>This is a computer-generated document and does not require a signature.</p>
            <p>Generated on <?php echo date(DISPLAY_DATETIME_FORMAT); ?></p>
        </div>
    </div>
</body>
</html>

