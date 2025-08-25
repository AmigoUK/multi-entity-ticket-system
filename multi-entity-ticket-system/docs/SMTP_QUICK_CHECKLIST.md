# SMTP Quick Setup Checklist

## Pre-Setup Requirements ✅

- [ ] WordPress admin access with `manage_options` capability
- [ ] Email provider credentials (username, password, or API key)
- [ ] Knowledge of email provider's SMTP settings
- [ ] Test email address for verification

## Basic Configuration Steps ✅

### Step 1: Access SMTP Settings
- [ ] Navigate to **WordPress Admin > Tickets > Settings**
- [ ] Click on **SMTP Settings** tab
- [ ] Ensure you have admin permissions

### Step 2: Provider Selection
- [ ] Choose your email provider from the dropdown, OR
- [ ] Select "Custom" for manual configuration
- [ ] Fields will auto-populate for known providers

### Step 3: Required Settings
- [ ] **Enable SMTP**: Check the enable checkbox
- [ ] **SMTP Host**: Enter provider's SMTP server
- [ ] **SMTP Port**: Usually 587 (TLS) or 465 (SSL)
- [ ] **Encryption**: Select TLS (recommended) or SSL
- [ ] **Username**: Your email account username
- [ ] **Password**: Your email account password or app password
- [ ] **From Email**: Email address for outgoing messages
- [ ] **From Name**: Display name for sender

### Step 4: Authentication (Provider-Specific)
#### Gmail Users:
- [ ] Enable 2-Factor Authentication on Google Account
- [ ] Generate App Password (not regular password)
- [ ] Use App Password in SMTP settings

#### Office 365 Users:
- [ ] Verify SMTP is enabled for mailbox
- [ ] Use full email address as username
- [ ] Ensure account has proper permissions

#### SendGrid/Mailgun Users:
- [ ] Use API key instead of password
- [ ] Username might be "apikey" (SendGrid) or specific username
- [ ] Verify API key has mail sending permissions

## Testing & Verification ✅

### Step 5: Connection Test
- [ ] Click **"Test SMTP Connection"** button
- [ ] Verify connection status shows success
- [ ] Check for any error messages
- [ ] If failed, review settings and try again

### Step 6: Email Test
- [ ] Enter test email address
- [ ] Click **"Send Test Email"** button
- [ ] Check test email delivery
- [ ] Verify sender name and address appear correctly
- [ ] Check spam folder if not received

### Step 7: Save Settings
- [ ] Click **"Save Changes"** button
- [ ] Confirm settings saved successfully
- [ ] Note any warning messages

## Advanced Configuration (Optional) ✅

### Email Queue Setup
- [ ] Enable Email Queue for high-volume sites
- [ ] Set appropriate batch size (default: 50)
- [ ] Configure queue processing interval (default: 5 minutes)
- [ ] Set maximum retry attempts (default: 3)

### Security Settings
- [ ] Enable email logging
- [ ] Configure rate limiting if needed
- [ ] Set up bounce handling
- [ ] Configure webhook notifications (if supported)

## Post-Setup Verification ✅

### Step 8: WordPress Integration
- [ ] Test ticket creation email notifications
- [ ] Verify SLA notification emails
- [ ] Check knowledge base notifications
- [ ] Test password reset emails

### Step 9: Monitor Logs
- [ ] Check SMTP logs in WordPress admin
- [ ] Verify successful email sending
- [ ] Look for any error patterns
- [ ] Monitor delivery rates

### Step 10: Performance Check
- [ ] Test bulk email operations
- [ ] Verify queue processing (if enabled)
- [ ] Check for timeout issues
- [ ] Monitor server resources

## Troubleshooting Checklist ✅

### If Connection Fails:
- [ ] Double-check host and port settings
- [ ] Try alternative ports (587, 465, 25)
- [ ] Verify firewall/hosting provider allows SMTP
- [ ] Check DNS resolution of SMTP host
- [ ] Test from command line or external tool

### If Authentication Fails:
- [ ] Verify username and password
- [ ] Check for app password requirement (Gmail)
- [ ] Ensure no extra spaces in credentials
- [ ] Try using email address as username
- [ ] Check account permissions and 2FA settings

### If Emails Don't Deliver:
- [ ] Check spam/junk folders
- [ ] Verify recipient email addresses
- [ ] Review email content for spam triggers
- [ ] Check DNS records (SPF, DKIM, DMARC)
- [ ] Monitor bounce messages and logs

## Security Best Practices ✅

### Credential Management:
- [ ] Use app-specific passwords when available
- [ ] Store credentials securely (wp-config.php preferred)
- [ ] Rotate passwords regularly
- [ ] Never commit credentials to version control

### Access Control:
- [ ] Limit admin access to SMTP settings
- [ ] Monitor authentication logs
- [ ] Use strong passwords for email accounts
- [ ] Enable 2FA where supported

### Monitoring:
- [ ] Set up regular email tests
- [ ] Monitor delivery rates and bounce rates
- [ ] Review SMTP logs weekly
- [ ] Set up alerts for critical failures

## Provider-Specific Quick Settings ✅

### Gmail/Google Workspace
```
Host: smtp.gmail.com
Port: 587
Encryption: TLS
Username: your-email@gmail.com
Password: [APP PASSWORD - not regular password]
```

### Microsoft 365/Outlook
```
Host: smtp.office365.com
Port: 587
Encryption: TLS
Username: your-email@yourdomain.com
Password: [Account password]
```

### SendGrid
```
Host: smtp.sendgrid.net
Port: 587
Encryption: TLS
Username: apikey
Password: [SendGrid API Key]
```

### Mailgun
```
Host: smtp.mailgun.org
Port: 587
Encryption: TLS
Username: [Mailgun SMTP username]
Password: [Mailgun SMTP password]
```

### Amazon SES
```
Host: email-smtp.[region].amazonaws.com
Port: 587
Encryption: TLS
Username: [SES SMTP username]
Password: [SES SMTP password]
```

## Final Verification ✅

### Complete Setup Check:
- [ ] SMTP connection test passes
- [ ] Test email delivers successfully
- [ ] WordPress notifications work correctly
- [ ] Logs show successful sends
- [ ] No error messages in WordPress debug log
- [ ] Email queue processes correctly (if enabled)
- [ ] Bounce handling works (if configured)

### Documentation:
- [ ] Document settings for team reference
- [ ] Note any custom configurations
- [ ] Record test results and dates
- [ ] Update team on SMTP status

### Maintenance Schedule:
- [ ] Monthly connection tests
- [ ] Quarterly password rotation (if applicable)
- [ ] Regular log review
- [ ] Performance monitoring setup

---

## Emergency Contacts & Resources

### Support Resources:
- **Plugin Documentation**: `/docs/SMTP_SETUP_GUIDE.md`
- **Diagnostic Tool**: `/tools/smtp-diagnostic.php`
- **WordPress Admin**: Tickets > Settings > SMTP Settings

### Provider Support:
- **Gmail**: https://support.google.com/mail/answer/7126229
- **Office 365**: Microsoft Support Portal
- **SendGrid**: https://support.sendgrid.com/
- **Mailgun**: https://help.mailgun.com/
- **Amazon SES**: AWS Support Center

### Quick Diagnostic Commands:
```bash
# Test SMTP connection
telnet [smtp-host] [port]

# Check DNS resolution
nslookup [smtp-host]

# Test WordPress mail function
wp eval "wp_mail('test@example.com', 'Test', 'Test message');"
```

**Remember**: Remove diagnostic files from production servers after testing is complete.

---

**Setup Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete | ❌ Failed

**Date Completed**: _______________
**Tested By**: _______________
**Notes**: _______________