# Git Repository Setup with Personal Access Token

This document explains how to set up a remote Git repository for the Multi-Entity Ticket System plugin using personal access tokens for authentication.

## Creating a GitHub Repository

1. Go to https://github.com and log in to your account
2. Click the "+" icon in the upper right corner and select "New repository"
3. Enter a repository name (e.g., "multi-entity-ticket-system")
4. Optionally add a description
5. Choose if the repository should be Public or Private
6. DO NOT initialize the repository with a README, .gitignore, or license
7. Click "Create repository"

## Adding the Remote Repository

After creating the repository on GitHub, you'll need to add it as a remote to your local repository:

```bash
cd /path/to/multi-entity-ticket-system
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git
```

## Creating a Personal Access Token (Classic)

1. Go to GitHub Settings (click your profile picture and select "Settings")
2. In the left sidebar, click "Developer settings"
3. In the left sidebar, click "Personal access tokens"
4. Click "Tokens (classic)"
5. Click "Generate new token" and then "Generate new token (classic)"
6. Give your token a descriptive name (e.g., "Multi-Entity Ticket System Development")
7. Select the appropriate scopes:
   - `repo` - Full control of private repositories
   - `workflow` - Update GitHub Action workflows (optional)
   - `delete_repo` - Delete repositories (optional, for safety)
8. Click "Generate token"
9. Copy the generated token immediately (you won't be able to see it again)

## Using the Personal Access Token

You can use the personal access token in several ways:

### Method 1: Credential Helper (Recommended)
Configure Git to cache your credentials:

```bash
git config --global credential.helper store
```

Then, when you next push, enter your GitHub username and the personal access token as the password.

### Method 2: URL with Token (Less Secure)
You can embed the token directly in the remote URL:

```bash
git remote set-url origin https://YOUR_TOKEN@github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git
```

Note: This method stores the token in your Git configuration, which is less secure.

### Method 3: Interactive Authentication
When pushing, Git will prompt for username and password:
- Username: Your GitHub username
- Password: Your personal access token

## First Push to Remote Repository

After setting up the remote and authentication:

1. Ensure you're on the master branch:
   ```bash
   git checkout master
   ```

2. Push the master branch:
   ```bash
   git push -u origin master
   ```

3. Push the develop branch:
   ```bash
   git checkout develop
   git push -u origin develop
   ```

4. Push all branches and tags:
   ```bash
   git push --all origin
   git push --tags origin
   ```

## Best Practices for Personal Access Tokens

1. **Token Security**:
   - Treat personal access tokens like passwords
   - Don't share tokens with others
   - Don't commit tokens to repositories
   - Regenerate tokens periodically

2. **Token Scopes**:
   - Grant minimal required permissions
   - Use specific tokens for specific purposes
   - Create separate tokens for different applications

3. **Token Management**:
   - Keep a list of tokens and their purposes
   - Delete unused tokens
   - Monitor token usage

## Troubleshooting Authentication Issues

1. **Invalid username/password**:
   - Ensure you're using the personal access token as the password, not your GitHub account password

2. **Permission denied**:
   - Check that your token has the required scopes
   - Verify you have write access to the repository

3. **Token expired**:
   - Personal access tokens don't expire by default, but you can set an expiration
   - Generate a new token if needed

4. **Clear cached credentials**:
   ```bash
   git config --global --unset credential.helper
   git config --global credential.helper store
   ```

## Alternative Git Hosting Services

While this guide focuses on GitHub, similar processes apply to other Git hosting services:

- **GitLab**: Similar personal access token creation process
- **Bitbucket**: Uses app passwords instead of personal access tokens
- **Self-hosted Git**: Depends on the specific implementation

For any of these services, the general approach remains:
1. Create a personal access token or equivalent
2. Add the remote repository
3. Configure authentication
4. Push your code