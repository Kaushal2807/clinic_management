# InfinityFree Deployment Guide

## Step-by-Step Deployment Instructions

### 1. Sign Up for InfinityFree

1. Visit https://infinityfree.net
2. Click **"Sign Up"** and create an account
3. Verify your email address
4. Log in to the control panel

### 2. Create a Hosting Account

1. In the control panel, click **"Create Account"**
2. Choose your subdomain name (e.g., `yourclinic.great-site.net`)
   - Or use your custom domain if you have one
3. Leave the label field (optional)
4. Click **"Create Account"**
5. Wait 2-5 minutes for activation

### 3. Get Your Database Credentials

1. Click on **"Control Panel"** for your account
2. Go to **"MySQL Databases"** section
3. Create a new database:
   - Click **"Create Database"**
   - Note down these details:
     - **Database Name**: `epiz_XXXXX_clinicdb` (replace XXXXX)
     - **Database User**: `epiz_XXXXX`
     - **Database Password**: (create a strong password)
     - **MySQL Hostname**: `sqlXXX.infinityfreeapp.com`

### 4. Upload Your Files

#### Option A: Using File Manager (Easier)

1. In control panel, go to **"Online File Manager"**
2. Navigate to `/htdocs` folder
3. Delete any default files (like default.php)
4. Upload your project files:
   - Upload all files EXCEPT:
     - `assets/uploads/logos/*` (uploaded images)
     - `TCPDF-main/` (optional, if size issues)
     - `.git/` folder
     - Local database backups

#### Option B: Using FTP (Recommended)

1. Get FTP credentials from control panel → **"FTP Details"**
2. Download FileZilla: https://filezilla-project.org/
3. Connect to FTP:
   - Host: `ftpupload.net` (or from your FTP details)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
4. Navigate to `/htdocs/` on remote side
5. Upload all project files to `/htdocs/`

### 5. Configure Database Connection

1. After upload, edit `/htdocs/config/database.php`:
   - Use the File Manager's "Edit" option
   - Or download, edit locally, and re-upload

2. Update these lines:
```php
private $host = 'sqlXXX.infinityfreeapp.com';  // Your MySQL hostname
private $user = 'epiz_XXXXX';                  // Your database username
private $pass = 'YOUR_PASSWORD';               // Your database password
private $masterDb = 'epiz_XXXXX_clinicdb';     // Your database name
```

**Template file created**: Use `config/database.infinityfree.php` as reference

### 6. Setup the Database

1. Visit: `http://yourclinic.great-site.net/setup.php`
2. This will create the master database tables
3. You should see success message

### 7. Access Your Application

1. Visit: `http://yourclinic.great-site.net/`
2. You'll be redirected to login page
3. Default admin credentials:
   - Username: `admin`
   - Password: `admin123` (change immediately!)

### 8. Create Your First Clinic

1. Log in as admin
2. Go to **"Clinics"** section
3. Click **"Add New Clinic"**
4. Fill in clinic details and upload logo
5. System will automatically create clinic database with all tables

### 9. Important Post-Deployment Steps

#### Security:
- [ ] Change admin password immediately
- [ ] Remove or restrict access to `setup.php`
- [ ] Delete `config/database.infinityfree.php` template

#### Testing:
- [ ] Create a test clinic
- [ ] Add a patient
- [ ] Create prescription, treatment, work done records
- [ ] Test reports and exports
- [ ] Verify PDF generation works

#### Backups:
- [ ] Set up regular database backups via phpMyAdmin
- [ ] Download backups weekly

## Common Issues & Solutions

### Issue 1: "Connection failed" Error
**Solution**: Double-check database credentials in `config/database.php`

### Issue 2: "Table doesn't exist" Errors
**Solution**: Run `setup.php` to initialize master database

### Issue 3: Files not uploading
**Solution**: 
- Check file size limits (50MB max on InfinityFree)
- Compress TCPDF fonts if needed
- Upload in batches

### Issue 4: PDF generation fails
**Solution**: 
- Ensure TCPDF-main folder is uploaded completely
- Check file permissions (755 for folders, 644 for files)

### Issue 5: Slow performance
**Solution**:
- InfinityFree has hit limits (use caching)
- Optimize images (logos should be <500KB)
- Consider upgrading to paid hosting for production

## Performance Tips

1. **Enable PHP OPcache**: Already enabled on InfinityFree
2. **Optimize Images**: Compress clinic logos before upload
3. **Database Indexing**: Already implemented in schema
4. **Limit Records**: Use pagination (already implemented)

## Monitoring

- Check **Error Logs** in control panel → "Error Log"
- Monitor **Disk Usage** (keep under 5GB)
- Track **Bandwidth** (unlimited but monitored)

## Upgrading to Premium

If you need better performance:
- **iFastNet Premium**: $5/month (same provider)
- **Alternative**: Hostinger ($2.99/month)
- **Best value**: Oracle Cloud Always Free tier

## Support

- InfinityFree Forum: https://forum.infinityfree.net/
- Documentation: https://infinityfree.net/support/

## Backup Your Data

**Important**: Always backup before major changes!

1. Go to phpMyAdmin in control panel
2. Select your database
3. Click "Export" → "Go"
4. Store backup file safely

---

## Quick Reference

| Item | Value |
|------|-------|
| Control Panel | https://app.infinityfree.net |
| File Manager | Control Panel → Online File Manager |
| phpMyAdmin | Control Panel → MySQL Databases → phpMyAdmin |
| FTP Host | ftpupload.net |
| FTP Port | 21 |
| Upload Path | /htdocs/ |

---

**Need Help?** Check the InfinityFree forum or contact support.
