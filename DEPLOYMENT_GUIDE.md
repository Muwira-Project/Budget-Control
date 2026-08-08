# Production Deployment Guide - Budget Control (2026-08-08)

## Pre-Deployment Checklist

### Environment Verification
- [ ] Production server meets Laravel 13.23 + PHP 8.3 requirements
- [ ] Database: MySQL/PostgreSQL (SQLite not recommended for production)
- [ ] Web server: Nginx or Apache with proper rewrites
- [ ] PHP extensions: mbstring, ctype, fileinfo, openssl, pdo, tokenizer, xml
- [ ] Disk space: Minimum 2GB free for application + database
- [ ] Memory: Minimum 2GB RAM (4GB+ recommended)
- [ ] SSL certificate configured and valid
- [ ] Backup system in place (daily backups, minimum 7-day retention)

### Code Verification
- [ ] All code changes committed to git
- [ ] Git tags created for release version
- [ ] Code reviewed and approved
- [ ] Security audit completed
- [ ] Dependencies updated and locked (composer.lock)

### Database Preparation
- [ ] Production database created and accessible
- [ ] Database user has appropriate permissions
- [ ] Database backup completed before migration
- [ ] Migration tested in staging environment
- [ ] Rollback plan documented

---

## Step-by-Step Deployment

### 1. Prepare Production Server

```bash
# SSH into production server
ssh deploy@production-server

# Navigate to application directory
cd /var/www/budget-control

# Verify current directory
pwd  # Should output: /var/www/budget-control
```

### 2. Pull Latest Code

```bash
# Pull latest code from repository
git pull origin main

# Verify you''re on main branch
git branch -v

# Show latest commit
git log --oneline -1
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Verify installation
composer validate

# Check for security vulnerabilities (optional but recommended)
composer audit
```

### 4. Environment Configuration

```bash
# Copy environment file (if not exists)
cp .env.example .env

# Generate application key
php artisan key:generate

# Update .env with production values:
# - APP_ENV=production
# - APP_DEBUG=false
# - DB_CONNECTION=mysql (or your database)
# - DB_HOST=your-db-host
# - DB_DATABASE=budget_control_prod
# - DB_USERNAME=db_user
# - DB_PASSWORD=secure_password
# - CACHE_STORE=database (or redis)
# - QUEUE_CONNECTION=database
# - MAIL_MAILER=smtp (configure as needed)

# Verify .env configuration
php artisan env
```

### 5. Database Setup

```bash
# Create cache table for database cache driver
php artisan cache:table

# Run all migrations (including optimization indexes)
php artisan migrate --force

# Verify migrations completed
php artisan migrate:status

# Seed data (if needed - ONLY on fresh installation)
# php artisan db:seed --class=DummyDataSeeder

# Or for fresh installation:
# php artisan migrate:fresh --seed --force
```

### 6. Clear and Optimize Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify caches created
ls -la bootstrap/cache/
```

### 7. File Permissions

```bash
# Set correct ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data /var/www/budget-control

# Set directory permissions
sudo chmod -R 755 /var/www/budget-control
sudo chmod -R 755 /var/www/budget-control/storage
sudo chmod -R 755 /var/www/budget-control/bootstrap/cache

# Verify permissions
ls -la /var/www/budget-control/storage
ls -la /var/www/budget-control/bootstrap/cache
```

### 8. Web Server Configuration

#### For Nginx:
```nginx
server {
    listen 443 ssl http2;
    server_name budget-control.example.com;

    ssl_certificate /etc/ssl/certs/your-cert.crt;
    ssl_certificate_key /etc/ssl/private/your-key.key;

    root /var/www/budget-control/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Enable gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name budget-control.example.com;
    return 301 https://$server_name$request_uri;
}
```

#### For Apache:
```apache
<VirtualHost *:443>
    ServerName budget-control.example.com
    DocumentRoot /var/www/budget-control/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your-key.key

    <Directory /var/www/budget-control/public>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>

# HTTP to HTTPS redirect
<VirtualHost *:80>
    ServerName budget-control.example.com
    Redirect / https://budget-control.example.com/
</VirtualHost>
```

Reload web server:
```bash
# Nginx
sudo systemctl reload nginx

# Apache
sudo systemctl reload apache2
```

### 9. Verify Deployment

```bash
# Test application
php artisan tinker
# Type: exit (to exit tinker)

# Check database connection
php artisan db

# Test cache
php artisan cache:clear
php artisan cache:put test_key "test_value" 60

# Verify logs
tail -f storage/logs/laravel-*.log

# Test application URL
curl -I https://budget-control.example.com
```

### 10. Post-Deployment Verification

```bash
# Run health check
php artisan health

# Verify all tables exist
php artisan schema:table-summary

# Check indexes were created
php artisan db:show --counts

# Monitor system resources
free -h
df -h
top -b -n 1 | head -20
```

---

## Database Migration Details

### What Gets Migrated
✅ All tables with proper schema
✅ Foreign key relationships
✅ Performance indexes (15 single + 5 composite)
✅ Cache table for database caching

### Rollback Plan (If Needed)

```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific migration
php artisan migrate:rollback --target=2026_08_08_000001

# Reset all migrations (WARNING: deletes all data)
php artisan migrate:reset

# Verify rollback
php artisan migrate:status
```

---

## Performance Configuration

### Caching Strategy
```
Cache Store: database (production)
Cache TTL: 10 minutes for dashboard
Auto-invalidation: On realisasi create/update/delete
```

### Database Optimization
```
Indexes: 20 total (applied via migration)
Query logging: Enable in .env for monitoring
Connection pool: Configure based on traffic
```

### Queue Configuration (Optional)
```bash
# If using queues, configure in .env
QUEUE_CONNECTION=database

# Start queue worker
php artisan queue:work --max-tries=3 --timeout=90

# Or with supervisor (recommended for production):
# See supervisor configuration below
```

### Supervisor Configuration (for Queue Workers)

Create `/etc/supervisor/conf.d/budget-control.conf`:
```ini
[program:budget-control-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/budget-control/artisan queue:work database --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/budget-control/storage/logs/worker.log
```

Start supervisor:
```bash
sudo systemctl start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start budget-control-worker:*
```

---

## Monitoring & Maintenance

### Daily Monitoring

```bash
# Check application health
curl https://budget-control.example.com/health

# Monitor error logs
tail -f storage/logs/laravel-*.log

# Check system resources
df -h  # Disk space
free -h  # Memory
uptime  # System uptime

# Database backups
ls -lh /backups/budget-control/  # Verify daily backup
```

### Weekly Tasks

```bash
# Check for security updates
composer outdated

# Review error logs for patterns
grep ERROR storage/logs/laravel-*.log | wc -l

# Verify cache performance
php artisan cache:clear
# Monitor hit rates after clear
```

### Monthly Tasks

```bash
# Full security audit
composer audit

# Database optimization
php artisan db:optimize

# Update composer dependencies
composer update

# Review performance metrics
# Check dashboard load times
```

---

## Troubleshooting

### Application Not Loading
```bash
# Check error log
tail -100 storage/logs/laravel-*.log

# Verify .env configuration
php artisan env

# Test database connection
php artisan db

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### High Memory Usage
```bash
# Check running processes
ps aux | grep php

# Limit cache workers
php artisan queue:work --max-jobs=1000 --max-time=3600

# Enable query logging to find slow queries
# Set APP_DEBUG=true temporarily (then disable)
```

### Slow Dashboard Load
```bash
# Verify indexes exist
php artisan db:show --counts

# Check cache hit rate
redis-cli INFO stats  # If using Redis

# Monitor database queries
# Enable Laravel Telescope (optional)
php artisan telescope:publish
```

### Database Permissions Error
```bash
# Verify database user permissions
mysql -u db_user -p
SHOW GRANTS FOR 'db_user'@'localhost';

# Grant necessary permissions
GRANT ALL PRIVILEGES ON budget_control_prod.* TO 'db_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Rollback Procedure (Emergency)

If deployment fails and immediate rollback is needed:

```bash
# 1. Stop application traffic
# (Update DNS or disable in load balancer)

# 2. Rollback code to previous version
git checkout previous-tag
composer install --optimize-autoloader --no-dev

# 3. Rollback database (if migrations had issues)
php artisan migrate:rollback

# 4. Clear caches
php artisan cache:clear
php artisan config:clear

# 5. Restart web server
sudo systemctl restart nginx  # or apache2

# 6. Re-enable application traffic
# (Update DNS or enable in load balancer)

# 7. Verify application is working
curl -I https://budget-control.example.com
```

---

## Security Checklist

- [ ] .env file not in version control
- [ ] APP_DEBUG=false in production
- [ ] SSL certificate valid and renewed
- [ ] Database credentials secure (not in code)
- [ ] File permissions correct (storage, bootstrap)
- [ ] Backup encryption enabled
- [ ] Regular security updates scheduled
- [ ] WAF (Web Application Firewall) configured
- [ ] DDoS protection enabled
- [ ] Regular penetration testing scheduled

---

## Support & Escalation

### If Issues Occur During Deployment

1. **Immediate** (within 5 min):
   - Stop deployment
   - Check error logs
   - Assess impact

2. **Short-term** (within 30 min):
   - Implement rollback if needed
   - Document issue
   - Notify team

3. **Follow-up** (within 24h):
   - Root cause analysis
   - Fix implementation
   - Retry deployment

---

## Final Sign-Off

```
Deployed By: ________________
Deployment Date: 2026-08-08
Environment: Production
Status: [  ] Successful  [  ] Rollback Required

Verification Results:
- Application loads: [  ] Yes  [  ] No
- Dashboard responsive: [  ] Yes  [  ] No
- Database connected: [  ] Yes  [  ] No
- Cache working: [  ] Yes  [  ] No
- All tests passing: [  ] Yes  [  ] No

Notes:
_____________________________________________________________
_____________________________________________________________
```

---

**For questions or issues, contact the development team immediately.**
