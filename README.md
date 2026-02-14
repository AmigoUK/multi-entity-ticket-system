# Multi-Entity Ticket System (METS)

A WordPress plugin for centralized customer service ticket management across multiple cooperative businesses with hierarchical entity structure.

## Features

- **Multi-Entity Support**: Manage tickets across multiple businesses/departments
- **Hierarchical Structure**: Support for complex organizational hierarchies
- **WooCommerce Integration**: Seamless integration with WooCommerce orders
- **Knowledge Base**: Built-in knowledge base system for self-service
- **SLA Monitoring**: Service level agreement tracking and notifications
- **Agent Management**: Role-based access control and agent assignment
- **SMTP Integration**: Reliable email notifications with multiple provider support
- **REST API**: Complete API for third-party integrations
- **Shortcodes**: Public ticket forms, dashboards, and knowledge base views

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- WooCommerce 3.0+ (optional, for e-commerce integration)

## Installation

1. Download the plugin from this repository
2. Upload to your WordPress `wp-content/plugins` directory
3. Activate the plugin through the WordPress admin panel
4. Configure your entities and departments
5. Set up SMTP for email notifications (see [SMTP Setup Guide](multi-entity-ticket-system/docs/SMTP_SETUP_GUIDE.md))
6. Configure SLA rules and business hours

## What's New in v1.1.0

### Security Hardening
- Database-level race condition prevention using `GET_LOCK`/`RELEASE_LOCK`
- Null pointer safety on `get_user_by()` calls
- Capability checks added to all AJAX handlers
- Standardized JSON responses via `wp_send_json_success`/`wp_send_json_error`
- SQL field whitelisting in Knowledge Base model
- User validation on ticket assignment (returns `WP_Error` on failure)
- Strict type comparisons (`===`/`!==`) throughout
- Debug `error_log` calls removed from production code

### UX Improvements
- AJAX wrapper with loading indicators, timeouts, and user-friendly error messages
- Field-level form validation with ARIA attributes for accessibility
- Focus-visible keyboard navigation styles
- Mobile-responsive CSS for admin interfaces
- Event handler namespacing (`.mets`) to prevent conflicts
- Knowledge base search race condition fix (request abort on new search)

### Architecture Refactoring
- Extracted AJAX handlers to `class-mets-admin-ajax.php` (29 methods)
- Extracted settings management to `class-mets-admin-settings.php` (11 methods)
- New `class-mets-ticket-service.php` service layer with unit tests
- Admin class reduced from ~9,000 to ~6,000 lines (33% reduction)

## Documentation

- [API Documentation](multi-entity-ticket-system/docs/api-documentation.md)
- [Shortcode Reference](multi-entity-ticket-system/docs/shortcodes.md)
- [SMTP Setup Guide](multi-entity-ticket-system/docs/SMTP_SETUP_GUIDE.md)
- [SMTP Quick Checklist](multi-entity-ticket-system/docs/SMTP_QUICK_CHECKLIST.md)

## Testing

Unit tests are located in the `tests/` directory and use PHPUnit with the WordPress test suite:

```bash
cd tests
composer install
./vendor/bin/phpunit
```

## License

This project is licensed under the GPL v2 or later.

## Author

**Tomasz 'Amigo' Lewandowski**
- Website: [https://attv.uk](https://attv.uk)
- Email: lewandowski.tl@gmail.com

## Project Status

**Version**: 1.1.0
**Last Updated**: February 2026
