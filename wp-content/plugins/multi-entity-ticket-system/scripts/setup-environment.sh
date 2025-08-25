#!/bin/bash

# Multi-Entity Ticket System - Development Environment Setup
# This script sets up the development environment for the plugin

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the correct directory
if [[ ! -f "multi-entity-ticket-system.php" ]]; then
    print_error "This script must be run from the plugin root directory"
    exit 1
fi

print_info "Setting up development environment for Multi-Entity Ticket System..."

# Check Git installation
print_info "Checking Git installation..."
if ! command -v git &> /dev/null; then
    print_error "Git is not installed. Please install Git and try again."
    exit 1
else
    GIT_VERSION=$(git --version)
    print_success "Git is installed: $GIT_VERSION"
fi

# Check if this is a Git repository
print_info "Checking Git repository status..."
if git rev-parse --git-dir > /dev/null 2>&1; then
    print_success "Git repository already initialized"
else
    print_info "Initializing Git repository..."
    git init
    if [ $? -eq 0 ]; then
        print_success "Git repository initialized"
    else
        print_error "Failed to initialize Git repository"
        exit 1
    fi
fi

# Check Git configuration
print_info "Checking Git configuration..."
GIT_USER_NAME=$(git config user.name)
GIT_USER_EMAIL=$(git config user.email)

if [[ -z "$GIT_USER_NAME" || -z "$GIT_USER_EMAIL" ]]; then
    print_warning "Git user configuration not set. Please configure your Git user information:"
    echo "git config --global user.name \"Your Name\""
    echo "git config --global user.email \"your.email@example.com\""
else
    print_success "Git user: $GIT_USER_NAME <$GIT_USER_EMAIL>"
fi

# Create develop branch if it doesn't exist
print_info "Setting up Git Flow branches..."
if git show-ref --verify --quiet "refs/heads/develop"; then
    print_success "Develop branch already exists"
else
    print_info "Creating develop branch..."
    git checkout -b develop
    if [ $? -eq 0 ]; then
        print_success "Develop branch created"
    else
        print_error "Failed to create develop branch"
    fi
fi

# Switch back to master if we were on a different branch
git checkout master > /dev/null 2>&1

# Check for required files
print_info "Checking for required files..."
REQUIRED_FILES=(
    "multi-entity-ticket-system.php"
    "README.md"
    "DevelopmentPlan.md"
    "Troubleshooting.md"
    "CHANGELOG.md"
    "next_steps.md"
    ".gitignore"
)

MISSING_FILES=()
for file in "${REQUIRED_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        print_success "Found $file"
    else
        print_error "Missing $file"
        MISSING_FILES+=("$file")
    fi
done

if [ ${#MISSING_FILES[@]} -ne 0 ]; then
    print_warning "Some required files are missing. Please create them before proceeding."
fi

# Check for scripts directory and files
print_info "Checking for automation scripts..."
if [[ -d "scripts" && -f "scripts/git-automation.sh" && -f "scripts/manual-testing.sh" ]]; then
    print_success "Automation scripts found"
else
    print_error "Automation scripts missing. Please create the scripts directory and required scripts."
fi

# Check file permissions
print_info "Checking script permissions..."
if [[ -x "scripts/git-automation.sh" && -x "scripts/manual-testing.sh" ]]; then
    print_success "Script permissions are correct"
else
    print_warning "Scripts may not be executable. Setting executable permissions..."
    chmod +x scripts/*.sh 2>/dev/null
    if [ $? -eq 0 ]; then
        print_success "Executable permissions set"
    else
        print_error "Failed to set executable permissions"
    fi
fi

# Check for includes directory
print_info "Checking plugin structure..."
if [[ -d "includes" && -d "admin" && -d "public" ]]; then
    print_success "Plugin directory structure is correct"
else
    print_warning "Plugin directory structure may be incomplete"
fi

# Initialize submodules if any
print_info "Checking for submodules..."
if [[ -f ".gitmodules" ]]; then
    print_info "Initializing submodules..."
    git submodule init
    git submodule update
    if [ $? -eq 0 ]; then
        print_success "Submodules initialized"
    else
        print_error "Failed to initialize submodules"
    fi
else
    print_info "No submodules found"
fi

# Show repository status
print_info "Repository status:"
git status --short

# Show recent commits
print_info "Recent commits:"
git log --oneline -5

print_success "Development environment setup check completed!"

echo ""
echo "Next steps:"
echo "1. Review the README.md file for development guidelines"
echo "2. Use the git-automation.sh script for common Git operations"
echo "3. Run manual-testing.sh to perform manual testing procedures"
echo "4. Check DevelopmentPlan.md for project documentation"
echo "5. Review next_steps.md for planned features"