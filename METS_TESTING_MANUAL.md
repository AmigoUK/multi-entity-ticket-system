# METS (Multi-Entity Ticket System) - Complete Testing Manual

## 📋 **Overview**
This manual provides step-by-step testing procedures for all METS features, forms, functions, and settings to identify any errors or missing components.

---

## 🏗️ **PHASE 1: INITIAL SETUP & INSTALLATION**

### ✅ **1.1 Plugin Installation**
- [ ] Plugin activates without errors
- [ ] Database tables are created successfully
- [ ] Default settings are populated
- [ ] No PHP errors in error logs
- [ ] Plugin appears in WordPress admin menu

**Test Steps:**
1. Navigate to `Plugins → Add New → Upload Plugin`
2. Upload METS plugin zip file
3. Activate plugin
4. Check `Tools → Site Health → Info → Database` for METS tables
5. Verify admin menu shows "METS Tickets"

**Expected Tables:**
- `wp_mets_entities`
- `wp_mets_tickets` 
- `wp_mets_ticket_replies`
- `wp_mets_attachments`
- `wp_mets_kb_articles`
- `wp_mets_audit_trail`
- `wp_mets_security_log`
- `wp_mets_rate_limits`

---

## 🎛️ **PHASE 2: ADMIN CONFIGURATION**

### ✅ **2.1 General Settings**
**Location:** `METS Tickets → Settings → General`

**Test Items:**
- [ ] Company/Organization name field
- [ ] Support email address field
- [ ] Ticket number prefix field
- [ ] Default ticket status dropdown
- [ ] Default ticket priority dropdown
- [ ] Portal header text field
- [ ] New ticket link text field
- [ ] New ticket link URL field
- [ ] Settings save successfully
- [ ] Settings persist after page reload

**Test Steps:**
1. Navigate to General Settings
2. Fill in all fields with test data
3. Click "Save Changes"
4. Reload page and verify data is saved
5. Test with special characters and long text

### ✅ **2.2 Entity Management**
**Location:** `METS Tickets → Entities`

**Test Items:**
- [ ] Create new entity
- [ ] Edit existing entity
- [ ] Delete entity
- [ ] Entity hierarchy (parent/child relationships)
- [ ] Entity status (active/inactive)
- [ ] Entity search functionality
- [ ] Bulk actions on entities

**Test Steps:**
1. Create parent entity: "Main Support"
2. Create child entity: "Technical Support" (under Main Support)
3. Create another child: "Billing Support" (under Main Support)
4. Test editing entity names and descriptions
5. Test deactivating/activating entities
6. Test deleting entities (check for tickets dependency)
7. Test search functionality with partial names

### ✅ **2.3 Ticket Categories**
**Location:** `METS Tickets → Settings → Categories`

**Test Items:**
- [ ] Add new categories
- [ ] Edit category names
- [ ] Delete categories
- [ ] Category display in forms
- [ ] Category validation

**Test Steps:**
1. Add categories: "Bug Report", "Feature Request", "General Inquiry"
2. Edit a category name
3. Delete a category
4. Check if categories appear in ticket forms
5. Test with special characters in category names

### ✅ **2.4 Ticket Statuses**
**Location:** `METS Tickets → Settings → Statuses`

**Test Items:**
- [ ] Add custom statuses
- [ ] Edit status labels and colors
- [ ] Delete statuses
- [ ] Status workflow logic
- [ ] Status permissions

**Test Steps:**
1. Verify default statuses: New, Open, In Progress, Resolved, Closed
2. Add custom status: "Waiting for Customer"
3. Change status colors
4. Test status transitions in tickets
5. Delete unused status

### ✅ **2.5 Ticket Priorities**
**Location:** `METS Tickets → Settings → Priorities`

**Test Items:**
- [ ] Add custom priorities
- [ ] Edit priority labels and colors
- [ ] Delete priorities
- [ ] Priority display and filtering

**Test Steps:**
1. Verify default priorities: Low, Normal, High, Urgent, Critical
2. Add custom priority: "Medium"
3. Change priority colors
4. Test priority assignment in tickets
5. Delete unused priority

### ✅ **2.6 Email Settings (SMTP)**
**Location:** `METS Tickets → Settings → Email`

**Test Items:**
- [ ] SMTP configuration
- [ ] Email templates
- [ ] Test email functionality
- [ ] Email queue system
- [ ] Email notifications

**Test Steps:**
1. Configure SMTP settings
2. Send test email
3. Check email templates for all notification types
4. Test email queue processing
5. Verify email logs

### ✅ **2.7 n8n Chat Integration**
**Location:** `METS Tickets → Settings → n8n Chat`

**Test Items:**
- [ ] Enable/disable chat
- [ ] Webhook URL configuration
- [ ] Chat appearance settings
- [ ] Chat positioning
- [ ] Mobile display settings
- [ ] Page restrictions

**Test Steps:**
1. Enable n8n chat
2. Enter webhook URL
3. Configure appearance (colors, title, subtitle)
4. Set position (bottom-right, bottom-left, etc.)
5. Test mobile display toggle
6. Configure page restrictions (all, specific, except)
7. Test chat widget on frontend

---

## 📝 **PHASE 3: TICKET MANAGEMENT**

### ✅ **3.1 Ticket Creation (Admin)**
**Location:** `METS Tickets → Add New`

**Test Items:**
- [ ] All form fields display correctly
- [ ] Entity dropdown populated
- [ ] Category dropdown populated
- [ ] Priority dropdown populated
- [ ] Status dropdown populated
- [ ] Customer information fields
- [ ] Ticket description editor
- [ ] File attachment functionality
- [ ] Assignment to agents
- [ ] Form validation
- [ ] Ticket number generation

**Test Steps:**
1. Navigate to Add New Ticket
2. Fill all required fields
3. Test entity selection
4. Test category selection
5. Test priority and status selection
6. Add customer information
7. Write ticket description with formatting
8. Upload multiple file attachments
9. Assign to an agent
10. Submit ticket and verify creation

### ✅ **3.2 Ticket List View**
**Location:** `METS Tickets → All Tickets`

**Test Items:**
- [ ] Ticket list displays correctly
- [ ] Sorting functionality
- [ ] Filtering by status
- [ ] Filtering by priority
- [ ] Filtering by entity
- [ ] Filtering by assigned agent
- [ ] Search functionality
- [ ] Bulk actions
- [ ] Pagination
- [ ] Status color coding

**Test Steps:**
1. View all tickets list
2. Test sorting by each column
3. Filter by different statuses
4. Filter by priorities
5. Filter by entities
6. Search by ticket number
7. Search by customer name
8. Search by keywords
9. Test bulk status changes
10. Test bulk assignment
11. Navigate through pagination

### ✅ **3.3 Ticket Detail View**
**Location:** `METS Tickets → [Individual Ticket]`

**Test Items:**
- [ ] Ticket information display
- [ ] Status change functionality
- [ ] Priority change functionality
- [ ] Assignment functionality
- [ ] Reply functionality
- [ ] Internal notes
- [ ] File attachments
- [ ] Activity timeline
- [ ] Customer information
- [ ] Related tickets

**Test Steps:**
1. Open a ticket detail page
2. Change ticket status
3. Change ticket priority
4. Assign/reassign ticket
5. Add public reply
6. Add internal note
7. Upload attachment to reply
8. View activity timeline
9. Check customer information panel
10. Look for related tickets section

### ✅ **3.4 Ticket Replies & Communication**

**Test Items:**
- [ ] Public replies
- [ ] Internal notes
- [ ] Reply formatting
- [ ] File attachments in replies
- [ ] Email notifications
- [ ] Reply permissions
- [ ] Auto-responses

**Test Steps:**
1. Add public reply to ticket
2. Add internal note (not visible to customer)
3. Test rich text formatting in replies
4. Attach files to replies
5. Verify email notifications sent
6. Test different user role reply permissions
7. Check for auto-response triggers

---

## 🎫 **PHASE 4: FRONTEND FUNCTIONALITY**

### ✅ **4.1 Ticket Submission Form**
**Shortcode:** `[ticket_form]`

**Test Items:**
- [ ] Form displays correctly
- [ ] All fields functional
- [ ] Entity selection
- [ ] Category selection
- [ ] File upload
- [ ] Form validation
- [ ] Success message
- [ ] Email notifications
- [ ] Knowledge base integration

**Test Steps:**
1. Create a page with `[ticket_form]` shortcode
2. View page on frontend
3. Fill out form with valid data
4. Test form validation with missing fields
5. Test file uploads (multiple files)
6. Test file type restrictions
7. Submit form and verify success message
8. Check if ticket appears in admin
9. Test knowledge base search integration
10. Test on mobile devices

### ✅ **4.2 Customer Portal**
**Shortcode:** `[ticket_portal]`

**Test Items:**
- [ ] Portal displays correctly
- [ ] User authentication
- [ ] Ticket list for logged-in user
- [ ] Ticket filtering
- [ ] Ticket detail view
- [ ] Reply functionality
- [ ] File attachments
- [ ] Ticket status visibility
- [ ] Pagination

**Test Steps:**
1. Create a page with `[ticket_portal]` shortcode
2. View page without login (should show login prompt)
3. Login as test customer
4. View customer's tickets
5. Filter tickets by status
6. Open ticket details
7. Add reply to ticket
8. Upload attachment with reply
9. Test pagination if many tickets
10. Test mobile responsiveness

### ✅ **4.3 Knowledge Base (If Implemented)**

**Test Items:**
- [ ] Article display
- [ ] Search functionality
- [ ] Category browsing
- [ ] Article voting (helpful/not helpful)
- [ ] Related articles
- [ ] Search integration with ticket form

**Test Steps:**
1. Create knowledge base articles
2. Test article search
3. Browse by categories
4. Vote on article helpfulness
5. Check related articles display
6. Test integration with ticket form

---

## 🔧 **PHASE 5: ADVANCED FEATURES**

### ✅ **5.1 User Roles & Permissions**

**Test Items:**
- [ ] Agent role permissions
- [ ] Manager role permissions
- [ ] Customer role permissions
- [ ] Entity-specific permissions
- [ ] Bulk operations permissions

**Test Steps:**
1. Create users with different roles
2. Test agent access levels
3. Test manager access levels
4. Test customer limitations
5. Test entity-specific restrictions
6. Test bulk operations access

### ✅ **5.2 Bulk Operations**
**Location:** `METS Tickets → Bulk Operations`

**Test Items:**
- [ ] Bulk status changes
- [ ] Bulk assignments
- [ ] Bulk priority changes
- [ ] Bulk entity transfers
- [ ] Bulk exports
- [ ] Progress tracking

**Test Steps:**
1. Select multiple tickets
2. Bulk change status
3. Bulk assign to agent
4. Bulk change priority
5. Bulk transfer between entities
6. Export selected tickets
7. Monitor progress indicators

### ✅ **5.3 Reporting & Analytics**

**Test Items:**
- [ ] Ticket statistics
- [ ] Agent performance
- [ ] Response time metrics
- [ ] Entity performance
- [ ] Export functionality
- [ ] Date range filtering

**Test Steps:**
1. View ticket statistics dashboard
2. Check agent performance reports
3. Review response time metrics
4. Analyze entity performance
5. Export reports to CSV/PDF
6. Test date range filtering

### ✅ **5.4 Security Features**

**Test Items:**
- [ ] Rate limiting
- [ ] Security audit logs
- [ ] Access controls
- [ ] File upload security
- [ ] SQL injection protection
- [ ] XSS protection

**Test Steps:**
1. Test rate limiting on form submissions
2. Check security audit logs
3. Test unauthorized access attempts
4. Upload malicious files (should be blocked)
5. Test for SQL injection vulnerabilities
6. Test for XSS vulnerabilities

---

## 🚀 **PHASE 6: PERFORMANCE & COMPATIBILITY**

### ✅ **6.1 Performance Testing**

**Test Items:**
- [ ] Page load times
- [ ] Database query performance
- [ ] Large dataset handling
- [ ] File upload performance
- [ ] Search performance
- [ ] Caching effectiveness

**Test Steps:**
1. Measure page load times with dev tools
2. Monitor database queries (Query Monitor plugin)
3. Test with 1000+ tickets
4. Upload large files
5. Search with complex queries
6. Test caching mechanisms

### ✅ **6.2 Browser Compatibility**

**Test Items:**
- [ ] Chrome compatibility
- [ ] Firefox compatibility
- [ ] Safari compatibility
- [ ] Edge compatibility
- [ ] Mobile browsers
- [ ] JavaScript functionality

**Test Steps:**
1. Test in Chrome (latest)
2. Test in Firefox (latest)
3. Test in Safari (if available)
4. Test in Edge (latest)
5. Test on mobile Chrome/Safari
6. Verify JavaScript features work

### ✅ **6.3 WordPress Compatibility**

**Test Items:**
- [ ] WordPress version compatibility
- [ ] Theme compatibility
- [ ] Plugin conflicts
- [ ] Multisite compatibility
- [ ] Database prefix handling

**Test Steps:**
1. Test with current WordPress version
2. Test with different themes
3. Test with common plugins activated
4. Test on multisite installation
5. Test with custom database prefix

---

## 🔍 **PHASE 7: ERROR HANDLING & EDGE CASES**

### ✅ **7.1 Error Conditions**

**Test Items:**
- [ ] Database connection errors
- [ ] File upload errors
- [ ] Email sending failures
- [ ] Invalid data submission
- [ ] Missing permissions
- [ ] Network timeouts

**Test Steps:**
1. Simulate database disconnect
2. Upload oversized files
3. Configure invalid SMTP settings
4. Submit forms with invalid data
5. Test with insufficient permissions
6. Test with slow network conditions

### ✅ **7.2 Edge Cases**

**Test Items:**
- [ ] Very long ticket descriptions
- [ ] Special characters in all fields
- [ ] Multiple file uploads
- [ ] Concurrent user actions
- [ ] Missing entities/categories
- [ ] Deleted user assignments

**Test Steps:**
1. Create ticket with 10,000+ character description
2. Use unicode, emojis, special chars in all fields
3. Upload 10+ files simultaneously
4. Have multiple users edit same ticket
5. Delete entity with existing tickets
6. Delete user assigned to tickets

---

## 📊 **TESTING CHECKLIST SUMMARY**

### **Critical Features (Must Work)**
- [ ] Ticket creation (admin & frontend)
- [ ] Ticket viewing and management
- [ ] Customer portal functionality
- [ ] Email notifications
- [ ] File attachments
- [ ] User authentication
- [ ] Basic search and filtering

### **Important Features (Should Work)**
- [ ] Advanced filtering and sorting
- [ ] Bulk operations
- [ ] Knowledge base integration
- [ ] Reporting and analytics
- [ ] n8n chat integration
- [ ] Security features

### **Nice-to-Have Features (May Have Issues)**
- [ ] Advanced customizations
- [ ] Third-party integrations
- [ ] Complex workflows
- [ ] Advanced permissions

---

## 🐛 **BUG REPORTING TEMPLATE**

When you find issues, document them using this format:

```markdown
**Bug ID:** BUG-001
**Severity:** High/Medium/Low
**Area:** Frontend/Admin/API/Performance
**Description:** Brief description of the issue
**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three
**Expected Result:** What should happen
**Actual Result:** What actually happens
**Browser/Environment:** Chrome 120, WordPress 6.4, PHP 8.1
**Screenshots:** [If applicable]
**Error Messages:** [Any error messages]
```

---

## 📝 **TESTING NOTES**

- Test with fresh WordPress installation
- Use different user roles for testing
- Test on both desktop and mobile
- Document all findings
- Take screenshots of issues
- Check browser console for JavaScript errors
- Monitor PHP error logs
- Test with different themes
- Verify database integrity after major operations

---

**Testing Completion:** ___% (Fill in as you complete sections)

**Last Updated:** [Date]
**Tested By:** [Your Name]
**WordPress Version:** [Version]
**PHP Version:** [Version]