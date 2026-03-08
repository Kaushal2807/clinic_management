# 🚀 Quick Start Guide for phpMyAdmin Users

## ✅ Easy Installation (2 Minutes)

### Option 1: Browser-Based Setup (Recommended)

1. **Open the setup page in your browser:**
   ```
   http://localhost/clinic_management/setup.php
   ```

2. **Click "Install Database Now"**
   - It will automatically create all databases
   - Creates admin and demo accounts
   - Takes about 10 seconds

3. **Done! Click "Go to Login Page"**

4. **Login with default credentials:**
   - **Admin:** admin@clinic.com / admin123
   - **Demo Clinic:** demo@clinic.com / clinic123

---

### Option 2: Manual phpMyAdmin Import

If the browser setup doesn't work:

1. **Open phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Click "Import" tab** (top navigation)

3. **Click "Choose File"** and select:
   ```
   /opt/lampp/htdocs/clinic_management/database_schema.sql
   ```

4. **Scroll down and click "Go"**
   - Wait for import to complete (10-15 seconds)
   - You should see success message

5. **Access the system:**
   ```
   http://localhost/clinic_management/public/login.php
   ```

6. **Login:**
   - Admin: admin@clinic.com / admin123
   - Demo: demo@clinic.com / clinic123

---

## 🔍 Verifying Installation

### Check Databases Created

In phpMyAdmin, you should see:
- ✅ `clinic_management_master` (main system)
- ✅ `clinic_demo_dental` (demo clinic)

### Check Tables

Click on `clinic_management_master` database:
- ✅ `clinics` table
- ✅ `users` table  
- ✅ `user_activity_logs` table

---

## ⚠️ Troubleshooting

### Problem: "Connection failed"

**Solution:**
1. Make sure XAMPP/LAMPP MySQL is running
2. Check in XAMPP Control Panel - MySQL should be green/running
3. Restart MySQL if needed

### Problem: "File not found" in phpMyAdmin import

**Solution:**
1. Make sure you selected the correct file path
2. File should be: `/opt/lampp/htdocs/clinic_management/database_schema.sql`
3. Try using the browser-based setup instead

### Problem: Import shows errors

**Solution:**
1. Delete any partially created databases:
   - Go to phpMyAdmin
   - Click on database name
   - Click "Operations" tab
   - Click "Drop database"
2. Try import again with fresh start

### Problem: Login page shows blank/white screen

**Solution:**
1. Check Apache is running in XAMPP
2. Verify file permissions:
   ```bash
   chmod 755 -R /opt/lampp/htdocs/clinic_management
   ```
3. Check PHP error logs in XAMPP logs folder

---

## 📋 What Gets Installed

### Master Database
- System-wide clinic management
- Admin accounts
- Activity tracking

### Demo Clinic Database
- Sample patient structure
- Pre-configured work types
- Expense categories
- Ready to use immediately

### Default Accounts
- **System Admin** - Full system access
- **Demo Clinic** - Single clinic access

---

## 🎯 First Steps After Installation

1. **Change Default Passwords**
   - Login as admin
   - Go to Profile/Settings (when available)
   - Update password

2. **Create Your Clinic**
   - Login as admin
   - Go to "Clinics" page
   - Click "Add New Clinic"
   - Upload your logo

3. **Access Your Clinic**
   - Logout from admin
   - Login with clinic email
   - Start adding patients

---

## 🔐 Security Notes

⚠️ **Important:** Change these passwords immediately:
- admin@clinic.com (password: admin123)
- demo@clinic.com (password: clinic123)

After installation, you can delete these files for security:
- `setup.php`
- `migrate.php`
- `database_schema.sql` (keep backup elsewhere)

---

## 📞 Need Help?

**Common URLs:**
- Login: http://localhost/clinic_management/public/login.php
- Setup: http://localhost/clinic_management/setup.php
- phpMyAdmin: http://localhost/phpmyadmin

**Check if services are running:**
1. Open XAMPP Control Panel
2. MySQL should show "Running" with green background
3. Apache should show "Running" with green background

**View error logs:**
- XAMPP Control Panel → Logs button → PHP Error Log

---

## ✨ Features Overview

Once installed, you get:

### Admin Panel
- ✅ Manage multiple clinics
- ✅ Create/edit clinics
- ✅ Upload clinic logos
- ✅ View all activity

### Clinic Panel
- ✅ Patient management
- ✅ Prescriptions
- ✅ Treatments
- ✅ Expenses
- ✅ Reports
- ✅ Custom branding with logo

### Security
- ✅ Encrypted passwords
- ✅ Secure sessions
- ✅ Activity logging
- ✅ Role-based access

---

## 📱 Access URLs

After successful installation:

**Login Page:**
```
http://localhost/clinic_management/public/login.php
```

**Admin Dashboard** (after login as admin):
```
http://localhost/clinic_management/admin/index.php
```

**Clinic Dashboard** (after login as clinic):
```
http://localhost/clinic_management/clinic/dashboard.php
```

---

**That's it! You're ready to start using the system.** 🎉

For detailed documentation, see [README.md](README.md) and [INSTALLATION.md](INSTALLATION.md)
