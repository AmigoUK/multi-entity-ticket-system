# Multi-Entity Ticket System

A comprehensive WordPress plugin for centralized customer service ticket management across multiple cooperative businesses with hierarchical entity structure.

## Description

The Multi-Entity Ticket System is a powerful WordPress plugin designed to streamline customer service operations for organizations with complex structures. It provides a centralized platform for managing support tickets across multiple entities, with advanced features like role-based access control, knowledge base management, SLA monitoring, and real-time notifications.

## Key Features

- **Multi-Entity Support**: Manage tickets across multiple cooperative businesses with hierarchical structure
- **Advanced Ticket Management**: Customizable workflows, statuses, and ticket routing
- **Role-Based Access Control**: Granular permissions with entity-specific access
- **Knowledge Base**: Comprehensive article management with search functionality
- **SLA Monitoring**: Service Level Agreement tracking and breach notifications
- **Email Integration**: SMTP support with customizable email templates
- **Real-time Notifications**: AJAX polling for instant updates
- **Performance Optimization**: Caching and database optimization
- **Security Features**: Rate limiting, audit trails, and input validation
- **REST API**: External integration capabilities
- **WooCommerce Integration**: E-commerce support for order-related tickets
- **AI Chat Widget**: Intelligent customer support assistant

## Installation

1. Upload the `multi-entity-ticket-system` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the plugin settings to configure entities, roles, and other options

## Development Workflow

This project follows Git Flow methodology with automated tooling for changelog maintenance and version control.

### Prerequisites

- Git CLI installed and configured
- Bash shell environment (macOS/Linux)

### Git Repository Setup

For information on setting up a remote repository with personal access token authentication, see [GIT_SETUP.md](GIT_SETUP.md).

### Automated Git Operations

Use the provided automation script for common Git operations:

```bash
cd /path/to/plugin/directory
./scripts/git-automation.sh
```

### Branching Strategy

- `master` - Production-ready code
- `develop` - Integration branch for features
- `feature/feature-name` - Individual feature development
- `release/version` - Preparation for releases
- `hotfix/issue` - Urgent bug fixes

### Commit Message Guidelines

Follow conventional commits format:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes
- `refactor:` Code refactoring
- `perf:` Performance improvements
- `test:` Adding or modifying tests
- `chore:` Maintenance tasks

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## Documentation

- [Development Plan](DevelopmentPlan.md) - Comprehensive project documentation
- [Troubleshooting Guide](Troubleshooting.md) - Common issues and solutions
- [Next Steps](next_steps.md) - Planned features and implementations

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a pull request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Author

Tomasz 'Amigo' Lewandowski - [attv.uk](https://attv.uk)

## Support

For support, please open an issue on the GitHub repository or contact the author directly.