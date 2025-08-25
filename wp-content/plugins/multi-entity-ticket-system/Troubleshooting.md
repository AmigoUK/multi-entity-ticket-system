# Multi-Entity Ticket System - Troubleshooting Guide

## Purpose
This document serves as a centralized location for documenting problems, diagnostic steps, root cause analysis, and solutions for issues encountered during the development and maintenance of the Multi-Entity Ticket System plugin.

## Common Issues and Solutions

### 1. Database Connection Issues

**Symptoms:**
- Error messages related to database queries
- Slow page loading in admin area
- Inability to create or update tickets

**Diagnostic Steps:**
1. Check WordPress database connection settings in wp-config.php
2. Verify database user permissions
3. Check if required tables exist using phpMyAdmin or similar tool
4. Review error logs for specific database error messages

**Root Cause Analysis:**
- Incorrect database credentials
- Missing database tables
- Insufficient database user privileges
- Database server performance issues

**Solution:**
1. Verify database credentials in wp-config.php
2. Run plugin activation to create missing tables
3. Grant necessary privileges to database user
4. Optimize database server performance

**Testing Results:**
- [ ] Database connection successful
- [ ] All plugin tables exist
- [ ] Queries execute without errors
- [ ] Page loading times within acceptable range

### 2. Permission and Access Issues

**Symptoms:**
- Users unable to access certain features
- "You do not have sufficient permissions" errors
- Features appearing/disappearing based on user role

**Diagnostic Steps:**
1. Check user roles and capabilities
2. Verify entity-specific permissions
3. Review role manager configuration
4. Test with different user accounts

**Root Cause Analysis:**
- Incorrect role assignments
- Missing capabilities
- Entity-specific permission conflicts
- Role manager configuration issues

**Solution:**
1. Assign correct roles to users
2. Add missing capabilities
3. Resolve entity-specific permission conflicts
4. Correct role manager configuration

**Testing Results:**
- [ ] Users can access appropriate features
- [ ] No permission errors
- [ ] Features appear correctly for each role
- [ ] Entity-specific permissions work as expected

### 3. Email Delivery Issues

**Symptoms:**
- Email notifications not being sent
- SMTP connection errors
- Delayed email delivery
- Emails marked as spam

**Diagnostic Steps:**
1. Check SMTP configuration settings
2. Verify SMTP server connectivity
3. Review email logs
4. Test with different email providers
5. Check spam folders

**Root Cause Analysis:**
- Incorrect SMTP settings
- Server firewall blocking SMTP ports
- Authentication failures
- Email content flagged as spam
- Queue processing issues

**Solution:**
1. Correct SMTP configuration settings
2. Configure server firewall to allow SMTP traffic
3. Fix authentication issues
4. Modify email content to avoid spam filters
5. Resolve email queue processing problems

**Testing Results:**
- [ ] SMTP connection successful
- [ ] Test emails delivered
- [ ] No authentication errors
- [ ] Emails not marked as spam
- [ ] Queue processing works correctly

### 4. Performance Issues

**Symptoms:**
- Slow page loading
- High server resource usage
- Timeouts during AJAX requests
- Database query performance issues

**Diagnostic Steps:**
1. Monitor server resource usage
2. Profile database queries
3. Check caching configuration
4. Review AJAX request handling
5. Analyze browser network activity

**Root Cause Analysis:**
- Inefficient database queries
- Missing or misconfigured caching
- Large data sets without pagination
- Blocking AJAX requests
- Unoptimized assets

**Solution:**
1. Optimize database queries with proper indexing
2. Configure and enable caching
3. Implement pagination for large data sets
4. Make AJAX requests asynchronous
5. Minify and optimize CSS/JS assets

**Testing Results:**
- [ ] Page loading times within acceptable range
- [ ] Server resource usage at normal levels
- [ ] No timeouts during AJAX requests
- [ ] Database queries execute efficiently
- [ ] Assets load quickly

## Diagnostic Tools and Procedures

### WordPress Debugging
Enable WordPress debugging by adding these lines to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Plugin-Specific Debugging
Use the built-in debugging tools located in the plugin's debug directory:
- debug-tables.php - Database schema validation
- debug-security.php - Security configuration check
- debug-kb-edit.php - Knowledge base editing diagnostics

### Browser Developer Tools
Use browser developer tools to diagnose:
- JavaScript errors in the console
- Network request performance
- CSS rendering issues
- Mobile responsiveness problems

### Server Log Analysis
Check these server logs for issues:
- Apache/Nginx error logs
- PHP error logs
- MySQL slow query logs
- System resource monitoring

## Recent Issues and Resolutions

### [Date] - Issue Description
**Problem:** Brief description of the problem
**Symptoms:** Observable symptoms of the issue
**Diagnostic Steps:** Steps taken to diagnose the issue
**Root Cause:** Determined root cause of the problem
**Solution:** How the issue was resolved
**Testing Results:** Verification that the solution worked
**Prevention:** Steps to prevent similar issues in the future

### [2025-08-25] - AJAX Polling Performance
**Problem:** AJAX polling was causing excessive server load
**Symptoms:** High CPU usage, slow response times during polling
**Diagnostic Steps:** 
1. Monitored server resource usage during polling
2. Profiled AJAX endpoint performance
3. Analyzed request frequency and data size
**Root Cause:** Polling interval too frequent with large data payloads
**Solution:** 
1. Increased polling interval from 5 to 30 seconds
2. Optimized data payload by only sending necessary information
3. Implemented smarter polling that only sends requests when user is active
**Testing Results:** 
- [x] Server CPU usage normalized
- [x] Response times improved
- [x] User experience maintained
**Prevention:** 
1. Regular performance monitoring of AJAX endpoints
2. Load testing before deploying polling changes
3. Implementing configurable polling intervals

## Emergency Procedures

### Critical System Failure
1. **Immediate Response:**
   - Disable plugin via WordPress admin or by renaming plugin folder
   - Restore from latest backup if available
   - Check error logs for specific failure information

2. **Diagnosis:**
   - Identify the specific component that failed
   - Determine if it's a code, database, or server issue
   - Check for recent changes that may have caused the failure

3. **Resolution:**
   - Apply hotfix if available
   - Roll back to previous stable version
   - Implement permanent fix in development environment

4. **Verification:**
   - Test fix in staging environment
   - Deploy to production after verification
   - Monitor system for any residual issues

### Data Corruption
1. **Identification:**
   - Check database integrity
   - Verify data consistency across related tables
   - Review recent data modification operations

2. **Recovery:**
   - Restore from backup if recent
   - Attempt data repair using database tools
   - Manually correct corrupted entries if feasible

3. **Prevention:**
   - Implement regular database backups
   - Add data validation to all input points
   - Use transactions for multi-table operations

## Contact Information

**Lead Developer:** Tomasz Lewandowski
**Email:** design@attv.uk
**GitHub:** [Repository URL]
**Support:** [Support Contact Information]

## Version History

**1.0.0** - Initial version with core troubleshooting framework