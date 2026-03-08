# Pre-Deployment Checklist

## Before Uploading to InfinityFree

### 1. Database Configuration
- [ ] Copy `config/database.php` to `config/database.local.php` (for local backup)
- [ ] Update `config/database.php` with InfinityFree credentials
- [ ] Test database connection

### 2. File Preparation
- [ ] Remove sensitive files:
  - [ ] `fix_password.php`
  - [ ] `migrate.php`
  - [ ] `fix_existing_clinic.sql`
  - [ ] `clinic/treatment/fix_tables.php`
  - [ ] `*.bak` files
- [ ] Verify all required files are present:
  - [ ] All `/admin/` files
  - [ ] All `/api/` files
  - [ ] All `/clinic/` files
  - [ ] All `/config/` files
  - [ ] All `/core/` files
  - [ ] `TCPDF-main/` folder
  - [ ] `setup.php`

### 3. Security
- [ ] Change default admin password in setup
- [ ] Review `.htaccess` rules
- [ ] Ensure no database passwords in comments

### 4. Upload Methods
Choose one:
- [ ] FTP Upload (recommended for full control)
- [ ] File Manager Upload (easier, might timeout on large files)
- [ ] ZIP upload then extract on server

### 5. Post-Upload
- [ ] Visit `your-site.com/setup.php`
- [ ] Login with admin credentials
- [ ] Create test clinic
- [ ] Verify all features work:
  - [ ] Patient management
  - [ ] Prescription creation
  - [ ] Treatment plans
  - [ ] Work done records
  - [ ] Reports generation
  - [ ] PDF exports
  - [ ] Certificate printing

### 6. Production Readiness
- [ ] Delete or rename `setup.php` to prevent re-runs
- [ ] Set up database backup schedule
- [ ] Test from different devices
- [ ] Check mobile responsiveness
- [ ] Verify logo uploads work
- [ ] Test PDF generation

## InfinityFree Specific Notes

### File Size Limits
- Max upload: 50 MB per file
- Total storage: 5 GB
- Bandwidth: Unlimited (with fair use)

### Database Limits
- MySQL 5.7
- Max database size: 1 GB
- Max connections: 10 simultaneous

### PHP Version
- PHP 7.4 or 8.x
- Check your code compatibility

### Known Limitations
- ❌ No cron jobs (can use external services)
- ❌ No email sending without external SMTP
- ❌ Hit limits during peak hours
- ✅ Free SSL certificate
- ✅ PHP OPcache enabled

## Troubleshooting Quick Reference

| Problem | Solution |
|---------|----------|
| 502/504 Errors | Wait a moment, hit limits - optimize queries |
| File upload fails | Check size (<50MB), use FTP for large files |
| Database error | Verify credentials, run setup.php |
| PDF not generating | Ensure TCPDF folder uploaded completely |
| Slow loading | Optimize images, enable caching |
| Can't login | Check database connection, verify users table |

## Emergency Rollback

If something goes wrong:
1. Download backup via phpMyAdmin
2. Restore database from backup
3. Re-upload files from Git repository
4. Run setup.php again

## Support Contacts

- **InfinityFree Forum**: https://forum.infinityfree.net/
- **Documentation**: Check INFINITYFREE_DEPLOYMENT.md
- **phpMyAdmin**: Available in control panel

---

**Last Updated**: March 8, 2026
**Deployment Target**: InfinityFree
**Application**: Clinic Management System
