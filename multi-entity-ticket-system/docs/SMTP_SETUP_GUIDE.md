# SMTP Setup Guide for Multi-Entity Ticket System

## Table of Contents
1. [Overview](#overview)
2. [Quick Setup](#quick-setup)
3. [Provider-Specific Configuration](#provider-specific-configuration)
4. [Advanced Configuration](#advanced-configuration)
5. [Testing & Verification](#testing--verification)
6. [Troubleshooting](#troubleshooting)
7. [Best Practices](#best-practices)
8. [Security Considerations](#security-considerations)

## Overview

The Multi-Entity Ticket System includes a comprehensive SMTP system that allows you to send emails through external mail servers instead of relying on WordPress's default mail function. This ensures better deliverability, tracking, and reliability for ticket notifications.

### Benefits of Using SMTP
- **Better Deliverability**: Emails are less likely to end up in spam folders
- **Authentication**: Proper authentication with mail servers
- **Logging**: Complete email logging and error tracking
- **Provider Integration**: Built-in support for popular email providers
- **Scalability**: Handle high-volume email sending

## Quick Setup

### Step 1: Access SMTP Settings
1. Navigate to **WordPress Admin > Tickets > Settings**
2. Click on the **SMTP Settings** tab
3. Enable SMTP by checking "Enable SMTP"

### Step 2: Basic Configuration
Fill in the following required fields:
- **SMTP Host**: Your mail server hostname
- **SMTP Port**: Usually 587 (TLS) or 465 (SSL)
- **Encryption**: Choose TLS (recommended) or SSL
- **Username**: Your email account username
- **Password**: Your email account password
- **From Email**: The email address emails will be sent from
- **From Name**: The name that will appear as the sender

### Step 3: Test Configuration
1. Click "Test SMTP Connection" to verify settings
2. Send a test email to confirm everything works
3. Save your settings

## Provider-Specific Configuration

### Gmail / Google Workspace

#### Standard Setup
```
SMTP Host: smtp.gmail.com
SMTP Port: 587
Encryption: TLS
Username: your-email@gmail.com
Password: your-app-password (not your regular password)
```

#### App Password Setup (Required for Gmail)
1. Enable 2-Factor Authentication on your Google account
2. Go to Google Account settings > Security > App passwords
3. Generate a new app password for "Mail"
4. Use this app password in the SMTP settings

#### OAuth2 Setup (Advanced)
For Google Workspace accounts, you can use OAuth2:
1. Create a Google Cloud Project
2. Enable Gmail API
3. Create OAuth2 credentials
4. Configure in SMTP settings using OAuth2 option

### Microsoft 365 / Outlook.com

#### Outlook.com
```
SMTP Host: smtp-mail.outlook.com
SMTP Port: 587
Encryption: TLS
Username: your-email@outlook.com
Password: your-account-password
```

#### Microsoft 365
```
SMTP Host: smtp.office365.com
SMTP Port: 587
Encryption: TLS
Username: your-email@yourdomain.com
Password: your-account-password
```

### SendGrid

#### API Setup (Recommended)
```
SMTP Host: smtp.sendgrid.net
SMTP Port: 587
Encryption: TLS
Username: apikey
Password: your-sendgrid-api-key
```

#### Configuration Steps
1. Create SendGrid account
2. Generate API key with "Mail Send" permissions
3. Use "apikey" as username and API key as password

### Mailgun

```
SMTP Host: smtp.mailgun.org
SMTP Port: 587
Encryption: TLS
Username: your-mailgun-smtp-username
Password: your-mailgun-smtp-password
```

### Amazon SES

```
SMTP Host: email-smtp.region.amazonaws.com
SMTP Port: 587
Encryption: TLS
Username: your-ses-smtp-username
Password: your-ses-smtp-password
```

Replace `region` with your AWS region (e.g., `us-west-2`).

### Other Popular Providers

#### Zoho Mail
```
SMTP Host: smtp.zoho.com
SMTP Port: 587
Encryption: TLS
```

#### Yahoo Mail
```
SMTP Host: smtp.mail.yahoo.com
SMTP Port: 587 or 465
Encryption: TLS or SSL
```

#### iCloud
```
SMTP Host: smtp.mail.me.com
SMTP Port: 587
Encryption: TLS
```

## Advanced Configuration

### Email Queue System

The plugin includes an advanced email queue system for high-volume scenarios:

#### Queue Settings
- **Enable Queue**: Process emails in background
- **Batch Size**: Number of emails to process per batch (default: 50)
- **Queue Interval**: How often to process queue (default: 5 minutes)
- **Max Retries**: Maximum retry attempts for failed emails (default: 3)

#### Benefits
- Prevents timeouts on bulk email operations
- Automatic retry for failed emails
- Better performance for high-volume sites
- Detailed logging and tracking

### Rate Limiting

Configure rate limiting to comply with provider limits:

```php
// In wp-config.php
define('METS_SMTP_RATE_LIMIT', 100); // Emails per hour
define('METS_SMTP_BURST_LIMIT', 10); // Max emails per minute
```

### Custom Headers

Add custom headers for better tracking:

```php
// In wp-config.php
define('METS_SMTP_CUSTOM_HEADERS', [
    'X-Mailer' => 'METS Ticket System',
    'X-Priority' => '3'
]);
```

## Testing & Verification

### Built-in Test Tools

#### Connection Test
1. Go to SMTP Settings
2. Click "Test SMTP Connection"
3. Check the connection status and any error messages

#### Email Test
1. Enter a test email address
2. Click "Send Test Email"
3. Check both sending status and email receipt

### Manual Testing

#### Using WordPress Hooks
```php
// Add to functions.php or plugin
add_action('init', function() {
    if (current_user_can('manage_options') && isset($_GET['mets_test_email'])) {
        $result = wp_mail(
            'test@example.com',
            'METS Test Email',
            'This is a test email from METS.'
        );
        
        echo $result ? 'Email sent successfully!' : 'Email failed to send.';
        exit;
    }
});
```

#### Log Verification
Check the SMTP logs in:
- **WordPress Admin > Tickets > Settings > SMTP Settings > View Logs**
- Look for successful sends and any error messages

## Troubleshooting

### Common Issues & Solutions

#### Issue: "SMTP Connection Failed"

**Possible Causes & Solutions:**

1. **Incorrect Host/Port**
   - Verify SMTP host and port with your provider
   - Try alternative ports (587, 465, 25)

2. **Firewall Issues**
   - Check if your hosting provider blocks SMTP ports
   - Contact hosting support to whitelist SMTP ports

3. **SSL/TLS Issues**
   - Try switching between TLS and SSL
   - Some servers require specific encryption types

**Debugging Steps:**
```php
// Enable SMTP debugging
define('METS_SMTP_DEBUG', true);
```

#### Issue: "Authentication Failed"

**Solutions:**

1. **Gmail Users**
   - Enable 2-Factor Authentication
   - Use App Password instead of regular password
   - Check if "Less Secure Apps" needs to be enabled (not recommended)

2. **Office 365 Users**
   - Verify SMTP authentication is enabled for the mailbox
   - Use full email address as username

3. **Generic Solutions**
   - Double-check username and password
   - Ensure no extra spaces in credentials
   - Try using email address as username

#### Issue: "Emails Not Delivered"

**Diagnostic Steps:**

1. **Check SMTP Logs**
   - Look for successful send status
   - Check for bounce messages

2. **Verify DNS Settings**
   - Ensure SPF records include your SMTP provider
   - Add DKIM records if provided
   - Set up DMARC policy

3. **Content Issues**
   - Check for spam-triggering content
   - Ensure proper HTML formatting
   - Include text alternative for HTML emails

#### Issue: "Rate Limit Exceeded"

**Solutions:**

1. **Enable Email Queue**
   - Processes emails in batches
   - Prevents overwhelming SMTP server

2. **Adjust Rate Limits**
   ```php
   define('METS_SMTP_RATE_LIMIT', 50); // Reduce from default
   ```

3. **Upgrade Provider Plan**
   - Consider higher-tier plans for more volume

### Debug Mode

Enable comprehensive debugging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('METS_SMTP_DEBUG', true);
define('METS_EMAIL_QUEUE_DEBUG', true);
```

View debug logs in:
- WordPress debug.log file
- METS SMTP logs (Admin panel)

### Log Analysis

#### Success Indicators
- "Message sent successfully"
- "SMTP connection established"
- "Authentication successful"

#### Error Indicators
- "Connection timeout"
- "Authentication failed"
- "Message rejected"

## Best Practices

### Security

1. **Use App Passwords**
   - Never use your main account password
   - Generate specific app passwords for SMTP

2. **Secure Storage**
   - Store credentials in wp-config.php, not database
   - Use environment variables for sensitive data

3. **Regular Rotation**
   - Rotate SMTP passwords regularly
   - Monitor for unauthorized usage

### Performance

1. **Use Email Queue**
   - Essential for high-volume sites
   - Prevents timeouts and failures

2. **Optimize Email Content**
   - Keep HTML simple and clean
   - Optimize images and attachments
   - Use text alternatives

3. **Monitor Delivery**
   - Regular check bounce rates
   - Monitor spam complaints
   - Track open rates where possible

### Reliability

1. **Backup SMTP Providers**
   - Configure fallback SMTP settings
   - Test failover scenarios

2. **Regular Testing**
   - Monthly test email functionality
   - Verify all email types (notifications, reports, etc.)

3. **Monitor Logs**
   - Review SMTP logs weekly
   - Set up alerts for critical failures

## Security Considerations

### Credential Protection

1. **Environment Variables**
   ```php
   // In wp-config.php
   define('METS_SMTP_USER', getenv('SMTP_USERNAME'));
   define('METS_SMTP_PASS', getenv('SMTP_PASSWORD'));
   ```

2. **File Permissions**
   - Ensure wp-config.php is not publicly readable
   - Use proper file permissions (644 or 600)

3. **Database Encryption**
   - SMTP passwords are encrypted in database
   - Use strong WordPress security keys

### Network Security

1. **Use TLS/SSL**
   - Always use encrypted connections
   - Verify certificate validity

2. **IP Restrictions**
   - Whitelist server IP with SMTP provider
   - Use dedicated IP for high-volume sending

3. **Authentication**
   - Use strongest authentication available
   - Enable 2FA where supported

### Compliance

1. **Data Privacy**
   - Follow GDPR/CCPA requirements
   - Include unsubscribe mechanisms
   - Respect user preferences

2. **Anti-Spam**
   - Include proper headers
   - Follow CAN-SPAM requirements
   - Monitor bounce rates

## Support & Resources

### Getting Help

1. **Documentation**
   - Check plugin documentation
   - Review provider-specific guides

2. **Logs**
   - Always check SMTP logs first
   - Enable debug mode for detailed info

3. **Community**
   - WordPress support forums
   - Provider-specific communities

### Useful Tools

1. **Email Testing**
   - Mail-tester.com - Test spam score
   - MXToolbox.com - DNS/delivery testing
   - Gmail's Postmaster Tools

2. **Monitoring**
   - SendGrid/Mailgun analytics
   - Google Postmaster Tools
   - Custom webhook monitoring

### Provider Resources

- **Gmail**: https://support.google.com/mail/answer/7126229
- **Outlook**: https://support.microsoft.com/en-us/office/pop-imap-and-smtp-settings-8361e398-8af4-4e97-b147-6c6c4ac95353
- **SendGrid**: https://docs.sendgrid.com/for-developers/sending-email/integrating-with-the-smtp-api
- **Mailgun**: https://documentation.mailgun.com/en/latest/user_manual.html#sending-via-smtp
- **Amazon SES**: https://docs.aws.amazon.com/ses/latest/dg/send-email-smtp.html

---

## Quick Reference Card

### Most Common Settings

| Provider | Host | Port | Encryption |
|----------|------|------|------------|
| Gmail | smtp.gmail.com | 587 | TLS |
| Outlook | smtp.office365.com | 587 | TLS |
| SendGrid | smtp.sendgrid.net | 587 | TLS |
| Mailgun | smtp.mailgun.org | 587 | TLS |
| Amazon SES | email-smtp.region.amazonaws.com | 587 | TLS |

### Emergency Fallback
If SMTP fails completely, the system will fall back to WordPress's default mail function. Monitor logs to detect when this happens.

### Contact Support
For plugin-specific SMTP issues, contact the Multi-Entity Ticket System support team with:
- SMTP provider being used
- Error messages from logs
- Test results from connection test
- WordPress and plugin version numbers