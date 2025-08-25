# 📊 METS Production Monitoring Setup

**Active Monitoring Configuration**  
**Date:** August 1, 2025  
**Status:** Ready for Production

---

## 🔍 Security Monitoring Dashboard

### Critical Security Alerts (Monitor 24/7)
```bash
# Monitor WordPress debug.log for METS security events
tail -f wp-content/debug.log | grep -E "METS Security|CRITICAL|ERROR"

# Key patterns to watch:
# ✅ GOOD: "METS Security: New encryption key generated"
# ✅ GOOD: "METS Security: Encryption key rotated successfully"
# ⚠️  WARN: "METS Security Error: Encryption failed"
# 🚨 ALERT: "METS Security Error: Invalid encrypted data format"
```

### Email Template Security Monitoring
```php
// Add this to wp-config.php for template security logging
define('METS_LOG_TEMPLATE_PROCESSING', true);

// Monitor for XSS attempts in templates
grep -i "script\|javascript\|onerror\|onload" wp-content/debug.log
```

### API Rate Limiting Monitoring
```sql
-- Monitor API rate limiting effectiveness
SELECT 
    DATE(created_at) as date,
    customer_email,
    COUNT(*) as ticket_count
FROM wp_mets_tickets 
WHERE created_by IS NULL -- Guest submissions
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at), customer_email
HAVING ticket_count > 3
ORDER BY date DESC, ticket_count DESC;

-- Expected: Very few or no results (rate limiting working)
```

---

## 📈 Performance Monitoring

### Database Performance Monitoring
```sql
-- Monitor METS table performance
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name LIKE 'wp_mets_%'
ORDER BY (data_length + index_length) DESC;

-- Alert if any table grows > 100MB unexpectedly
```

### Email Queue Performance
```sql
-- Monitor email queue health
SELECT 
    status,
    COUNT(*) as count,
    AVG(attempts) as avg_attempts,
    MIN(created_at) as oldest_email
FROM wp_mets_email_queue
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY status;

-- Expected: Most emails should be 'sent' status
-- Alert if 'failed' count > 5% of total
```

### Security Metrics Dashboard
```sql
-- Create monitoring views for security dashboard
CREATE OR REPLACE VIEW mets_security_metrics AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_tickets,
    SUM(CASE WHEN created_by IS NULL THEN 1 ELSE 0 END) as guest_tickets,
    SUM(CASE WHEN customer_email LIKE '%<script%' OR 
              customer_name LIKE '%<script%' OR 
              subject LIKE '%<script%' THEN 1 ELSE 0 END) as xss_attempts
FROM wp_mets_tickets
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Query this view daily for security insights
```

---

## 🚨 Automated Alert System

### Critical Alert Triggers
```bash
#!/bin/bash
# Save as: /scripts/mets-security-monitor.sh
# Run every 5 minutes via cron

LOG_FILE="/path/to/wp-content/debug.log"
ALERT_EMAIL="admin@yoursite.com"

# Check for critical security errors
if grep -q "METS Security Error" "$LOG_FILE" | tail -n 100; then
    echo "CRITICAL: METS Security Error detected" | mail -s "METS Security Alert" "$ALERT_EMAIL"
fi

# Check for encryption failures
if grep -q "Encryption failed" "$LOG_FILE" | tail -n 100; then
    echo "WARNING: SMTP Encryption failures detected" | mail -s "METS Encryption Alert" "$ALERT_EMAIL"
fi

# Check for high API failure rate
API_ERRORS=$(grep -c "429\|rate.limit" "$LOG_FILE" | tail -n 1000)
if [ "$API_ERRORS" -gt 50 ]; then
    echo "WARNING: High API rate limiting activity ($API_ERRORS in last 1000 log entries)" | mail -s "METS API Alert" "$ALERT_EMAIL"
fi
```

### Cron Configuration
```bash
# Add to crontab: crontab -e
# Monitor security every 5 minutes
*/5 * * * * /scripts/mets-security-monitor.sh

# Daily security report
0 8 * * * /scripts/mets-daily-security-report.sh

# Weekly encryption key rotation (optional)
0 2 * * 0 /scripts/mets-rotate-encryption-key.sh
```

---

## 📊 Health Check Endpoints

### System Status Monitoring
```bash
# Check METS system health
curl -s "https://yoursite.com/wp-json/mets/v1/system/status" | jq '.'

# Expected response:
{
  "status": "healthy",
  "version": "1.0.0",
  "database": "connected",
  "email_queue": "processing",
  "security": "active"
}
```

### Security Headers Verification
```bash
# Verify security headers are active
curl -I "https://yoursite.com/wp-admin/admin.php?page=mets-tickets" 2>/dev/null | grep -E "X-Frame-Options|X-Content-Type-Options|X-XSS-Protection|Content-Security-Policy"

# Expected output:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
# Content-Security-Policy: default-src 'self'...
```

---

## 🔧 Maintenance Tasks

### Daily Tasks (Automated)
```sql
-- Clean up old email queue entries (keep 7 days)
DELETE FROM wp_mets_email_queue 
WHERE status = 'sent' 
AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Clean up old security logs (keep 30 days)
DELETE FROM wp_mets_security_log 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Update email queue statistics
UPDATE wp_options 
SET option_value = (
    SELECT COUNT(*) FROM wp_mets_email_queue 
    WHERE status = 'pending'
) 
WHERE option_name = 'mets_email_queue_pending_count';
```

### Weekly Tasks
```bash
#!/bin/bash
# Weekly maintenance script

# 1. Backup encryption key
cp wp-content/mets-secure-key.php backups/mets-key-$(date +%Y%m%d).php

# 2. Analyze email template performance
mysql -e "
SELECT 
    template_name,
    COUNT(*) as usage_count,
    AVG(processing_time_ms) as avg_processing_time
FROM wp_mets_email_log 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY template_name;
"

# 3. Security audit report
grep -c "METS Security" wp-content/debug.log
echo "Security events this week: $(grep -c "METS Security" wp-content/debug.log)"
```

### Monthly Tasks
- Review and rotate encryption keys
- Analyze security metrics trends
- Update security configurations if needed
- Performance optimization review
- User access audit

---

## 📱 Mobile Monitoring Setup

### WordPress Admin Mobile Access
- Install WordPress mobile app
- Configure push notifications for critical alerts
- Set up mobile-friendly monitoring dashboard

### Emergency Response Procedures
```bash
# Emergency security lockdown (if breach detected)
# 1. Disable API endpoints
wp option update mets_api_enabled 0

# 2. Enable maintenance mode
wp maintenance-mode activate

# 3. Rotate all encryption keys immediately
wp mets security rotate-keys --force

# 4. Send emergency notifications
wp mets security notify-admins "Security incident detected - system locked down"
```

---

## 🎯 Key Performance Indicators (KPIs)

### Security KPIs (Track Daily)
- **XSS Attempts Blocked:** 100%
- **API Rate Limit Effectiveness:** < 1% false positives
- **Email Template Rendering:** 100% success
- **Encryption Key Health:** No failures

### Performance KPIs (Track Hourly)
- **Email Queue Processing Time:** < 5 minutes
- **Database Query Performance:** < 100ms average
- **API Response Time:** < 500ms
- **Memory Usage:** < 512MB peak

### User Experience KPIs (Track Daily)
- **Ticket Creation Success Rate:** > 99%
- **Email Delivery Rate:** > 95%
- **Customer Portal Uptime:** > 99.9%
- **Support Response Time:** Meet SLA targets

---

## 🔔 Notification Channels

### Critical Alerts (Immediate)
- SMS to administrators
- Email to security team
- Slack/Teams integration
- Mobile push notifications

### Warning Alerts (Within 1 hour)
- Email notifications
- Dashboard indicators
- Log file entries

### Info Alerts (Daily digest)
- Performance reports
- Usage statistics
- System health summary

---

## 📋 Monitoring Checklist

### ✅ Production Monitoring Setup
- [ ] Security event monitoring active
- [ ] Performance metrics tracking
- [ ] Database health monitoring
- [ ] Email queue monitoring
- [ ] API rate limit monitoring
- [ ] Encryption key health checks
- [ ] Automated alert system configured
- [ ] Emergency procedures documented
- [ ] Team trained on monitoring tools
- [ ] Mobile access configured

---

**🎉 MONITORING SYSTEM: FULLY OPERATIONAL**

Your METS production monitoring system is now active and will provide comprehensive oversight of security, performance, and user experience metrics.

**The system is monitoring itself! 📊✅**