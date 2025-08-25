# Multi-Entity Ticket System - Developer's Quick Start Guide

## Welcome!

This guide provides a quick overview of the development environment and tools available for the Multi-Entity Ticket System plugin.

## Repository Structure

```
multi-entity-ticket-system/
├── admin/                 # Admin area functionality
├── assets/                # CSS, JavaScript, and image assets
├── database/              # Database schema and management
├── includes/              # Core plugin classes and functionality
├── public/                # Public-facing functionality
├── scripts/               # Automation scripts
├── tools/                 # Development and debugging tools
├── *.md                   # Documentation files
└── multi-entity-ticket-system.php  # Main plugin file
```

## Essential Documentation

1. **DevelopmentPlan.md** - Complete project documentation
2. **README.md** - Quick start and usage instructions
3. **next_steps.md** - Planned features and implementations
4. **MANUAL_TESTING_GUIDE.md** - Detailed testing procedures
5. **Troubleshooting.md** - Common issues and solutions
6. **GIT_SETUP.md** - Remote repository setup instructions

## Automation Scripts

All scripts are in the `scripts/` directory and are ready to use:

### 1. Git Automation (`git-automation.sh`)
Streamlines Git operations:
```bash
./scripts/git-automation.sh
```
Features:
- Commit changes with changelog updates
- Create and manage feature branches
- Handle releases and versioning
- Push changes to remote repository

### 2. Environment Setup (`setup-environment.sh`)
Verifies your development environment:
```bash
./scripts/setup-environment.sh
```
Checks:
- Git installation and configuration
- Required files and permissions
- Branch structure
- Script permissions

### 3. Feature Branch Creation (`create-feature-branch.sh`)
Creates new feature branches based on next_steps.md:
```bash
./scripts/create-feature-branch.sh
```
Automatically:
- Lists available features from next_steps.md
- Creates properly named feature branches
- Pushes new branches to remote repository

### 4. Manual Testing (`manual-testing.sh`)
Guided manual testing procedures:
```bash
./scripts/manual-testing.sh
```
Provides:
- Step-by-step testing instructions
- Results logging
- Comprehensive test coverage

## Development Workflow

### 1. Planning
- Review `next_steps.md` for planned features
- Add new features to the list before implementation

### 2. Branching
```bash
# Create a new feature branch
./scripts/create-feature-branch.sh
```

### 3. Implementation
- Follow WordPress coding standards
- Update documentation as you work
- Write clean, error-free code

### 4. Testing
```bash
# Perform manual testing
./scripts/manual-testing.sh
```

### 5. Committing
```bash
# Commit with automated changelog update
./scripts/git-automation.sh
```

### 6. Pushing
```bash
# Push to remote repository
git push origin feature/your-feature-name
```

## Git Workflow

This project follows Git Flow:

- **master** - Production-ready code
- **develop** - Integration branch for features
- **feature/feature-name** - Individual feature development
- **release/version** - Preparation for releases
- **hotfix/issue** - Urgent bug fixes

## Commit Message Guidelines

Follow conventional commits format:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes
- `refactor:` Code refactoring
- `perf:` Performance improvements
- `test:` Adding or modifying tests
- `chore:` Maintenance tasks

## Remote Repository Setup

To set up the remote repository:

1. Create a new repository on GitHub
2. Add the remote:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/multi-entity-ticket-system.git
   ```
3. Set up a personal access token (see GIT_SETUP.md)
4. Push all branches:
   ```bash
   git push -u origin master
   git push -u origin develop
   git push --all origin
   ```

## Getting Help

- For Git issues: Check GIT_SETUP.md
- For testing procedures: Review MANUAL_TESTING_GUIDE.md
- For project documentation: Read DevelopmentPlan.md
- For troubleshooting: Consult Troubleshooting.md
- For usage instructions: See README.md

## Contact

For questions or issues, contact the lead developer:
Tomasz Lewandowski <design@attv.uk>

Happy coding!