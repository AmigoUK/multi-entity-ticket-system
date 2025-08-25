#!/bin/bash

# Feature Branch Creation Script
# This script helps create new feature branches based on the next_steps.md file

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

# Check if next_steps.md exists
if [[ ! -f "next_steps.md" ]]; then
    print_error "next_steps.md not found. Please run this script from the plugin root directory."
    exit 1
fi

print_info "Available features from next_steps.md:"
echo "======================================"

# Extract feature titles from next_steps.md
FEATURES=()
while IFS= read -r line; do
    if [[ $line == "### "* ]]; then
        feature=$(echo "$line" | sed 's/### //')
        FEATURES+=("$feature")
        echo "${#FEATURES[@]}. $feature"
    fi
done < <(grep "^### " next_steps.md)

echo ""
read -p "Select a feature to create a branch for (1-${#FEATURES[@]}): " feature_choice

# Validate input
if ! [[ "$feature_choice" =~ ^[0-9]+$ ]] || [ "$feature_choice" -lt 1 ] || [ "$feature_choice" -gt "${#FEATURES[@]}" ]; then
    print_error "Invalid selection. Please choose a number between 1 and ${#FEATURES[@]}."
    exit 1
fi

# Get selected feature
SELECTED_FEATURE="${FEATURES[$((feature_choice-1))]}"
print_info "Selected feature: $SELECTED_FEATURE"

# Create a branch name from the feature title
BRANCH_NAME=$(echo "$SELECTED_FEATURE" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-zA-Z0-9]/-/g' | sed 's/^-//' | sed 's/-$//' | cut -c1-50)

# Prefix with feature/
BRANCH_NAME="feature/$BRANCH_NAME"

print_info "Creating branch: $BRANCH_NAME"

# Check if branch already exists
if git show-ref --verify --quiet "refs/heads/$BRANCH_NAME"; then
    print_warning "Branch $BRANCH_NAME already exists."
    read -p "Do you want to switch to this branch? (y/n): " switch_choice
    if [[ $switch_choice == y || $switch_choice == Y ]]; then
        git checkout "$BRANCH_NAME"
        if [ $? -eq 0 ]; then
            print_success "Switched to branch $BRANCH_NAME"
        else
            print_error "Failed to switch to branch $BRANCH_NAME"
            exit 1
        fi
    fi
else
    # Create and switch to the new branch
    git checkout -b "$BRANCH_NAME"
    if [ $? -eq 0 ]; then
        print_success "Created and switched to branch $BRANCH_NAME"
        
        # Optionally push the new branch
        read -p "Do you want to push this branch to origin? (y/n): " push_choice
        if [[ $push_choice == y || $push_choice == Y ]]; then
            git push -u origin "$BRANCH_NAME"
            if [ $? -eq 0 ]; then
                print_success "Branch pushed to origin"
            else
                print_error "Failed to push branch to origin"
            fi
        fi
    else
        print_error "Failed to create branch $BRANCH_NAME"
        exit 1
    fi
fi

print_success "Feature branch setup completed!"
echo "Remember to update next_steps.md with your implementation plan for this feature."