# Clinic Management System v2.0

A comprehensive, secure, and modern clinic management system with multi-clinic support, session-based authentication, and professional UI/UX.

## 🚀 Features

### Core Features
- ✅ **Multi-Clinic Support** - Each clinic gets its own isolated database
- ✅ **Secure Authentication** - Bcrypt password hashing with session management
- ✅ **Role-Based Access Control** - Admin and Clinic user roles
- ✅ **Dynamic Branding** - Each clinic can have its own logo
- ✅ **Professional UI** - Modern, responsive design with Tailwind CSS
- ✅ **Activity Logging** - Track all user actions
- ✅ **Secure Sessions** - Auto-regeneration and timeout handling

### Admin Panel Features
- Manage multiple clinics
- Create/activate/deactivate clinics
- User management
- Activity monitoring
- System-wide statistics

### Clinic Panel Features
- Patient management
- Prescription tracking
- Treatment planning
- Work done records
- Medicine inventory
- Expense tracking
- Revenue reports
- Dynamic clinic logo in header

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/LAMPP (recommended for development)

## 🛠️ Installation

### Quick Installation (Recommended for phpMyAdmin Users)

**Option 1: Browser-Based Setup** ⭐
1. Open in browser: `http://localhost/clinic_management/setup.php`
2. Click "Install Database Now"
3. Done in 10 seconds!

**Option 2: phpMyAdmin Manual Import**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Select `database_schema.sql`
4. Click "Go"

**Option 3: MySQL Command Line** (Advanced)
```bash
cd /opt/lampp/htdocs/clinic_management
mysql -u root -p < database_schema.sql
```

📘 **See [QUICKSTART.md](QUICKSTART.md) for detailed step-by-step guide**

### Step 1: Files Setup

Place all files in your web server directory:
```bash
/opt/lampp/htdocs/clinic_management/
```

### Step 2: Import Database

Use one of the three methods above (Browser-based recommended)

### Step 3: Configure Database Connection

Edit `config/database.php` if needed:
```php
private $host = 'localhost';
private $user = 'root';
private $pass = '';
private $masterDb = 'clinic_management_master';
```

### Step 4: Set Permissions

```bash
chmod 755 -R /opt/lampp/htdocs/clinic_management/
chmod 777 -R /opt/lampp/htdocs/clinic_management/assets/uploads/
```

### Step 5: Access the System

Open your browser and navigate to:
```
http://localhost/clinic_management/public/login.php
```

Or use the setup wizard (first time only):
```
http://localhost/clinic_management/setup.php
```

## 🔐 Default Credentials

### Admin Account
- **Email:** admin@clinic.com
- **Password:** admin123

### Demo Clinic Account
- **Email:** demo@clinic.com
- **Password:** clinic123

**⚠️ IMPORTANT: Change these passwords after first login!**

## 📁 Folder Structure

```
clinic_management/
├── admin/                  # Admin panel files
│   ├── index.php          # Admin dashboard
│   ├── clinics.php        # Clinic management
│   └── ...
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── uploads/           # Uploaded files (logos, docs)
├── clinic/                 # Clinic panel files
│   ├── dashboard.php      # Clinic dashboard
│   ├── patients/          # Patient management
│   ├── prescriptions/     # Prescription management
│   └── ...
├── config/                 # Configuration files
│   ├── database.php       # Database connection
│   └── constants.php      # App constants
├── core/                   # Core classes
│   ├── Auth.php           # Authentication handler
│   ├── Session.php        # Session management
│   └── Database.php       # Database wrapper
├── public/                 # Public access files
│   ├── login.php          # Login page
│   ├── logout.php         # Logout handler
│   └── unauthorized.php   # Access denied page
├── TCPDF-main/            # PDF library
└── database_schema.sql    # Database schema
```

## 🎨 UI/UX Features

### Professional Design
- **Color Scheme:** Indigo/Purple gradient with medical theme
- **Typography:** Inter font family for modern look
- **Icons:** Font Awesome 6 icons
- **Responsive:** Mobile-first design with Tailwind CSS
- **Animations:** Smooth transitions and hover effects

### Dashboard Features
- Real-time statistics cards
- Recent activity feed
- Quick action buttons
- Professional charts and graphs
- Color-coded status indicators

### Header with Dynamic Logo
- Each clinic's logo appears in the header
- Fallback to gradient with first letter
- Professional branding throughout

## 🔒 Security Features

### Authentication
- Bcrypt password hashing (cost factor 12)
- Secure session management
- Session timeout after 30 minutes
- Automatic session regeneration
- Login activity logging

### SQL Injection Prevention
- Prepared statements throughout
- Parameterized queries
- Input validation and sanitization

### Access Control
- Role-based permissions (Admin/Clinic)
- Route protection middleware
- Database isolation per clinic

### Session Security
- HTTP-only cookies
- Session fixation prevention
- CSRF protection ready
- Secure session storage

## 🏥 Clinic Database Structure

Each clinic gets an isolated database with these tables:
- `patients` - Patient records
- `prescriptions` - Prescription history
- `treatments` - Treatment plans
- `medicine` - Medicine inventory
- `expenses` - Expense tracking
- `work_done` - Service types
- `patient_work_done` - Services rendered
- `appointments` - Appointment scheduling
- `payments` - Payment tracking

## 📊 Master Database Structure

- `clinics` - Clinic information
- `users` - All system users (Admin + Clinic)
- `user_activity_logs` - Activity tracking

## 🔧 Configuration

### Session Timeout
Edit in `config/constants.php`:
```php
define('SESSION_TIMEOUT', 1800); // 30 minutes
```

### Upload Limits
```php
define('MAX_LOGO_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
```

### Base URL
Edit in `config/constants.php`:
```php
define('BASE_URL', '/clinic_management');
```

## 🚀 Usage Guide

### For Admin

1. **Login** as admin
2. **Add Clinic** - Go to Clinics → Add New Clinic
3. **Upload Logo** - Optional clinic branding
4. **Share Credentials** - Send login details to clinic

### For Clinic Users

1. **Login** with clinic credentials
2. **View Dashboard** - See statistics and recent activity
3. **Manage Patients** - Add/edit patient records
4. **Create Prescriptions** - Generate prescriptions
5. **Track Payments** - Monitor revenue and pending payments
6. **View Reports** - Access analytics and insights

## 🐛 Troubleshooting

### Database Connection Issues
- Check MySQL service is running
- Verify credentials in `config/database.php`
- Ensure database exists

### Logo Upload Not Working
- Check folder permissions: `chmod 777 assets/uploads/logos/`
- Verify file size (max 2MB)
- Check file type (JPG/PNG only)

### Session Timeout Issues
- Increase timeout in `config/constants.php`
- Check PHP session configuration
- Verify session directory is writable

### Login Page Not Loading
- Check Apache/Nginx is running
- Verify file permissions
- Check error logs

## 📝 Development

### Adding New Features
1. Create feature files in appropriate directory
2. Use Auth::requireClinic() or Auth::requireAdmin()
3. Follow existing code structure
4. Use prepared statements for database queries

### Database Changes
1. Add migration SQL to `database_schema.sql`
2. Update relevant models/classes
3. Test thoroughly

## 🔄 Migration from Old System

1. **Backup** existing database
2. **Export** patient data
3. **Create Clinic** in new system
4. **Import Data** into new clinic database
5. **Verify** all data migrated correctly

## 📞 Support

For issues or questions:
- Check documentation
- Review error logs
- Contact system administrator

## 📄 License

Copyright © 2026. All rights reserved.

## 🙏 Credits

- **Tailwind CSS** - UI framework
- **Font Awesome** - Icons
- **TCPDF** - PDF generation
- **Inter Font** - Typography

---

## 🎯 Next Steps

After installation:

1. ✅ Change default admin password
2. ✅ Create your first clinic
3. ✅ Upload clinic logo
4. ✅ Add clinic users
5. ✅ Start managing patients

## 🔐 Security Recommendations

- Change all default passwords immediately
- Use HTTPS in production
- Enable firewall
- Regular database backups
- Keep PHP and MySQL updated
- Implement CSRF tokens
- Add rate limiting for login attempts
- Use strong password policy

---

**Version:** 2.0.0  
**Last Updated:** March 4, 2026  
**Status:** Production Ready ✅
