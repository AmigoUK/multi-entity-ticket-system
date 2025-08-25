# 🚨 METS Critical Security Fix Plan - URGENT

**IMMEDIATE ACTION REQUIRED - STEP BY STEP IMPLEMENTATION GUIDE**

**Priority:** CRITICAL  
**Timeline:** 24-48 hours  
**Risk Level:** PRODUCTION BLOCKING  

---

## 🎯 Phase 1: CRITICAL SECURITY FIXES (IMMEDIATE - Day 1)

### Step 1: Fix XSS Vulnerabilities in Email Templates

**Time Required:** 2-3 hours  
**Files to Modify:** All email template files  

#### 1.1 Backup Current Templates
```bash
# Create backup directory
mkdir -p /path/to/backup/email-templates-backup-$(date +%Y%m%d)
cp -r wp-content/plugins/multi-entity-ticket-system/includes/email-templates/* /path/to/backup/email-templates-backup-$(date +%Y%m%d)/
```

#### 1.2 Fix Each Template File

**Template Files to Fix:**
- `ticket-created-customer.php`
- `ticket-created-agent.php`
- `ticket-reply-customer.php`
- `sla-breach.php`
- `sla-breach-warning.php`
- `sla-warning.php`

**REPLACE ALL INSTANCES:**

```php
// OLD VULNERABLE CODE:
{{ticket_content}}
{{reply_content}}
{{customer_name}}
{{customer_email}}
{{ticket_subject}}
{{entity_name}}
{{agent_name}}

// NEW SECURE CODE:
<?php echo esc_html( $ticket_content ); ?>
<?php echo wp_kses_post( $reply_content ); ?>
<?php echo esc_html( $customer_name ); ?>
<?php echo esc_html( $customer_email ); ?>
<?php echo esc_html( $ticket_subject ); ?>
<?php echo esc_html( $entity_name ); ?>
<?php echo esc_html( $agent_name ); ?>
```

#### 1.3 Template Engine Update
**File:** `includes/class-mets-email-template-engine.php`

**FIND (around line 150-200):**
```php
$content = str_replace( $placeholder, $value, $content );
```

**REPLACE WITH:**
```php
// Sanitize based on content type
if ( strpos( $placeholder, '_html' ) !== false ) {
    $sanitized_value = wp_kses_post( $value );
} else {
    $sanitized_value = esc_html( $value );
}
$content = str_replace( $placeholder, $sanitized_value, $content );
```

#### 1.4 Test Template Security
```php
// Add this test function to verify fixes
private function test_template_security() {
    $test_data = array(
        'customer_name' => '<script>alert("XSS")</script>Test User',
        'ticket_content' => 'Safe content <strong>with HTML</strong>',
        'reply_content' => '<script>alert("XSS")</script><p>Reply content</p>'
    );
    
    // Process template and verify no script tags remain
    $result = $this->process_template( 'ticket-created-customer', $test_data );
    
    if ( strpos( $result, '<script>' ) !== false ) {
        error_log( 'SECURITY WARNING: XSS vulnerability still present in email templates' );
        return false;
    }
    return true;
}
```

### Step 2: Fix SMTP Encryption Vulnerabilities

**Time Required:** 3-4 hours  
**File:** `includes/smtp/class-mets-smtp-manager.php`

#### 2.1 Create Secure Key Storage

**FIND (around line 240-250):**
```php
private function get_encryption_key() {
    return hash('sha256', SECURE_AUTH_SALT . 'mets_smtp_key');
}

private function get_iv() {
    return substr(hash('sha256', AUTH_SALT), 0, 16);
}
```

**REPLACE WITH:**
```php
private function get_encryption_key() {
    $key_file = WP_CONTENT_DIR . '/mets-secure-key.php';
    
    if ( ! file_exists( $key_file ) ) {
        $this->generate_secure_key( $key_file );
    }
    
    $key_data = include $key_file;
    return base64_decode( $key_data['key'] );
}

private function generate_secure_key( $key_file ) {
    $key = base64_encode( random_bytes( 32 ) );
    
    $content = "<?php\n";
    $content .= "// METS Secure Key - DO NOT EDIT OR SHARE\n";
    $content .= "if ( ! defined( 'ABSPATH' ) ) { exit; }\n";
    $content .= "return array( 'key' => '{$key}', 'created' => '" . date( 'Y-m-d H:i:s' ) . "' );\n";
    
    file_put_contents( $key_file, $content, LOCK_EX );
    chmod( $key_file, 0600 );
}

private function get_iv() {
    // Generate random IV for each encryption
    return random_bytes( 16 );
}
```

#### 2.2 Update Encryption Methods

**FIND:**
```php
public function encrypt_password( $password ) {
    $key = $this->get_encryption_key();
    $iv = $this->get_iv();
    
    $encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );
    return base64_encode( $iv . $encrypted );
}
```

**REPLACE WITH:**
```php
public function encrypt_password( $password ) {
    $key = $this->get_encryption_key();
    $iv = $this->get_iv();
    
    $encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );
    return base64_encode( $iv . $encrypted );
}

public function decrypt_password( $encrypted_password ) {
    $key = $this->get_encryption_key();
    $data = base64_decode( $encrypted_password );
    
    $iv = substr( $data, 0, 16 );
    $encrypted = substr( $data, 16 );
    
    return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
}
```

#### 2.3 Add Key Rotation Function
```php
public function rotate_encryption_key() {
    // Get all encrypted passwords
    $entities = $this->get_entities_with_smtp();
    
    // Decrypt with old key
    $decrypted_passwords = array();
    foreach ( $entities as $entity ) {
        $decrypted_passwords[ $entity->id ] = $this->decrypt_password( $entity->smtp_password );
    }
    
    // Generate new key
    $key_file = WP_CONTENT_DIR . '/mets-secure-key.php';
    unlink( $key_file );
    $this->generate_secure_key( $key_file );
    
    // Re-encrypt with new key
    foreach ( $decrypted_passwords as $entity_id => $password ) {
        $new_encrypted = $this->encrypt_password( $password );
        $this->update_entity_smtp_password( $entity_id, $new_encrypted );
    }
    
    return true;
}
```

### Step 3: Secure API Endpoints

**Time Required:** 2 hours  
**File:** `includes/api/class-mets-rest-api.php`

#### 3.1 Fix Unrestricted Ticket Creation

**FIND (around line 920):**
```php
public function check_ticket_create_permission( $request ) {
    return true; // Allows anyone to create tickets
}
```

**REPLACE WITH:**
```php
public function check_ticket_create_permission( $request ) {
    // Allow logged-in users
    if ( is_user_logged_in() ) {
        return true;
    }
    
    // For guest users, validate email and implement rate limiting
    $customer_email = $request->get_param( 'customer_email' );
    
    if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
        return new WP_Error( 'invalid_email', 'Valid email required for guest ticket creation', array( 'status' => 400 ) );
    }
    
    // Check rate limiting for guest submissions
    if ( ! $this->check_guest_rate_limit( $customer_email ) ) {
        return new WP_Error( 'rate_limit_exceeded', 'Too many tickets submitted. Please try again later.', array( 'status' => 429 ) );
    }
    
    return true;
}

private function check_guest_rate_limit( $email ) {
    global $wpdb;
    
    $recent_tickets = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
        WHERE customer_email = %s 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        $email
    ) );
    
    return $recent_tickets < 3; // Max 3 tickets per hour for guests
}
```

#### 3.2 Enhance Error Handling

**FIND (around line 150-200):**
```php
catch ( Exception $e ) {
    return new WP_Error( 'database_error', $e->getMessage(), array( 'status' => 500 ) );
}
```

**REPLACE WITH:**
```php
catch ( Exception $e ) {
    // Log detailed error for debugging
    error_log( 'METS API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
    
    // Return generic error to prevent information disclosure
    return new WP_Error( 'server_error', 'An internal error occurred. Please try again.', array( 'status' => 500 ) );
}
```

### Step 4: Add Security Headers and CSP

**Time Required:** 1 hour  
**File:** `includes/class-mets-security-manager.php`

#### 4.1 Enhance Security Headers

**FIND (around line 450):**
```php
public function add_security_headers() {
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-Content-Type-Options: nosniff' );
}
```

**REPLACE WITH:**
```php
public function add_security_headers() {
    // Prevent clickjacking
    header( 'X-Frame-Options: SAMEORIGIN' );
    
    // Prevent MIME type sniffing
    header( 'X-Content-Type-Options: nosniff' );
    
    // XSS Protection
    header( 'X-XSS-Protection: 1; mode=block' );
    
    // HTTPS enforcement
    if ( is_ssl() ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
    }
    
    // Referrer Policy
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    
    // Content Security Policy
    $csp = $this->generate_csp_header();
    header( 'Content-Security-Policy: ' . $csp );
}

private function generate_csp_header() {
    $nonce = wp_create_nonce( 'mets_csp_nonce' );
    
    $csp_directives = array(
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline'", // Remove unsafe-inline in production
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self'",
        "connect-src 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "base-uri 'self'"
    );
    
    return implode( '; ', $csp_directives );
}
```

---

## 🔧 Phase 2: IMMEDIATE TESTING (Day 1 - After fixes)

### Step 5: Security Testing Checklist

#### 5.1 XSS Testing
```bash
# Test email templates with malicious input
curl -X POST "http://yoursite.com/wp-json/mets/v1/tickets" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "<script>alert(\"XSS\")</script>Test Ticket",
    "description": "<img src=x onerror=alert(1)>Description",
    "customer_email": "test@example.com",
    "customer_name": "<svg onload=alert(1)>TestUser"
  }'
```

#### 5.2 Encryption Testing
```php
// Add to admin panel for testing
public function test_smtp_encryption() {
    $test_password = 'TestPassword123!@#';
    
    $encrypted = $this->encrypt_password( $test_password );
    $decrypted = $this->decrypt_password( $encrypted );
    
    if ( $test_password === $decrypted ) {
        return 'Encryption test PASSED';
    } else {
        return 'Encryption test FAILED - Security vulnerability!';
    }
}
```

#### 5.3 API Security Testing
```bash
# Test rate limiting
for i in {1..5}; do
  curl -X POST "http://yoursite.com/wp-json/mets/v1/tickets" \
    -H "Content-Type: application/json" \
    -d '{"subject": "Test '$i'", "description": "Test", "customer_email": "test@example.com"}'
done
```

### Step 6: Verification Checklist

**Complete this checklist before proceeding:**

- [ ] All email templates sanitize user input
- [ ] SMTP passwords use secure encryption with random IV
- [ ] API endpoints have proper authentication
- [ ] Security headers are implemented
- [ ] XSS testing shows no vulnerabilities
- [ ] Encryption testing passes
- [ ] Rate limiting works correctly
- [ ] Error messages don't leak sensitive information

---

## 🚀 Phase 3: PRODUCTION DEPLOYMENT (Day 2)

### Step 7: Pre-Production Security Audit

#### 7.1 Run Security Scan
```bash
# Use WordPress security scanner
wp plugin install wordfence --activate
wp wordfence scan
```

#### 7.2 Database Security Check
```sql
-- Verify encrypted passwords don't contain plaintext
SELECT id, entity_name, 
       CASE WHEN smtp_password LIKE '%password%' THEN 'INSECURE' ELSE 'SECURE' END as status
FROM wp_mets_entities 
WHERE smtp_password IS NOT NULL;
```

#### 7.3 File Permissions Audit
```bash
# Check critical file permissions
find wp-content/plugins/multi-entity-ticket-system/ -type f -name "*.php" -not -perm 644
find wp-content/ -name "mets-secure-key.php" -not -perm 600
```

### Step 8: Production Deployment Steps

#### 8.1 Staging Environment Testing
1. Deploy fixes to staging
2. Run comprehensive security tests
3. Test all functionality works correctly
4. Verify performance hasn't degraded

#### 8.2 Production Deployment
1. **Schedule maintenance window**
2. **Backup entire site and database**
3. **Deploy fixes in order:**
   - Email template fixes
   - SMTP encryption fixes
   - API security fixes
   - Security headers
4. **Run post-deployment tests**
5. **Monitor logs for 24 hours**

#### 8.3 Post-Deployment Monitoring
```bash
# Monitor for security events
tail -f wp-content/debug.log | grep -i "security\|error\|warning"

# Check for failed login attempts
grep "authentication failure" /var/log/auth.log
```

---

## 🛡️ Phase 4: ONGOING SECURITY (Week 1-2)

### Step 9: Additional Security Enhancements

#### 9.1 Implement File Upload Security
**File:** `includes/class-mets-file-handler.php`

```php
// Add virus scanning
private function scan_file_for_malware( $file_path ) {
    // Implement ClamAV integration or use WordPress security plugin
    if ( function_exists( 'clamav_scan_file' ) ) {
        return clamav_scan_file( $file_path );
    }
    
    // Basic malicious pattern detection
    $content = file_get_contents( $file_path, false, null, 0, 1024 ); // First 1KB
    $malicious_patterns = array(
        '/<\?php/',
        '/<script/',
        '/eval\s*\(/',
        '/exec\s*\(/',
        '/system\s*\(/'
    );
    
    foreach ( $malicious_patterns as $pattern ) {
        if ( preg_match( $pattern, $content ) ) {
            return false;
        }
    }
    
    return true;
}
```

#### 9.2 Implement Session Security
```php
// Add to class-mets-security-manager.php
public function configure_secure_sessions() {
    // Secure session configuration
    ini_set( 'session.cookie_httponly', 1 );
    ini_set( 'session.cookie_secure', is_ssl() ? 1 : 0 );
    ini_set( 'session.use_strict_mode', 1 );
    ini_set( 'session.cookie_samesite', 'Strict' );
    
    // Session timeout for sensitive operations
    add_action( 'wp_login', array( $this, 'start_secure_session' ) );
}
```

### Step 10: Security Monitoring Dashboard

#### 10.1 Create Security Metrics
```php
public function get_security_metrics() {
    global $wpdb;
    
    return array(
        'failed_logins_24h' => $this->get_failed_logins_count( 24 ),
        'blocked_ips' => $this->get_blocked_ip_count(),
        'suspicious_activities' => $this->get_suspicious_activity_count(),
        'last_security_scan' => get_option( 'mets_last_security_scan' ),
        'critical_vulnerabilities' => $this->check_critical_vulnerabilities()
    );
}
```

---

## ⚠️ CRITICAL WARNINGS

### DO NOT IGNORE:

1. **BACKUP EVERYTHING** before making changes
2. **Test on staging environment** first
3. **Monitor logs** for 48 hours after deployment
4. **Keep security patches** up to date
5. **Run weekly security scans**

### EMERGENCY ROLLBACK PLAN:

If issues occur:
1. Restore from backup immediately
2. Review error logs
3. Fix issues in staging
4. Re-deploy with fixes

---

## 📞 IMMEDIATE ACTION ITEMS

**TODAY (Next 4 hours):**
- [ ] Backup entire site and database
- [ ] Fix XSS vulnerabilities in email templates
- [ ] Implement secure SMTP encryption
- [ ] Test fixes in staging environment

**TOMORROW:**
- [ ] Deploy API security fixes
- [ ] Implement security headers
- [ ] Run comprehensive security tests
- [ ] Deploy to production

**THIS WEEK:**
- [ ] Set up security monitoring
- [ ] Implement additional file upload security
- [ ] Create security documentation
- [ ] Train team on security practices

---

**⚡ PRIORITY: EXECUTE IMMEDIATELY - PRODUCTION SECURITY AT RISK ⚡**

This plan addresses all critical security vulnerabilities found in the analysis. Each step includes specific code changes, testing procedures, and verification methods. Follow this plan exactly to secure your METS system before production use.