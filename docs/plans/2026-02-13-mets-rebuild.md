# METS Plugin Incremental Rebuild Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all identified security bugs, UX issues, and architectural problems in the METS WordPress plugin through an incremental refactor that keeps the plugin functional at every step.

**Architecture:** Incremental Strangler Fig pattern. Phase 1 patches security bugs in-place. Phase 2 improves UX across JS/CSS/PHP. Phase 3 extracts the Admin God class into focused handler classes with a new service layer.

**Tech Stack:** PHP 7.4+, WordPress 5.0+, MySQL 5.6+, jQuery, PHPUnit 10.3+

---

## Phase 1: Security & Critical Bug Fixes

### Task 1: Fix Race Condition in Ticket Number Generation

**Files:**
- Modify: `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php:49-106`
- Test: `tests/unit/test-mets-ticket-model.php`

**Step 1: Write the failing test**

Add to `tests/unit/test-mets-ticket-model.php`:

```php
/**
 * Test that ticket number generation uses database locking
 * to prevent race conditions.
 */
public function test_create_ticket_generates_unique_numbers() {
    // Create two tickets for the same entity in quick succession
    $entity_id = $this->mets_factory->create_entity(['slug' => 'testco']);

    $ticket1_id = $this->mets_factory->create_ticket([
        'entity_id' => $entity_id,
        'subject' => 'First ticket',
        'description' => 'Test',
        'customer_name' => 'User One',
        'customer_email' => 'one@example.com',
    ]);

    $ticket2_id = $this->mets_factory->create_ticket([
        'entity_id' => $entity_id,
        'subject' => 'Second ticket',
        'description' => 'Test',
        'customer_name' => 'User Two',
        'customer_email' => 'two@example.com',
    ]);

    global $wpdb;
    $table = $wpdb->prefix . 'mets_tickets';
    $num1 = $wpdb->get_var($wpdb->prepare("SELECT ticket_number FROM $table WHERE id = %d", $ticket1_id));
    $num2 = $wpdb->get_var($wpdb->prepare("SELECT ticket_number FROM $table WHERE id = %d", $ticket2_id));

    $this->assertNotEquals($num1, $num2, 'Two tickets should never share a ticket number');

    // Verify sequential numbering: second ticket should have sequence = first + 1
    $seq1 = intval(substr($num1, -4));
    $seq2 = intval(substr($num2, -4));
    $this->assertEquals($seq1 + 1, $seq2, 'Ticket numbers should be sequential');
}
```

**Step 2: Run test to verify it passes (baseline)**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-model.php --filter=test_create_ticket_generates_unique_numbers -v`

Expected: This may pass or fail depending on test factory. Establishes baseline.

**Step 3: Replace `generate_ticket_number` with locked version**

Replace the entire method at `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php:49-106` with:

```php
private function generate_ticket_number( $entity_id ) {
    global $wpdb;

    // Get entity slug for prefix
    $entity_slug = $wpdb->get_var( $wpdb->prepare(
        "SELECT slug FROM {$wpdb->prefix}mets_entities WHERE id = %d",
        $entity_id
    ) );

    $prefix = strtoupper( substr( $entity_slug, 0, 3 ) );
    if ( empty( $prefix ) ) {
        $prefix = 'TKT';
    }

    $date_prefix = date( 'Y' ) . date( 'm' );

    // Acquire a named lock to prevent concurrent generation
    $lock_name = 'mets_ticket_num_' . $entity_id;
    $lock_acquired = $wpdb->get_var(
        $wpdb->prepare( "SELECT GET_LOCK(%s, 5)", $lock_name )
    );

    if ( ! $lock_acquired ) {
        // Lock timeout — fall back to timestamp-based ID
        return sprintf( '%s-%s-%s', $prefix, $date_prefix, time() );
    }

    try {
        $max_sequence = $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED))
            FROM {$this->table_name}
            WHERE entity_id = %d
            AND ticket_number LIKE %s
            AND CHAR_LENGTH(SUBSTRING_INDEX(ticket_number, '-', -1)) = 4
            AND SUBSTRING_INDEX(ticket_number, '-', -1) REGEXP '^[0-9]{4}$'",
            $entity_id,
            $prefix . '-' . $date_prefix . '-%'
        ) );

        $sequence = $max_sequence ? intval( $max_sequence ) + 1 : 1;
        return sprintf( '%s-%s-%04d', $prefix, $date_prefix, $sequence );
    } finally {
        $wpdb->query(
            $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_name )
        );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-model.php -v`

Expected: All ticket model tests PASS.

**Step 5: Commit**

```bash
git add multi-entity-ticket-system/includes/models/class-mets-ticket-model.php tests/unit/test-mets-ticket-model.php
git commit -m "fix: use database lock for ticket number generation to prevent race condition"
```

---

### Task 2: Fix Null Pointer Crash on get_user_by()

**Files:**
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php:845-848`

**Step 1: Write the fix**

Replace lines 845-848 in `admin/class-mets-admin.php`:

```php
// BEFORE (lines 845-848):
if ( $current_ticket->assigned_to != $data['assigned_to'] ) {
    $old_user = $current_ticket->assigned_to ? get_user_by( 'ID', $current_ticket->assigned_to )->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
    $new_user = $data['assigned_to'] ? get_user_by( 'ID', $data['assigned_to'] )->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
    $changes[] = sprintf( __( 'Assignment changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_user, $new_user );
}
```

Replace with:

```php
if ( $current_ticket->assigned_to != $data['assigned_to'] ) {
    $old_user_obj = $current_ticket->assigned_to ? get_user_by( 'ID', $current_ticket->assigned_to ) : false;
    $old_user = $old_user_obj ? $old_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
    $new_user_obj = $data['assigned_to'] ? get_user_by( 'ID', $data['assigned_to'] ) : false;
    $new_user = $new_user_obj ? $new_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
    $changes[] = sprintf( __( 'Assignment changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_user, $new_user );
}
```

**Step 2: Search for other instances of this pattern**

Run: `grep -n 'get_user_by.*->display_name' multi-entity-ticket-system/admin/class-mets-admin.php`

Fix every match with the same null-safe pattern.

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "fix: add null checks for get_user_by() to prevent crash on deleted users"
```

---

### Task 3: Add Missing Capability Checks to AJAX Handlers

**Files:**
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php:2167,2204,2216`

**Step 1: Audit all AJAX handlers**

The following admin AJAX handlers are missing `current_user_can()` checks:

| Method | Line | Fix |
|--------|------|-----|
| `ajax_search_entities()` | 2167 | Add `current_user_can('edit_tickets')` |
| `ajax_assign_ticket()` | 2204 | Add `current_user_can('edit_tickets')` |
| `ajax_change_ticket_status()` | 2216 | Add `current_user_can('edit_tickets')` |

Other handlers (`ajax_get_entity_agents`, `ajax_check_workflow_transition`, etc.) already have capability checks.

**Step 2: Add capability checks**

For each handler listed above, add after the nonce check line:

```php
if ( ! current_user_can( 'edit_tickets' ) ) {
    wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
}
```

Example for `ajax_search_entities()` (line 2167-2171):

```php
public function ajax_search_entities() {
    check_ajax_referer( 'mets_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_tickets' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
    }

    // Placeholder for entity search
    wp_send_json_success( array() );
}
```

Apply same pattern to `ajax_assign_ticket()` and `ajax_change_ticket_status()`.

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "fix: add missing capability checks to AJAX handlers"
```

---

### Task 4: Fix Improper JSON Responses in Core

**Files:**
- Modify: `multi-entity-ticket-system/includes/class-mets-core.php:837-953`

**Step 1: Replace wp_die(json_encode(...)) with wp_send_json_*()**

In `class-mets-core.php`, find all instances of:
```php
wp_die( json_encode( array( 'success' => false, 'data' => '...' ) ) );
```

Replace with:
```php
wp_send_json_error( array( 'message' => __( '...', METS_TEXT_DOMAIN ) ) );
```

And replace:
```php
wp_die( json_encode( array( 'success' => true, 'data' => $result ) ) );
```

With:
```php
wp_send_json_success( $result );
```

**Specific replacements in `class-mets-core.php`:**

| Line | Before | After |
|------|--------|-------|
| 841 | `wp_die( json_encode( array( 'success' => false, 'data' => 'Permission denied' ) ) )` | `wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) )` |
| 847 | `wp_die( json_encode( array( 'success' => true, 'data' => $audit_report ) ) )` | `wp_send_json_success( $audit_report )` |
| 859 | Same pattern as 841 | Same fix |
| 875 | `wp_die( json_encode( array( 'success' => $result ) ) )` | `if ($result) { wp_send_json_success(); } else { wp_send_json_error(); }` |
| 887 | Same as 841 | Same fix |
| 897-900 | `wp_die( json_encode( array( 'success' => true, 'data' => sprintf(...) ) ) )` | `wp_send_json_success( array( 'message' => sprintf(...) ) )` |
| 912 | Same as 841 | Same fix |
| 943 | Same as 841 | Same fix |
| 949-952 | Same as 897-900 | Same fix |

**Step 2: Commit**

```bash
git add multi-entity-ticket-system/includes/class-mets-core.php
git commit -m "fix: replace wp_die(json_encode()) with wp_send_json_success/error"
```

---

### Task 5: Harden SQL Field Whitelist in KB Article Model

**Files:**
- Modify: `multi-entity-ticket-system/includes/models/class-mets-kb-article-model.php:425-438`

**Step 1: Add whitelist validation**

Replace lines 425-438:

```php
public function update_helpful_count( $id, $helpful = true ) {
    global $wpdb;

    $allowed_fields = array( 'helpful_count', 'not_helpful_count' );
    $field = $helpful ? 'helpful_count' : 'not_helpful_count';

    if ( ! in_array( $field, $allowed_fields, true ) ) {
        return false;
    }

    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$this->table_name} SET {$field} = {$field} + 1 WHERE id = %d",
            $id
        )
    );

    return $result !== false;
}
```

**Step 2: Commit**

```bash
git add multi-entity-ticket-system/includes/models/class-mets-kb-article-model.php
git commit -m "fix: add field whitelist validation for SQL in KB article model"
```

---

### Task 6: Add User Validation on Ticket Assignment

**Files:**
- Modify: `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php:306-309`
- Test: `tests/unit/test-mets-ticket-model.php`

**Step 1: Write the failing test**

Add to `tests/unit/test-mets-ticket-model.php`:

```php
/**
 * Test that assigning a nonexistent user is rejected.
 */
public function test_update_ticket_rejects_invalid_assignee() {
    $ticket_id = $this->mets_factory->create_ticket();

    $ticket_model = new METS_Ticket_Model();
    $result = $ticket_model->update($ticket_id, [
        'assigned_to' => 999999, // nonexistent user
    ]);

    $this->assertInstanceOf(WP_Error::class, $result);
    $this->assertEquals('invalid_assignee', $result->get_error_code());
}
```

**Step 2: Run test to verify it fails**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-model.php --filter=test_update_ticket_rejects_invalid_assignee -v`

Expected: FAIL (currently accepts any integer)

**Step 3: Add validation**

In `class-mets-ticket-model.php`, replace lines 306-309:

```php
// BEFORE:
if ( isset( $data['assigned_to'] ) ) {
    $update_data['assigned_to'] = ! empty( $data['assigned_to'] ) ? intval( $data['assigned_to'] ) : null;
    $format[] = '%d';
}
```

With:

```php
if ( isset( $data['assigned_to'] ) ) {
    if ( ! empty( $data['assigned_to'] ) ) {
        $assignee = get_userdata( intval( $data['assigned_to'] ) );
        if ( ! $assignee ) {
            return new WP_Error( 'invalid_assignee', __( 'Assigned user does not exist.', METS_TEXT_DOMAIN ) );
        }
        $update_data['assigned_to'] = intval( $data['assigned_to'] );
    } else {
        $update_data['assigned_to'] = null;
    }
    $format[] = '%d';
}
```

**Step 4: Run test to verify it passes**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-model.php -v`

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add multi-entity-ticket-system/includes/models/class-mets-ticket-model.php tests/unit/test-mets-ticket-model.php
git commit -m "fix: validate assigned user exists before accepting ticket assignment"
```

---

### Task 7: Fix Loose Type Comparisons

**Files:**
- Modify: `multi-entity-ticket-system/includes/models/class-mets-ticket-reply-model.php:65`
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php` (lines 790, 815, 829, 837, 845)

**Step 1: Fix reply model**

In `class-mets-ticket-reply-model.php`, line 65, change:
```php
} elseif ( empty( $data['user_id'] ) || $data['user_id'] == 0 ) {
```
To:
```php
} elseif ( empty( $data['user_id'] ) || intval( $data['user_id'] ) === 0 ) {
```

**Step 2: Fix admin comparisons**

In `admin/class-mets-admin.php`, change `!=` to `!==` for all status/priority/category/assignment comparisons in the change tracking block (lines 790-849). These compare string values and should use strict comparison:

- Line 790: `$current_ticket->status != $data['status']` → `$current_ticket->status !== $data['status']`
- Line 815: same pattern → `!==`
- Line 829: same pattern → `!==`
- Line 837: same pattern → `!==`
- Line 845: same pattern → `!==`

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/includes/models/class-mets-ticket-reply-model.php multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "fix: use strict type comparisons for status/assignment checks"
```

---

### Task 8: Remove Debug error_log() Calls

**Files:**
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php`
- Modify: `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php`

**Step 1: Find all debug error_log calls**

Run: `grep -n "error_log.*\[METS\]" multi-entity-ticket-system/admin/class-mets-admin.php multi-entity-ticket-system/includes/models/class-mets-ticket-model.php`

**Step 2: Remove or gate behind WP_DEBUG**

Remove all `error_log('[METS] ...')` lines that are debug logging (showing data dumps, redirect URLs, form processing progress). Keep error_log calls that report actual errors (e.g., `error_log('[METS] Ticket creation failed...')`).

For error reporting lines, wrap in WP_DEBUG check:
```php
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( '[METS] Ticket creation failed. MySQL Error: ' . $wpdb->last_error );
}
```

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin.php multi-entity-ticket-system/includes/models/class-mets-ticket-model.php
git commit -m "fix: remove debug error_log calls, gate error reporting behind WP_DEBUG"
```

---

## Phase 2: UX Improvements

### Task 9: Create Reusable AJAX Wrapper with Loading States

**Files:**
- Modify: `multi-entity-ticket-system/assets/js/mets-public.js:695-714`
- Modify: `multi-entity-ticket-system/assets/css/mets-public.css` (append)

**Step 1: Add loading spinner CSS**

Append to `assets/css/mets-public.css`:

```css
/* Loading States */
.mets-btn-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}

.mets-btn-loading::after {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: mets-spin 0.6s linear infinite;
}

@keyframes mets-spin {
    to { transform: translateY(-50%) rotate(360deg); }
}

.mets-notice {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 16px;
    font-size: 14px;
}

.mets-notice-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.mets-notice-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
```

**Step 2: Replace `makeAjaxRequest` helper**

Replace lines 695-714 in `mets-public.js`:

```javascript
/**
 * Reusable AJAX helper with loading states and error handling.
 *
 * @param {string}   action   WordPress AJAX action name
 * @param {object}   data     Request payload
 * @param {function} callback Success callback receiving response.data
 * @param {object}   opts     Optional: { button: jQuery button element, errorContainer: jQuery element }
 */
function makeAjaxRequest(action, data, callback, opts) {
    opts = opts || {};
    var $btn = opts.button || null;
    var $errorContainer = opts.errorContainer || null;

    if ($btn) {
        $btn.addClass('mets-btn-loading').prop('disabled', true);
    }
    if ($errorContainer) {
        $errorContainer.empty().hide();
    }

    $.ajax({
        url: mets_public_ajax.ajax_url,
        type: 'POST',
        timeout: 30000,
        data: $.extend({
            action: action,
            nonce: mets_public_ajax.nonce
        }, data),
        success: function(response) {
            if (response.success) {
                callback(response.data);
            } else {
                var msg = (response.data && response.data.message) || 'An error occurred. Please try again.';
                if ($errorContainer) {
                    $errorContainer.html('<div class="mets-notice mets-notice-error">' + msg + '</div>').show();
                }
            }
        },
        error: function(xhr, status) {
            var msg = status === 'timeout'
                ? 'The request timed out. Please check your connection and try again.'
                : 'A network error occurred. Please try again.';
            if ($errorContainer) {
                $errorContainer.html('<div class="mets-notice mets-notice-error">' + msg + '</div>').show();
            }
        },
        complete: function() {
            if ($btn) {
                $btn.removeClass('mets-btn-loading').prop('disabled', false);
            }
        }
    });
}
```

**Step 3: Update existing AJAX calls to use loading states**

For the entity search (line 52-69), the KB search (line 170-200), and the ticket list filter AJAX calls — pass the relevant button and error container to `makeAjaxRequest`. Example for entity search:

```javascript
function performEntitySearch(searchTerm) {
    makeAjaxRequest('mets_search_entities_public', { search: searchTerm }, function(data) {
        displaySearchResults(data.entities);
    });
}
```

**Step 4: Commit**

```bash
git add multi-entity-ticket-system/assets/js/mets-public.js multi-entity-ticket-system/assets/css/mets-public.css
git commit -m "feat: add reusable AJAX wrapper with loading states, timeouts, and error display"
```

---

### Task 10: Improve Form Validation with Field-Level Feedback

**Files:**
- Modify: `multi-entity-ticket-system/assets/js/mets-public.js:360-406`
- Modify: `multi-entity-ticket-system/assets/css/mets-public.css` (append)

**Step 1: Add validation CSS**

Append to `assets/css/mets-public.css`:

```css
/* Field Validation */
.mets-field-error {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 1px #dc2626;
}

.mets-field-error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

.mets-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
```

**Step 2: Replace validation block**

Replace lines 360-406 in `mets-public.js` with:

```javascript
// Clear previous messages
successMessage.hide().empty();
errorMessage.hide().empty();

// Reset field error states
form.find('.mets-field-error').removeClass('mets-field-error').removeAttr('aria-invalid');
form.find('.mets-field-error-message').remove();

// Validate required fields
var isValid = true;
var firstErrorField = null;

form.find('[required]').each(function() {
    var $field = $(this);
    if (!$field.val().trim()) {
        $field.addClass('mets-field-error').attr('aria-invalid', 'true');
        var label = $field.closest('.mets-form-group').find('label').text() || 'This field';
        $field.after('<span class="mets-field-error-message" role="alert">' + label + ' is required.</span>');
        if (!firstErrorField) firstErrorField = $field;
        isValid = false;
    }
});

// Email validation
var emailField = form.find('input[type="email"]');
if (emailField.length > 0 && emailField.val().trim()) {
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(emailField.val())) {
        emailField.addClass('mets-field-error').attr('aria-invalid', 'true');
        emailField.after('<span class="mets-field-error-message" role="alert">Please enter a valid email address.</span>');
        if (!firstErrorField) firstErrorField = emailField;
        isValid = false;
    }
}
```

Then after the file size validation block, replace the `if (!isValid)` block with:

```javascript
if (!isValid) {
    if (firstErrorField) firstErrorField.focus();
    return false;
}
```

**Step 3: Add aria-live region to form**

In `public/class-mets-public.php`, find the ticket form output and add after the opening form tag:
```php
<div aria-live="polite" class="mets-sr-only" id="mets-form-status"></div>
```

**Step 4: Commit**

```bash
git add multi-entity-ticket-system/assets/js/mets-public.js multi-entity-ticket-system/assets/css/mets-public.css multi-entity-ticket-system/public/class-mets-public.php
git commit -m "feat: add field-level validation with accessible error messages"
```

---

### Task 11: Add Accessibility (ARIA + Focus Styles)

**Files:**
- Modify: `multi-entity-ticket-system/assets/css/mets-public.css` (append)
- Modify: `multi-entity-ticket-system/assets/css/mets-admin.css` (append)

**Step 1: Add focus-visible styles**

Append to `assets/css/mets-public.css`:

```css
/* Focus Styles */
.mets-ticket-form input:focus-visible,
.mets-ticket-form select:focus-visible,
.mets-ticket-form textarea:focus-visible,
.mets-ticket-form button:focus-visible,
.mets-btn:focus-visible,
.mets-entity-item:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

/* Keyboard-navigable entity dropdown */
.mets-entity-item {
    cursor: pointer;
    padding: 8px 12px;
}

.mets-entity-item:focus-visible,
.mets-entity-item:hover {
    background-color: #f0f0f0;
}
```

Append to `assets/css/mets-admin.css`:

```css
/* Focus Styles - Admin */
.mets-form-section input:focus-visible,
.mets-form-section select:focus-visible,
.mets-form-section textarea:focus-visible,
.mets-form-section button:focus-visible {
    outline: 2px solid #2271b1;
    outline-offset: 2px;
}
```

**Step 2: Commit**

```bash
git add multi-entity-ticket-system/assets/css/mets-public.css multi-entity-ticket-system/assets/css/mets-admin.css
git commit -m "feat: add focus-visible styles for keyboard accessibility"
```

---

### Task 12: Add Mobile Responsiveness

**Files:**
- Modify: `multi-entity-ticket-system/assets/css/mets-admin.css` (append)
- Modify: `multi-entity-ticket-system/assets/css/mets-public.css` (append)

**Step 1: Add responsive admin table styles**

Append to `assets/css/mets-admin.css`:

```css
/* Responsive Tables */
@media screen and (max-width: 782px) {
    .wp-list-table .column-ticket_number,
    .wp-list-table .column-subject,
    .wp-list-table .column-customer,
    .wp-list-table .column-entity,
    .wp-list-table .column-priority,
    .wp-list-table .column-assigned_to,
    .wp-list-table .column-created_at,
    .wp-list-table .column-updated_at {
        width: auto;
    }

    .mets-admin-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
```

**Step 2: Add responsive public form styles**

Append to `assets/css/mets-public.css`:

```css
/* Responsive Form */
@media screen and (max-width: 480px) {
    .mets-ticket-form {
        padding: 16px;
    }

    .mets-form-row {
        flex-direction: column;
        gap: 0;
    }

    .mets-form-row .mets-form-group {
        margin-bottom: 16px;
    }
}
```

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/assets/css/mets-admin.css multi-entity-ticket-system/assets/css/mets-public.css
git commit -m "feat: add mobile responsive styles for admin tables and public forms"
```

---

### Task 13: Fix Event Handler Duplication

**Files:**
- Modify: `multi-entity-ticket-system/assets/js/mets-public.js`

**Step 1: Namespace all event handlers**

Search for all `.on('click'`, `.on('change'`, `.on('input'` patterns in `mets-public.js`.

For each delegated handler using `$(document).on(...)`, add `.mets` namespace:

```javascript
// BEFORE:
$(document).on('input', '.mets-entity-search', function() { ... });

// AFTER:
$(document).off('input.mets', '.mets-entity-search').on('input.mets', '.mets-entity-search', function() { ... });
```

For directly-bound handlers, namespace them:

```javascript
// BEFORE:
$('#kb-gate-search-again').on('click', function() { ... });

// AFTER:
$('#kb-gate-search-again').off('click.mets').on('click.mets', function() { ... });
```

Apply to all event bindings in the file (~10 handlers).

**Step 2: Commit**

```bash
git add multi-entity-ticket-system/assets/js/mets-public.js
git commit -m "fix: namespace all event handlers to prevent duplicate binding"
```

---

### Task 14: Fix KB Search Race Condition

**Files:**
- Modify: `multi-entity-ticket-system/assets/js/mets-public.js:170-200`

**Step 1: Add request cancellation**

At the top of the file (inside the IIFE, after `var kbSearchTimeout;`), add:

```javascript
var currentKBRequest = null;
```

Then modify `performMandatoryKBSearch()` (lines 170-200):

```javascript
function performMandatoryKBSearch() {
    var searchTerm = $('#kb-gate-search').val().trim();

    if (searchTerm.length < 3) {
        $('#kb-gate-results').hide();
        return;
    }

    // Cancel any in-flight request
    if (currentKBRequest && currentKBRequest.readyState !== 4) {
        currentKBRequest.abort();
    }

    $('#kb-gate-results-list').html('<div class="kb-gate-loading"><p>Searching our knowledge base...</p></div>');
    $('#kb-gate-results').show();

    currentKBRequest = $.ajax({
        url: mets_public_ajax.ajax_url,
        type: 'POST',
        timeout: 15000,
        data: {
            action: 'mets_search_kb_articles',
            nonce: mets_public_ajax.nonce,
            search: searchTerm,
            entity_id: $('input[name="entity_id"]').val() || 0
        },
        success: function(response) {
            if (response.success) {
                displayKBGateResults(response.data.articles);
            } else {
                $('#kb-gate-results-list').html('<div class="kb-gate-error"><p>Search failed. Please try a different search term.</p></div>');
            }
        },
        error: function(xhr, status) {
            if (status !== 'abort') {
                $('#kb-gate-results-list').html('<div class="kb-gate-error"><p>Search request failed. Please try again.</p></div>');
            }
        }
    });
}
```

**Step 2: Commit**

```bash
git add multi-entity-ticket-system/assets/js/mets-public.js
git commit -m "fix: cancel previous KB search requests to prevent race conditions"
```

---

## Phase 3: Architecture Refactoring

### Task 15: Extract AJAX Handlers from Admin God Class

**Files:**
- Create: `multi-entity-ticket-system/admin/class-mets-admin-ajax.php`
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php`

**Step 1: Create the AJAX handler class**

Create `admin/class-mets-admin-ajax.php`. Move ALL methods starting with `ajax_` from `class-mets-admin.php` into this new class. The new class receives a reference to the admin class for shared state:

```php
<?php
/**
 * AJAX handlers for admin operations
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.1.0
 */

class METS_Admin_Ajax {

    /**
     * @var string Plugin name
     */
    private $plugin_name;

    /**
     * @var string Plugin version
     */
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // ... paste all ajax_* methods here, preserving their exact implementations
}
```

**Step 2: Update admin class constructor to delegate**

In `class-mets-admin.php` constructor, replace all `add_action('wp_ajax_mets_*', array($this, 'ajax_*'))` calls with delegation to the new class:

```php
require_once METS_PLUGIN_PATH . 'admin/class-mets-admin-ajax.php';
$ajax_handler = new METS_Admin_Ajax( $this->plugin_name, $this->version );

add_action( 'wp_ajax_mets_refresh_sla_widget', array( $ajax_handler, 'ajax_refresh_sla_widget' ) );
// ... etc for all AJAX hooks
```

**Step 3: Remove ajax_* methods from admin class**

Delete all `ajax_*` methods from `class-mets-admin.php`. This should remove ~1,500 lines.

**Step 4: Verify plugin still works**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit -v`

Expected: All tests pass.

**Step 5: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin-ajax.php multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "refactor: extract AJAX handlers from admin class into METS_Admin_Ajax"
```

---

### Task 16: Extract Settings Management from Admin Class

**Files:**
- Create: `multi-entity-ticket-system/admin/class-mets-admin-settings.php`
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php`

**Step 1: Identify settings methods**

Search `class-mets-admin.php` for all methods related to settings pages: methods containing "settings", "option", "config", "general_settings", "email_settings", "sla_settings", etc.

**Step 2: Create METS_Admin_Settings class**

Move all settings display and processing methods. Follow same pattern as Task 15.

**Step 3: Update admin class to delegate**

Wire the new class into the admin hook registration.

**Step 4: Run tests**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit -v`

**Step 5: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin-settings.php multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "refactor: extract settings management from admin class into METS_Admin_Settings"
```

---

### Task 17: Introduce Ticket Service Layer

**Files:**
- Create: `multi-entity-ticket-system/includes/services/class-mets-ticket-service.php`
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php` (form handlers)
- Test: `tests/unit/test-mets-ticket-service.php`

**Step 1: Write a test for the service**

Create `tests/unit/test-mets-ticket-service.php`:

```php
<?php
class Test_METS_Ticket_Service extends METS_Test_Case {

    public function test_create_ticket_returns_id() {
        require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
        $service = new METS_Ticket_Service();

        $entity_id = $this->mets_factory->create_entity(['slug' => 'svc-test']);

        $result = $service->create_ticket([
            'entity_id' => $entity_id,
            'subject' => 'Service layer test',
            'description' => 'Testing the service layer',
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_update_ticket_tracks_changes() {
        require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
        $service = new METS_Ticket_Service();

        $ticket_id = $this->mets_factory->create_ticket(['status' => 'new']);
        $result = $service->update_ticket_properties($ticket_id, [
            'status' => 'open',
        ]);

        $this->assertArrayHasKey('changes', $result);
    }
}
```

**Step 2: Run tests to verify they fail**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-service.php -v`

Expected: FAIL (class doesn't exist yet)

**Step 3: Create the service**

Create `includes/services/class-mets-ticket-service.php`:

```php
<?php
/**
 * Ticket business logic service
 *
 * Encapsulates ticket operations: creation, updates, assignment, status transitions.
 * This is the single entry point for all ticket business logic,
 * used by admin handlers, public handlers, and REST API alike.
 *
 * @package    MultiEntityTicketSystem
 * @since      1.1.0
 */

class METS_Ticket_Service {

    /** @var METS_Ticket_Model */
    private $ticket_model;

    public function __construct() {
        require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
        $this->ticket_model = new METS_Ticket_Model();
    }

    /**
     * Create a new ticket.
     *
     * @param array $data Ticket data (entity_id, subject, description, customer_name, customer_email, etc.)
     * @return int|WP_Error Ticket ID on success.
     */
    public function create_ticket( $data ) {
        return $this->ticket_model->create( $data );
    }

    /**
     * Update ticket properties with change tracking.
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $data      Properties to update (status, priority, category, assigned_to).
     * @return array|WP_Error  Array with 'changes' key listing human-readable changes.
     */
    public function update_ticket_properties( $ticket_id, $data ) {
        $current = $this->ticket_model->get( $ticket_id );
        if ( ! $current ) {
            return new WP_Error( 'not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
        }

        // Track changes
        $changes = array();
        $statuses = get_option( 'mets_ticket_statuses', array() );
        $priorities = get_option( 'mets_ticket_priorities', array() );

        if ( isset( $data['status'] ) && $current->status !== $data['status'] ) {
            $old_label = isset( $statuses[ $current->status ]['label'] ) ? $statuses[ $current->status ]['label'] : ucfirst( $current->status );
            $new_label = isset( $statuses[ $data['status'] ]['label'] ) ? $statuses[ $data['status'] ]['label'] : ucfirst( $data['status'] );
            $changes[] = sprintf( __( 'Status changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_label, $new_label );
        }

        if ( isset( $data['priority'] ) && $current->priority !== $data['priority'] ) {
            $old_label = isset( $priorities[ $current->priority ]['label'] ) ? $priorities[ $current->priority ]['label'] : ucfirst( $current->priority );
            $new_label = isset( $priorities[ $data['priority'] ]['label'] ) ? $priorities[ $data['priority'] ]['label'] : ucfirst( $data['priority'] );
            $changes[] = sprintf( __( 'Priority changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_label, $new_label );
        }

        if ( isset( $data['assigned_to'] ) && $current->assigned_to != $data['assigned_to'] ) {
            $old_user_obj = $current->assigned_to ? get_user_by( 'ID', $current->assigned_to ) : false;
            $old_user = $old_user_obj ? $old_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
            $new_user_obj = $data['assigned_to'] ? get_user_by( 'ID', $data['assigned_to'] ) : false;
            $new_user = $new_user_obj ? $new_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
            $changes[] = sprintf( __( 'Assignment changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_user, $new_user );
        }

        $result = $this->ticket_model->update( $ticket_id, $data );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return array( 'changes' => $changes );
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit tests/unit/test-mets-ticket-service.php -v`

Expected: PASS

**Step 5: Commit**

```bash
git add multi-entity-ticket-system/includes/services/class-mets-ticket-service.php tests/unit/test-mets-ticket-service.php
git commit -m "feat: introduce METS_Ticket_Service to centralize ticket business logic"
```

---

### Task 18: Wire Ticket Service into Admin Handlers

**Files:**
- Modify: `multi-entity-ticket-system/admin/class-mets-admin.php` (update_properties handler, ~lines 772-860)

**Step 1: Replace inline business logic with service call**

In the `update_properties` handler (around line 772), replace the inline change tracking and update logic with:

```php
} elseif ( $action === 'update_properties' ) {
    check_admin_referer( 'update_properties', 'properties_nonce' );

    $ticket_id = intval( $_POST['ticket_id'] );

    $data = array(
        'status'      => sanitize_text_field( $_POST['ticket_status'] ),
        'priority'    => sanitize_text_field( $_POST['ticket_priority'] ),
        'category'    => ! empty( $_POST['ticket_category'] ) ? sanitize_text_field( $_POST['ticket_category'] ) : '',
        'assigned_to' => ! empty( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : null,
    );

    // Validate workflow rules for status changes
    $current_ticket = $ticket_model->get( $ticket_id );
    if ( $current_ticket && $current_ticket->status !== $data['status'] ) {
        require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
        $workflow_model = new METS_Workflow_Model();
        $ticket_data = array(
            'priority' => $data['priority'],
            'category' => $data['category']
        );
        $workflow_result = $workflow_model->is_transition_allowed( $current_ticket->status, $data['status'], get_current_user_id(), $ticket_data );

        if ( is_wp_error( $workflow_result ) ) {
            set_transient( 'mets_admin_notice', array(
                'message' => sprintf( __( 'Status change not allowed: %s', METS_TEXT_DOMAIN ), $workflow_result->get_error_message() ),
                'type' => 'error'
            ), 45 );
            wp_redirect( admin_url( 'admin.php?page=mets-tickets&action=edit&ticket_id=' . $ticket_id ) );
            exit;
        }
    }

    require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
    $ticket_service = new METS_Ticket_Service();
    $result = $ticket_service->update_ticket_properties( $ticket_id, $data );

    if ( is_wp_error( $result ) ) {
        set_transient( 'mets_admin_notice', array(
            'message' => $result->get_error_message(),
            'type' => 'error'
        ), 45 );
    } else {
        $changes = $result['changes'];
        // ... rest of success handling (log changes, set success notice)
    }
```

**Step 2: Run tests**

Run: `cd /Users/tomaszlewandowski/githubprojects/multi-entity-ticket-system && php vendor/bin/phpunit -v`

**Step 3: Commit**

```bash
git add multi-entity-ticket-system/admin/class-mets-admin.php
git commit -m "refactor: wire METS_Ticket_Service into admin property update handler"
```

---

## Verification Checklist

After completing all tasks, run these verification steps:

### Security (after Tasks 1-8)
- [ ] `php vendor/bin/phpunit tests/unit/test-mets-ticket-model.php -v` — all pass
- [ ] Search for `get_user_by.*->display_name` — zero results without null check
- [ ] Search for `wp_die.*json_encode` in core — zero results
- [ ] All AJAX handlers have `current_user_can()` — verified via grep

### UX (after Tasks 9-14)
- [ ] All AJAX calls have `timeout: 30000` or use `makeAjaxRequest`
- [ ] All form fields show inline error messages on invalid submit
- [ ] `:focus-visible` outlines visible on Tab navigation
- [ ] Admin tables scroll horizontally on mobile
- [ ] KB search cancels previous requests (check Network tab)

### Architecture (after Tasks 15-18)
- [ ] `wc -l admin/class-mets-admin.php` — significantly reduced from 8,998
- [ ] `class-mets-admin-ajax.php` exists and contains all ajax_* methods
- [ ] `class-mets-ticket-service.php` exists with create/update methods
- [ ] `php vendor/bin/phpunit -v` — all tests pass
