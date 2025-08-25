# Manual Testing Guide

This document provides detailed procedures for manually testing the Multi-Entity Ticket System plugin features.

## Testing Environment Setup

Before beginning testing, ensure you have:

1. A WordPress installation with the Multi-Entity Ticket System plugin activated
2. At least two user accounts with different roles:
   - Administrator
   - Support Agent
   - Regular User (for frontend testing)
3. Sample data including:
   - Entities with hierarchical structure
   - Tickets in various statuses
   - Knowledge base articles
4. SMTP settings configured for email testing
5. WooCommerce installed and activated (for integration testing)

## Test Cases

### 1. AI Chat Widget Enhancement

**Files to test:**
- public/class-mets-ai-chat-widget.php
- includes/class-mets-ai-service.php
- assets/js/ai-chat-widget.js

**Test Procedures:**

1. **Widget Display**
   - Navigate to a frontend page
   - Verify the AI chat widget appears in the expected location
   - Check widget styling and responsiveness on different screen sizes
   - Confirm widget can be opened and closed

2. **AI Service Connection**
   - Open the chat widget
   - Send a test message
   - Verify the AI service responds appropriately
   - Check that conversation history is maintained

3. **User Interaction Handling**
   - Test different types of questions (simple, complex, KB-related)
   - Verify suggested articles appear when relevant
   - Test file upload functionality if available
   - Check that user input is properly sanitized

4. **Security Validation**
   - Attempt to inject malicious code through the chat
   - Test rate limiting by sending many messages quickly
   - Verify that unauthorized users cannot access admin features

### 2. Real-time Notification System

**Files to test:**
- includes/class-mets-ajax-polling.php
- assets/js/ajax-polling.js
- includes/class-mets-admin-bar.php

**Test Procedures:**

1. **Notification Display**
   - Log in as an admin user
   - Have another user create a new ticket
   - Verify notification appears in the admin bar
   - Check that notification count updates correctly

2. **AJAX Polling Frequency**
   - Monitor browser developer tools Network tab
   - Verify polling requests occur at expected intervals
   - Test that polling stops when user is inactive
   - Confirm polling resumes when user returns to active state

3. **User Permission Handling**
   - Log in as users with different roles
   - Verify that notifications are appropriate for each role
   - Confirm that users only see notifications they're authorized to see
   - Test that guest users don't see admin notifications

4. **Performance Impact**
   - Monitor server resource usage during polling
   - Check page load times with polling active
   - Verify that polling doesn't interfere with other AJAX requests
   - Test with multiple users logged in simultaneously

### 3. Enhanced Knowledge Base Features

**Files to test:**
- includes/models/class-mets-kb-article-model.php
- admin/kb/class-mets-kb-admin-integration.php
- public/class-mets-public.php

**Test Procedures:**

1. **Article Versioning**
   - Create a new KB article
   - Edit the article multiple times
   - Verify that previous versions are saved
   - Test restoring a previous version
   - Confirm that version history is accessible

2. **Collaboration Tools**
   - Have multiple users edit the same article
   - Test comment functionality on articles
   - Verify that edit conflicts are handled appropriately
   - Check that user attribution is maintained for each edit

3. **Search Result Accuracy**
   - Create articles with various tags and categories
   - Search for specific terms
   - Verify that relevant articles appear in results
   - Test search filtering by category, tag, and date
   - Confirm that search results are sorted appropriately

4. **Frontend Display Consistency**
   - View KB articles on different devices
   - Check article formatting and styling
   - Test navigation between related articles
   - Verify that article metadata displays correctly
   - Confirm that print styles work properly

## Testing Tools

### Browser Developer Tools
- Use the Network tab to monitor AJAX requests
- Use the Console tab to check for JavaScript errors
- Use the Elements tab to inspect UI components
- Use the Performance tab to monitor resource usage

### WordPress Debugging
Enable debugging in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Plugin Debugging Tools
Use the built-in debugging tools in the plugin's debug directory:
- debug-tables.php - Database schema validation
- debug-security.php - Security configuration check
- debug-kb-edit.php - Knowledge base editing diagnostics

## Test Data Management

### Creating Test Data
1. Use the WordPress import/export tools for consistent test data
2. Create a set of standard test entities with known properties
3. Develop sample tickets with various statuses and priorities
4. Prepare a collection of KB articles with different categories and tags

### Resetting Test Data
1. Use the plugin's bulk operations to clear test data
2. Reset database tables to known states
3. Clear caches and transients between tests
4. Restore from database backups when needed

## Test Reporting

### Documenting Test Results
For each test, record:
1. Test date and tester
2. WordPress version and environment
3. Plugin version tested
4. Pass/fail result
5. Any issues encountered
6. Screenshots if applicable

### Issue Tracking
1. Create detailed bug reports for failed tests
2. Include steps to reproduce
3. Provide expected vs. actual results
4. Attach relevant logs or screenshots
5. Assign priority levels to issues

## Regression Testing

### When to Perform Regression Testing
1. After implementing new features
2. After fixing bugs
3. Before releasing new versions
4. When updating WordPress or other plugins

### Regression Test Procedures
1. Re-run all previously passing tests
2. Verify that new changes don't break existing functionality
3. Check integration points with other plugins
4. Test on different WordPress themes
5. Confirm compatibility with various browsers

## Performance Testing

### Load Testing
1. Simulate multiple concurrent users
2. Monitor server response times
3. Check database query performance
4. Verify memory usage remains stable

### Stress Testing
1. Test with large datasets
2. Verify system behavior under heavy load
3. Check error handling for resource exhaustion
4. Confirm graceful degradation when resources are limited

## Security Testing

### Input Validation
1. Test all form inputs with various data types
2. Attempt to inject malicious code
3. Verify proper sanitization of user input
4. Check for potential XSS vulnerabilities

### Access Control
1. Test role-based permissions thoroughly
2. Verify entity-specific access restrictions
3. Confirm that unauthorized users cannot access restricted features
4. Check that admin functions are properly protected

## Mobile Testing

### Responsive Design
1. Test on various mobile devices
2. Check layout and functionality on different screen sizes
3. Verify touch interactions work properly
4. Confirm that mobile-specific features function correctly

### Performance on Mobile
1. Monitor page load times on mobile networks
2. Check data usage for mobile users
3. Verify that AJAX requests work efficiently
4. Test offline functionality if available

## Accessibility Testing

### Screen Reader Compatibility
1. Test with popular screen readers
2. Verify proper heading structure
3. Check that all functionality is accessible via keyboard
4. Confirm ARIA attributes are used appropriately

### Color Contrast
1. Verify sufficient color contrast for text
2. Check that color is not the only means of conveying information
3. Test with various color blindness simulations
4. Confirm that focus indicators are visible

## Cross-browser Testing

### Supported Browsers
1. Test on latest versions of:
   - Chrome
   - Firefox
   - Safari
   - Edge
2. Check compatibility with older browser versions if needed
3. Verify that JavaScript features work across browsers
4. Confirm that CSS renders consistently

## Test Automation Considerations

While this guide focuses on manual testing, consider these areas for future automation:

1. Regression test suite for core functionality
2. API endpoint validation
3. Database schema verification
4. Performance benchmarking
5. Security scanning

## Test Maintenance

### Updating Test Procedures
1. Review and update test procedures with each release
2. Add new tests for new features
3. Remove obsolete tests
4. Update test data as needed

### Training
1. Ensure all testers understand the testing procedures
2. Provide documentation for new testers
3. Conduct regular testing training sessions
4. Share knowledge about edge cases and common issues

This testing guide should be updated as new features are added and testing procedures evolve.