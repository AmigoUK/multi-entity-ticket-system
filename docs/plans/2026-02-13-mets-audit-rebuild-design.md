# METS Plugin: Comprehensive Audit & Incremental Rebuild Plan

## Context

The METS WordPress plugin (~50,000 LOC) is production-ready but has accumulated technical debt across security, UX, and architecture. A deep audit uncovered **7 critical/high code bugs**, **10+ UX issues** (near-zero accessibility, no loading states, no mobile responsiveness), and **8 architectural problems** (8,998-line God class, no service layer, no DI).

**Goal:** Incrementally fix all issues while keeping the plugin functional at every step. Security & bugs first, then UX, then architecture.

---

## Phase 1: Security & Critical Bug Fixes

### 1.1 Fix Race Condition in Ticket Number Generation (CRITICAL)
**File:** `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php` (lines 68-106)
**Problem:** SELECT MAX() + INSERT without locking = TOCTOU race condition. `usleep()` retry is not reliable.
**Fix:** Use database-level `GET_LOCK()` / `RELEASE_LOCK()` around the generate+insert sequence. The existing UNIQUE constraint on `ticket_number` provides a safety net.

### 1.2 Fix Null Pointer Crash on get_user_by() (HIGH)
**File:** `multi-entity-ticket-system/admin/class-mets-admin.php` (lines 846-847)
**Problem:** `get_user_by('ID', ...)` returns `false` for deleted users, but code calls `->display_name` without checking.
**Fix:** Add null-safe access before property dereference.

### 1.3 Add Missing Capability Checks to AJAX Handlers (HIGH)
**File:** `multi-entity-ticket-system/admin/class-mets-admin.php` (line 2167)
**Problem:** `ajax_search_entities()` checks nonce but not user capabilities.
**Fix:** Add capability checks to all AJAX handlers missing them.

### 1.4 Fix Improper JSON Responses in Core (MEDIUM)
**File:** `multi-entity-ticket-system/includes/class-mets-core.php` (lines 841-951)
**Problem:** Uses `wp_die(json_encode(...))` instead of `wp_send_json_success()` / `wp_send_json_error()`.
**Fix:** Replace all instances with WordPress JSON response functions.

### 1.5 Harden SQL Pattern in KB Article Model (MEDIUM)
**File:** `multi-entity-ticket-system/includes/models/class-mets-kb-article-model.php` (lines 425-438)
**Problem:** Dynamic field name interpolated into SQL without whitelist.
**Fix:** Add explicit whitelist validation for allowed field names.

### 1.6 Add User Validation on Ticket Assignment (HIGH)
**File:** `multi-entity-ticket-system/includes/models/class-mets-ticket-model.php` (lines 306-309)
**Problem:** Assigned user ID accepted without checking if user exists.
**Fix:** Validate user existence before accepting assignment.

### 1.7 Fix Loose Type Comparisons (LOW)
**Files:** Multiple models
**Fix:** Replace `==` with `===` for integer/boolean comparisons.

### 1.8 Remove Debug error_log() Calls (LOW)
**File:** `admin/class-mets-admin.php` (multiple locations)
**Fix:** Remove or gate behind `WP_DEBUG`.

---

## Phase 2: UX Improvements

### 2.1 Add Loading States to All AJAX Operations
### 2.2 Improve Form Validation & Error Feedback
### 2.3 Add Accessibility (ARIA Labels, Keyboard Navigation, Focus Styles)
### 2.4 Add Mobile Responsiveness
### 2.5 Replace confirm() Dialogs with Proper Modals
### 2.6 Add User-Facing AJAX Error Messages
### 2.7 Fix Event Handler Duplication
### 2.8 Fix KB Search Race Condition
### 2.9 Improve KB Gate Flow
### 2.10 Add AJAX Request Timeouts

---

## Phase 3: Architecture Refactoring (Incremental, Strangler Fig Pattern)

### 3.1 Extract AJAX Handlers from Admin God Class
### 3.2 Extract Settings Management
### 3.3 Extract Dashboard & Widgets
### 3.4 Introduce Service Layer
### 3.5 Introduce Repository Pattern
### 3.6 Add Dependency Injection Container
### 3.7 Centralize Cache Invalidation
### 3.8 Standardize Error Handling

---

## Phase 4: Testing (Ongoing)

### 4.1 Add Tests for Each Security Fix
### 4.2 Add Unit Tests for New Services
### 4.3 Add Integration Tests for UX Flows
