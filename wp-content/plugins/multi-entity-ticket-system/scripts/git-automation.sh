#!/bin/bash

# Git Automation Script for Multi-Entity Ticket System
# This script helps automate the process of committing changes and maintaining the changelog

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

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a git repository. Please run this script from the root of your plugin directory."
    exit 1
fi

# Get the current branch
CURRENT_BRANCH=$(git branch --show-current)
print_info "Current branch: $CURRENT_BRANCH"

# Function to update changelog
update_changelog() {
    local change_type=$1
    local change_description=$2
    local version="1.0.0" # This should be dynamically determined in a more complex implementation
    
    print_info "Updating CHANGELOG.md..."
    
    # Create a temporary file
    TEMP_FILE=$(mktemp)
    
    # Read the first line (header)
    read -r first_line < CHANGELOG.md
    
    # Write header and new entry
    echo "$first_line" > "$TEMP_FILE"
    echo "" >> "$TEMP_FILE"
    
    # Add new entry
    echo "## [${version}] - $(date +%Y-%m-%d)" >> "$TEMP_FILE"
    echo "" >> "$TEMP_FILE"
    echo "### ${change_type}" >> "$TEMP_FILE"
    echo "- ${change_description}" >> "$TEMP_FILE"
    echo "" >> "$TEMP_FILE"
    
    # Add the rest of the file (skip first line)
    tail -n +2 CHANGELOG.md >> "$TEMP_FILE"
    
    # Replace the original file
    mv "$TEMP_FILE" CHANGELOG.md
    
    print_success "CHANGELOG.md updated"
}

# Function to create a new commit
create_commit() {
    local commit_message=$1
    
    print_info "Staging changes..."
    git add .
    
    print_info "Creating commit..."
    git commit -m "$commit_message"
    
    if [ $? -eq 0 ]; then
        print_success "Commit created successfully"
    else
        print_error "Failed to create commit"
        exit 1
    fi
}

# Function to push changes
push_changes() {
    local branch_name=$1
    
    print_info "Pushing changes to origin/$branch_name..."
    git push origin "$branch_name"
    
    if [ $? -eq 0 ]; then
        print_success "Changes pushed successfully"
    else
        print_error "Failed to push changes"
        exit 1
    fi
}

# Main menu
print_info "Multi-Entity Ticket System Git Automation Script"
echo "=============================================="
echo "1. Commit changes with changelog update"
echo "2. Create and push a new feature branch"
echo "3. Merge feature branch to develop"
echo "4. Create release"
echo "5. Quick commit (no changelog update)"
echo "6. Exit"
echo ""

read -p "Select an option (1-6): " choice

case $choice in
    1)
        echo "Commit types:"
        echo "1. feat - New feature"
        echo "2. fix - Bug fix"
        echo "3. docs - Documentation changes"
        echo "4. style - Code style changes"
        echo "5. refactor - Code refactoring"
        echo "6. perf - Performance improvements"
        echo "7. test - Adding or modifying tests"
        echo "8. chore - Maintenance tasks"
        echo ""
        
        read -p "Select commit type (1-8): " type_choice
        read -p "Enter change description: " change_desc
        
        case $type_choice in
            1) commit_type="feat";;
            2) commit_type="fix";;
            3) commit_type="docs";;
            4) commit_type="style";;
            5) commit_type="refactor";;
            6) commit_type="perf";;
            7) commit_type="test";;
            8) commit_type="chore";;
            *) commit_type="chore";;
        esac
        
        # Update changelog
        update_changelog "$commit_type" "$change_desc"
        
        # Create commit
        create_commit "[$commit_type] $change_desc"
        
        # Push changes
        push_changes "$CURRENT_BRANCH"
        ;;
    2)
        read -p "Enter feature branch name (without 'feature/' prefix): " feature_name
        branch_name="feature/$feature_name"
        
        print_info "Creating branch $branch_name..."
        git checkout -b "$branch_name"
        
        if [ $? -eq 0 ]; then
            print_success "Branch $branch_name created"
            push_changes "$branch_name"
        else
            print_error "Failed to create branch"
            exit 1
        fi
        ;;
    3)
        if [[ $CURRENT_BRANCH == feature/* ]]; then
            develop_branch="develop"
            
            print_info "Switching to $develop_branch..."
            git checkout "$develop_branch"
            
            if [ $? -eq 0 ]; then
                print_info "Merging $CURRENT_BRANCH into $develop_branch..."
                git merge --no-ff "$CURRENT_BRANCH"
                
                if [ $? -eq 0 ]; then
                    print_success "Merge completed successfully"
                    push_changes "$develop_branch"
                    
                    read -p "Delete feature branch $CURRENT_BRANCH? (y/n): " delete_branch
                    if [[ $delete_branch == y || $delete_branch == Y ]]; then
                        git branch -d "$CURRENT_BRANCH"
                        git push origin --delete "$CURRENT_BRANCH"
                        print_success "Feature branch deleted"
                    fi
                else
                    print_error "Merge failed"
                    exit 1
                fi
            else
                print_error "Failed to switch to $develop_branch"
                exit 1
            fi
        else
            print_warning "You are not on a feature branch. Current branch: $CURRENT_BRANCH"
        fi
        ;;
    4)
        read -p "Enter release version (e.g., 1.2.0): " version
        release_branch="release/$version"
        
        print_info "Creating release branch $release_branch..."
        git checkout -b "$release_branch" develop
        
        if [ $? -eq 0 ]; then
            print_success "Release branch created"
            
            # Update version in main plugin file
            print_info "Updating version in plugin file..."
            sed -i '' "s/Version:.*$/Version:           $version/" multi-entity-ticket-system.php
            sed -i '' "s/define( 'METS_VERSION'.*$/define( 'METS_VERSION', '$version' );/" multi-entity-ticket-system.php
            
            # Commit version update
            git add multi-entity-ticket-system.php
            git commit -m "[chore] Bump version to $version"
            
            # Push release branch
            push_changes "$release_branch"
            
            print_info "Merge $release_branch to master and develop when ready"
            print_info "Don't forget to create a tag: git tag -a v$version -m 'Version $version'"
        else
            print_error "Failed to create release branch"
            exit 1
        fi
        ;;
    5)
        read -p "Enter commit message: " commit_msg
        create_commit "$commit_msg"
        push_changes "$CURRENT_BRANCH"
        ;;
    6)
        print_info "Exiting..."
        exit 0
        ;;
    *)
        print_error "Invalid option"
        exit 1
        ;;
esac

print_success "Script completed successfully!"