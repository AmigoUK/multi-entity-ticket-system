#!/bin/bash

# Script to set up GitHub credentials for the Multi-Entity Ticket System repository

echo "GitHub Credentials Setup"
echo "========================"
echo ""
echo "Please follow these steps to set up your GitHub credentials:"
echo ""
echo "1. Go to https://github.com/settings/tokens"
echo "2. Click \"Generate new token\" and then \"Generate new token (classic)\""
echo "3. Give your token a descriptive name like \"Multi-Entity Ticket System Development\""
echo "4. Select the following scopes:"
echo "   - repo - Full control of private repositories"
echo "   - workflow - Update GitHub Action workflows (optional)"
echo "   - delete_repo - Delete repositories (optional)"
echo "5. Click \"Generate token\""
echo "6. Copy the generated token"
echo ""
echo "Once you have your token, please enter it below:"
echo ""

read -p "GitHub Personal Access Token: " token

# Create the credentials file
echo "https://AmigoUK:$token@github.com" > /Users/tomaszlewandowski/.git-credentials

# Set proper permissions
chmod 600 /Users/tomaszlewandowski/.git-credentials

echo ""
echo "Credentials file created successfully!"
echo ""
echo "Now attempting to push to the remote repository..."
echo ""

# Try to push to the remote repository
cd /Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system
git push -u origin master