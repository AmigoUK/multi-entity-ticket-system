# Multi-Entity Ticket System Shortcodes

## Ticket Submission Form

The `[ticket_form]` shortcode displays a public ticket submission form.

### Basic Usage

```
[ticket_form]
```

### Parameters

- **entity** (optional) - Pre-select a specific entity by slug
  - Example: `[ticket_form entity="support-team"]`
  
- **require_login** (optional) - Require users to be logged in
  - Values: "yes" or "no" (default: "no")
  - Example: `[ticket_form require_login="yes"]`
  
- **categories** (optional) - Limit categories shown in dropdown
  - Comma-separated list of category keys
  - Example: `[ticket_form categories="technical,billing"]`
  
- **success_message** (optional) - Custom success message after submission
  - Example: `[ticket_form success_message="Thank you! We'll respond within 24 hours."]`

### Examples

#### Basic form with all entities
```
[ticket_form]
```

#### Pre-selected entity for a department page
```
[ticket_form entity="it-support"]
```

#### Members-only support form
```
[ticket_form require_login="yes" categories="premium,vip"]
```

#### Combined parameters
```
[ticket_form entity="sales" categories="product,pricing" success_message="A sales representative will contact you soon."]
```

### Form Features

- AJAX submission without page reload
- Client-side validation
- Responsive design
- Auto-fills user information if logged in
- Success/error message display
- Customizable via CSS classes

### CSS Classes for Styling

- `.mets-ticket-form-wrapper` - Main form container
- `.mets-ticket-form` - Form element
- `.mets-form-group` - Field wrapper
- `.mets-form-row` - Two-column field row
- `.mets-submit-button` - Submit button
- `.mets-success-message` - Success message container
- `.mets-error-message` - Error message container
- `.mets-field-error` - Applied to invalid fields

## Customer Portal (Coming Soon)

The `[ticket_portal]` shortcode will display a customer portal for viewing and managing tickets.

```
[ticket_portal]
```

This feature is currently under development in Phase 3.