# Installation Guide

## Quick Start (5 Minutes)

### Option 1: Automatic Installation

1. **Extract files** to your web directory:
   ```
   /opt/lampp/htdocs/clinic_management/
   ```

2. **Import database**:
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "Import" tab
   - Choose `database_schema.sql`
   - Click "Go"

3. **Done!** Access the system:
   ```
   http://localhost/clinic_management/public/login.php
   ```

### Option 2: Manual Installation

#### Step 1: Database Setup

Using MySQL command line:
```bash
cd /opt/lampp/htdocs/clinic_management
mysql -u root -p < database_schema.sql
```

Or create manually:
```sql
CREATE DATABASE clinic_management_master;
-- Then copy/paste SQL from database_schema.sql
```

#### Step 2: File Permissions

```bash
cd /opt/lampp/htdocs/clinic_management
chmod 755 -R .
chmod 777 -R assets/uploads
mkdir -p assets/uploads/logos
chmod 777 assets/uploads/logos
```

#### Step 3: Test Installation

Visit: http://localhost/clinic_management/public/login.php

Default credentials:
- **Admin:** admin@clinic.com / admin123
- **Demo Clinic:** demo@clinic.com / clinic123

## Migrating Existing Data

If you have an existing clinic database:

1. **Backup your current database**
   ```bash
   mysqldump -u root -p your_old_database > backup.sql
   ```

2. **Edit migration configuration**
   
   Open `migrate.php` and update:
   ```php
   define('OLD_DB_NAME', 'your_old_database_name');
   define('NEW_CLINIC_NAME', 'Your Clinic Name');
   define('NEW_CLINIC_EMAIL', 'your@email.com');
   ```

3. **Run migration**
   
   Visit: http://localhost/clinic_management/migrate.php

4. **Verify data migration**
   
   Login and check all patients/data transferred correctly

5. **Delete migration file**
   ```bash
   rm migrate.php
   ```

## Configuration

### Database Connection

Edit `config/database.php`:
```php
private $host = 'localhost';
private $user = 'root';
private $pass = 'your_password';
private $masterDb = 'clinic_management_master';
```

### Base URL

Edit `config/constants.php`:
```php
define('BASE_URL', '/clinic_management');
```

For subdomain: `define('BASE_URL', '');`

### Session Timeout

```php
define('SESSION_TIMEOUT', 1800); // 30 minutes
```

## Troubleshooting

### "Connection failed" error
- Check MySQL is running: `sudo /opt/lampp/lampp start`
- Verify credentials in `config/database.php`
- Check database exists

### "Permission denied" uploading logo
```bash
chmod 777 -R assets/uploads/
```

### Page shows PHP code instead of rendering
- Check Apache is running
- Ensure mod_php is enabled
- Verify .htaccess if using

### Session timeout too quickly
- Increase in `config/constants.php`
- Check PHP session.gc_maxlifetime

### Can't login
- Clear browser cookies
- Check database has admin user
- Verify password hash format

## Production Deployment

### Security Checklist

1. ✅ Change all default passwords
2. ✅ Enable HTTPS (SSL certificate)
3. ✅ Update `session.cookie_secure` to 1
4. ✅ Set strong MySQL password
5. ✅ Disable error display in production
6. ✅ Enable firewall
7. ✅ Regular database backups
8. ✅ Keep PHP/MySQL updated
9. ✅ Remove migrate.php after use
10. ✅ Set proper file permissions (755/644)

### PHP Configuration

Edit `php.ini`:
```ini
; Production settings
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1

; Upload limits
upload_max_filesize = 5M
post_max_size = 10M
```

### Apache Configuration

For production, add to `.htaccess`:
```apache
# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"

# Hide PHP version
Header unset X-Powered-By

# Prevent directory listing
Options -Indexes

# Block access to sensitive files
<FilesMatch "(\.env|\.git|\.sql|config\.php)">
  Order allow,deny
  Deny from all
</FilesMatch>
```

## Backup Strategy

### Automated Daily Backup

Create script `backup.sh`:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"

# Backup master database
mysqldump -u root -p clinic_management_master > $BACKUP_DIR/master_$DATE.sql

# Backup all clinic databases
for db in $(mysql -u root -p -e "SHOW DATABASES LIKE 'clinic_%'" -s --skip-column-names); do
    mysqldump -u root -p $db > $BACKUP_DIR/${db}_$DATE.sql
done

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /path/to/backup.sh
```

### Manual Backup

```bash
# Export all databases
mysqldump -u root -p --all-databases > all_databases_backup.sql

# Export files
tar -czf files_backup.tar.gz /opt/lampp/htdocs/clinic_management
```

## Performance Optimization

### Enable OPcache

Edit `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### MySQL Optimization

```sql
-- Add indexes for better performance
ALTER TABLE clinic_management_master.user_activity_logs 
ADD INDEX idx_created (created_at);

-- Optimize tables monthly
OPTIMIZE TABLE patients;
OPTIMIZE TABLE prescriptions;
```

### Enable Compression

Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript
</IfModule>
```

## Monitoring

### Setup Error Logging

```php
// Add to config/constants.php
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');
```

### Monitor Disk Space

```bash
# Check uploads folder size
du -sh /opt/lampp/htdocs/clinic_management/assets/uploads/
```

### Database Size Monitoring

```sql
SELECT 
  table_schema AS 'Database',
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema LIKE 'clinic_%'
GROUP BY table_schema;
```

## Support

### Getting Help

1. Check [README.md](README.md) documentation
2. Review error logs
3. Check file permissions
4. Verify database connection
5. Test with default credentials

### Common Issues & Solutions

**Issue:** Login page redirects in loop
- Clear browser cookies and cache
- Check BASE_URL in constants.php

**Issue:** Logo not displaying
- Verify file path is correct
- Check file permissions
- Ensure uploads folder exists

**Issue:** Slow performance
- Enable OPcache
- Add database indexes
- Optimize large tables

## Updates

### Updating the System

1. **Backup everything** first
2. Download new version
3. Replace files (keep config/)
4. Run any update SQL scripts
5. Test thoroughly
6. Clear cache

### Database Updates

When schema changes:
```sql
-- Example: Adding new column
ALTER TABLE clinic_xxx.patients 
ADD COLUMN emergency_contact VARCHAR(20) AFTER contact_number;
```

## Development

### Local Development Setup

1. Install XAMPP/LAMPP
2. Clone repository
3. Import database
4. Configure database.php
5. Start development

### Git Ignore

Create `.gitignore`:
```
# Config files with sensitive data
config/database.php

# Uploads
assets/uploads/*
!assets/uploads/.gitkeep

# Logs
logs/
*.log

# OS files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/

# Migration marker
.migrated
```

---

**Need Help?** Contact your system administrator or check the documentation.

**Version:** 2.0.0
**Last Updated:** March 4, 2026
