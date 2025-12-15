# Salary Sheet Management System

A comprehensive, fully responsive Salary Management System built with PHP 7+, MySQL, HTML5, CSS3, and JavaScript.

## Features

### Core Features
- ✅ Secure user authentication with role-based access (Admin/HR)
- ✅ Password encryption using bcrypt
- ✅ Session management with timeout
- ✅ Comprehensive dashboard with statistics and charts
- ✅ Complete employee management (Add, Edit, View, Delete)
- ✅ Salary structure management
- ✅ Monthly salary processing
- ✅ Salary slip generation (PDF-ready)
- ✅ Salary history and records
- ✅ Payment tracking
- ✅ Monthly and yearly reports
- ✅ Export functionality (CSV, Excel)
- ✅ Responsive design (Mobile, Tablet, Desktop)

### Security Features
- SQL injection prevention (PDO prepared statements)
- XSS protection (output escaping)
- CSRF token protection
- File upload validation
- Session security headers
- Password policy enforcement

## Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE salary_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p salary_management < sql/database.sql
```

Or use phpMyAdmin to import `sql/database.sql`

### 2. Configuration

1. Open `includes/config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'salary_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Update the application URL:
```php
define('APP_URL', 'http://localhost/salary-admin');
```

3. Set appropriate file permissions:
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 644 includes/config.php
```

### 3. Default Login Credentials

- **Username:** admin
- **Password:** admin123

**⚠️ IMPORTANT:** Change the default password immediately after first login!

### 4. Web Server Configuration

#### Apache (.htaccess)
The system includes a `.htaccess` file for URL rewriting and security.

#### Nginx
Add the following to your Nginx configuration:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Directory Structure

```
salary-admin/
├── api/                  # API endpoints
│   ├── employees.php
│   ├── salary.php
│   └── reports.php
├── assets/               # Static assets
│   ├── css/
│   │   ├── style.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   ├── employees.js
│   │   └── salary.js
│   └── img/
├── employees/            # Employee management pages
│   ├── list.php
│   ├── add.php
│   └── view.php
├── includes/             # Core PHP files
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── reports/              # Report pages
│   ├── monthly.php
│   └── yearly.php
├── salary/               # Salary processing pages
│   ├── process.php
│   ├── history.php
│   └── slips.php
├── sql/                  # Database schema
│   └── database.sql
├── uploads/              # Uploaded files (auto-created)
│   └── employees/
├── logs/                 # Error logs (auto-created)
├── index.php             # Login page
├── dashboard.php         # Dashboard
├── logout.php            # Logout handler
└── README.md
```

## Usage Guide

### Adding Employees

1. Navigate to **Employees > Add New Employee**
2. Fill in all required fields:
   - Employee Code (auto-generated)
   - Personal Information
   - Address Details
   - Bank Details
   - Salary Structure
3. Click **Add Employee**

### Processing Salary

1. Go to **Salary Processing**
2. Select the month and year
3. Optionally filter by department
4. Click **Preview** to review salaries
5. Click **Generate Salary** to create salary records

### Approving and Paying Salaries

1. Go to **Salary History**
2. Review pending salaries
3. Click **Approve** for each salary
4. After approval, click **Mark Paid** to record payment
5. Enter payment details (date, method, transaction ID)

### Generating Reports

1. **Monthly Report:**
   - Go to **Reports > Monthly Report**
   - Select month and year
   - View department-wise summary
   - Export to Excel if needed

2. **Yearly Report:**
   - Go to **Reports > Yearly Report**
   - Select year
   - View employee-wise and monthly breakdown
   - Export to Excel if needed

### Viewing Salary Slips

1. Go to **Salary History**
2. Click **View Slip** for any salary record
3. Print or save as PDF from browser

## API Endpoints

### Employees API (`api/employees.php`)
- `action=create` - Create new employee
- `action=update` - Update employee
- `action=delete` - Delete employee
- `action=update_status` - Update employee status
- `action=generate_code` - Generate employee code
- `action=export` - Export employees to CSV

### Salary API (`api/salary.php`)
- `action=preview` - Preview salary generation
- `action=generate` - Generate monthly salaries
- `action=approve` - Approve salary
- `action=mark_paid` - Mark salary as paid
- `action=send_slip` - Send salary slip via email
- `action=stats` - Get salary statistics
- `action=export` - Export salary data

### Reports API (`api/reports.php`)
- `action=export_monthly` - Export monthly report
- `action=export_yearly` - Export yearly report

## Customization

### Changing Company Details

Update company information in the database:
```sql
UPDATE company_settings SET 
    company_name = 'Your Company Name',
    address = 'Your Address',
    city = 'Your City',
    state = 'Your State',
    email = 'your@email.com'
WHERE id = 1;
```

### Modifying Salary Components

Salary components can be customized in the `salary_components` table. Default components include:
- Basic Salary
- HRA (House Rent Allowance)
- DA (Dearness Allowance)
- TA (Travel Allowance)
- Medical Allowance
- Special Allowance
- Provident Fund
- Professional Tax
- Income Tax

### Styling

Modify `assets/css/style.css` for custom styling. The system uses CSS variables for easy theming:
```css
:root {
    --primary-color: #2c3e50;
    --primary-light: #3498db;
    --success-color: #27ae60;
    /* ... */
}
```

## Security Best Practices

1. **Change default credentials** immediately
2. **Use HTTPS** in production
3. **Set proper file permissions** (755 for directories, 644 for files)
4. **Disable error display** in production (`config.php`)
5. **Regular database backups**
6. **Keep PHP and MySQL updated**
7. **Use strong passwords** for database

## Troubleshooting

### Database Connection Error
- Check database credentials in `includes/config.php`
- Verify MySQL service is running
- Ensure database exists

### Permission Denied
- Check file permissions for `uploads/` and `logs/` directories
- Ensure web server has write permissions

### Session Issues
- Check PHP session configuration
- Verify session directory is writable
- Clear browser cookies

### Upload Errors
- Check `uploads/` directory permissions
- Verify `upload_max_filesize` in `php.ini`
- Check file type restrictions

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

This project is open source and available for use and modification.

## Support

For issues or questions, please check:
1. Error logs in `logs/error.log`
2. Browser console for JavaScript errors
3. PHP error logs

## Future Enhancements

Potential features for future versions:
- Email notifications
- SMS alerts
- Advanced analytics
- Multi-currency support
- Tax calculation automation
- Integration with accounting software
- Mobile app
- API for third-party integrations

## Credits

Built with modern web technologies:
- PHP 7+
- MySQL
- Vanilla JavaScript
- Chart.js for visualizations
- Modern CSS (Grid, Flexbox)

---

**Note:** This is a production-ready system. Make sure to:
- Test thoroughly before deploying
- Backup database regularly
- Monitor error logs
- Keep system updated
- Follow security best practices

