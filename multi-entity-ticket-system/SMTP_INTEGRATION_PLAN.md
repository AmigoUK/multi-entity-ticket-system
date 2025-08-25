# SMTP Integration Plan - Multi-Entity Ticket System

## Overview
This plan outlines the integration of SMTP mail setup as an option within the existing email system, providing users with flexible email delivery options including WordPress default mail, custom SMTP, and popular email services.

## Goals
- Provide multiple email delivery methods (WordPress default, SMTP, popular services)
- Allow per-entity email configuration for multi-tenant support
- Implement secure credential storage and management
- Add email testing and validation features
- Maintain backward compatibility with existing email system
- Support common SMTP providers with pre-configured settings

---

## 1. SMTP Configuration Architecture

### 1.1 Configuration Levels
The system will support SMTP configuration at multiple levels:

1. **Global Level** - Default SMTP settings for the entire plugin
2. **Per-Entity Level** - Entity-specific SMTP settings (overrides global)
3. **Per-Template Level** - Template-specific sender configuration (future enhancement)

### 1.2 Email Delivery Methods
- **WordPress Default** - Use WordPress `wp_mail()` function
- **Custom SMTP** - User-defined SMTP server settings
- **Pre-configured Providers** - Gmail, Outlook, Yahoo, SendGrid, Mailgun, etc.

### 1.3 Configuration Storage
- Global settings: `mets_smtp_settings` WordPress option
- Entity settings: JSON field in `mets_entities` table
- Encrypted credential storage for security

---

## 2. Database Schema Updates

### 2.1 Update Entities Table
Add SMTP configuration support to entities:

```sql
ALTER TABLE wp_mets_entities 
ADD COLUMN smtp_settings longtext AFTER settings;
```

### 2.2 SMTP Settings Structure
```json
{
  "enabled": true,
  "method": "smtp", // "wordpress", "smtp", "provider"
  "provider": "custom", // "gmail", "outlook", "sendgrid", etc.
  "host": "smtp.gmail.com",
  "port": 587,
  "encryption": "tls", // "none", "ssl", "tls"
  "auth_required": true,
  "username": "user@gmail.com",
  "password": "encrypted_password",
  "from_email": "support@example.com",
  "from_name": "Support Team",
  "reply_to": "noreply@example.com",
  "test_email": "admin@example.com"
}
```

---

## 3. SMTP Provider Presets

### 3.1 Popular Email Providers
Pre-configured settings for common providers:

#### Gmail
- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: `TLS`
- Auth: Required

#### Outlook/Office 365
- Host: `smtp.office365.com`
- Port: `587`
- Encryption: `TLS`
- Auth: Required

#### Yahoo Mail
- Host: `smtp.mail.yahoo.com`
- Port: `587`
- Encryption: `TLS`
- Auth: Required

#### SendGrid
- Host: `smtp.sendgrid.net`
- Port: `587`
- Encryption: `TLS`
- Auth: Required

#### Mailgun
- Host: `smtp.mailgun.org`
- Port: `587`
- Encryption: `TLS`
- Auth: Required

### 3.2 Provider Configuration Class
```php
class METS_SMTP_Providers {
    public static function get_providers();
    public static function get_provider_config($provider);
    public static function validate_provider_credentials($provider, $credentials);
}
```

---

## 4. Implementation Components

### 4.1 Core Classes

#### METS_SMTP_Manager
Main SMTP management class:
- Configuration management
- Provider integration
- Email sending logic
- Credential encryption/decryption
- Connection testing

#### METS_SMTP_Mailer
Email sending implementation:
- PHPMailer integration
- Fallback mechanisms
- Error handling and logging
- Queue integration

#### METS_SMTP_Settings
Settings management:
- Global and entity-level settings
- Validation and sanitization
- Migration utilities

### 4.2 Admin Interface Components

#### SMTP Settings Page
- Global SMTP configuration
- Provider selection interface
- Connection testing tools
- Email sending test functionality

#### Entity SMTP Settings
- Per-entity SMTP override
- Inherit/override toggle
- Entity-specific testing

#### Settings Validation
- Real-time configuration validation
- Connection testing
- Credential verification

---

## 5. Security Considerations

### 5.1 Credential Storage
- Encrypt SMTP passwords using WordPress salt
- Store credentials securely in database
- Option to use external credential management

### 5.2 Validation and Sanitization
- Validate all SMTP configuration inputs
- Sanitize email addresses and settings
- Prevent injection attacks

### 5.3 Access Control
- Restrict SMTP configuration to authorized users
- Entity-level access controls
- Audit logging for configuration changes

---

## 6. Integration with Existing Email System

### 6.1 Email Queue Updates
Update existing email queue to support SMTP:

```sql
ALTER TABLE wp_mets_email_queue 
ADD COLUMN smtp_config longtext AFTER template_data,
ADD COLUMN delivery_method varchar(50) DEFAULT 'wordpress' AFTER smtp_config;
```

### 6.2 Template Engine Integration
- Update email template engine to include SMTP settings
- Support per-entity email configuration
- Dynamic sender information based on entity

### 6.3 Backward Compatibility
- Maintain existing WordPress mail functionality
- Gradual migration path for existing installations
- Fallback to WordPress mail if SMTP fails

---

## 7. Admin Interface Design

### 7.1 Global SMTP Settings
Location: `Admin > METS Settings > Email & SMTP`

#### Settings Sections:
1. **Email Delivery Method**
   - WordPress Default (recommended for beginners)
   - Custom SMTP Server
   - Email Service Provider

2. **SMTP Configuration** (when SMTP selected)
   - Provider dropdown with presets
   - Manual configuration fields
   - Advanced settings (encryption, authentication)

3. **Default Sender Settings**
   - From Name
   - From Email
   - Reply-To Email

4. **Testing & Validation**
   - Test email functionality
   - Connection diagnostics
   - Email delivery test

### 7.2 Entity-Level SMTP Override
Location: `Admin > Entities > Edit Entity > Email Settings`

#### Entity Settings:
- Use Global Settings (default)
- Override with Entity-Specific SMTP
- Entity-specific sender information
- Test entity email configuration

### 7.3 User Interface Elements

#### Provider Selection Interface
```html
<div class="smtp-provider-selection">
    <label>
        <input type="radio" name="smtp_provider" value="wordpress" checked>
        WordPress Default (Easy setup)
    </label>
    <label>
        <input type="radio" name="smtp_provider" value="gmail">
        Gmail / Google Workspace
    </label>
    <label>
        <input type="radio" name="smtp_provider" value="outlook">
        Outlook / Office 365
    </label>
    <label>
        <input type="radio" name="smtp_provider" value="custom">
        Custom SMTP Server
    </label>
</div>
```

#### Configuration Forms
Dynamic forms that show/hide based on provider selection with field validation and help text.

---

## 8. Implementation Timeline

### Phase 1: Core SMTP Infrastructure (Week 1-2)
- Create METS_SMTP_Manager class
- Implement credential encryption
- Add database schema updates
- Create provider configuration system

### Phase 2: Admin Interface (Week 2-3)
- Build global SMTP settings page
- Create entity-level SMTP settings
- Implement provider selection interface
- Add configuration validation

### Phase 3: Email Integration (Week 3-4)
- Update email queue system
- Integrate with template engine
- Implement PHPMailer integration
- Add fallback mechanisms

### Phase 4: Testing & Validation (Week 4)
- Connection testing tools
- Email sending tests
- Error handling and logging
- Documentation and user guides

---

## 9. Technical Requirements

### 9.1 Dependencies
- **PHPMailer** - For SMTP email sending
- **WordPress Encryption API** - For secure credential storage
- **Existing Email Queue System** - Integration point

### 9.2 PHP Requirements
- PHP 7.4+ (for PHPMailer compatibility)
- OpenSSL extension (for encryption)
- SMTP extension support

### 9.3 WordPress Integration
- WordPress Settings API
- WordPress Cron System
- WordPress Security Features (nonces, sanitization)

---

## 10. Error Handling & Logging

### 10.1 Error Types
- **Connection Errors** - SMTP server connection issues
- **Authentication Errors** - Login credential problems
- **Configuration Errors** - Invalid settings
- **Delivery Errors** - Email sending failures

### 10.2 Logging Strategy
- Log SMTP errors to WordPress error log
- Store delivery status in email queue
- Admin notifications for critical failures
- Debug mode for troubleshooting

### 10.3 Fallback Mechanisms
- Automatic fallback to WordPress mail on SMTP failure
- Retry logic for temporary failures
- Admin alerts for persistent issues

---

## 11. User Experience Considerations

### 11.1 Setup Wizard
- Guided SMTP configuration process
- Provider-specific setup instructions
- Automatic configuration testing
- Success/failure feedback

### 11.2 Documentation
- Provider-specific setup guides
- Troubleshooting documentation
- Common configuration examples
- Security best practices

### 11.3 Support Features
- Configuration validation
- Real-time connection testing
- Email delivery diagnostics
- Export/import settings functionality

---

## 12. Migration Strategy

### 12.1 Existing Installations
- Automatic detection of existing email settings
- Migration wizard for current configurations
- Backup/restore functionality
- Rollback options

### 12.2 Default Configuration
- WordPress mail as default for new installations
- Easy upgrade path to SMTP when needed
- Progressive enhancement approach

---

## 13. Success Criteria

### 13.1 Functional Requirements
✅ Support multiple email delivery methods
✅ Secure credential storage and management
✅ Per-entity email configuration capability
✅ Integration with existing email queue system
✅ Provider presets for popular services
✅ Connection testing and validation tools

### 13.2 Performance Requirements
✅ No impact on non-SMTP email delivery
✅ Efficient queue processing with SMTP
✅ Minimal overhead for WordPress default mail
✅ Reasonable setup time for common providers

### 13.3 Security Requirements
✅ Encrypted credential storage
✅ Secure configuration validation
✅ Access control for SMTP settings
✅ Audit logging for configuration changes

---

This comprehensive plan provides a robust foundation for integrating SMTP mail setup as an option within the Multi-Entity Ticket System while maintaining security, performance, and user experience standards.