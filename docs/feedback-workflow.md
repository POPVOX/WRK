# Feedback-to-Fix Workflow Automation

This document describes how to automate the lifecycle of user feedback from submission to resolution.

## Workflow Overview

```
User Submits     AI Analyzes      Developer        Commit with      Deploy &
  Feedback    →   & Triages    →   Fixes Bug    →   Reference    →  Auto-Resolve
     │               │                 │               │                │
     ▼               ▼                 ▼               ▼                ▼
  Feedback      Priority +        Code Changes    "fixes #42"      Status →
  Created       AI Summary                                         Addressed
```

## Commit Message Standards

When fixing a bug or implementing a feature request, reference the feedback ID in your commit message:

### Supported Patterns

```bash
# Basic patterns (recommended)
git commit -m "Fix calendar sync issue, fixes #42"
git commit -m "Add export button, closes #15"
git commit -m "Resolve milestone date bug, resolves #37"

# Explicit feedback references
git commit -m "Fix project lead display, closes feedback:39"
git commit -m "Add task editing [FB-38]"

# Multiple issues in one commit
git commit -m "Dashboard improvements - fixes #38, fixes #40, closes #41"
```

## Commands

### Mark Feedback as Resolved (Manual)
```bash
php artisan feedback:mark-addressed 42 43 44 --notes="Fixed in v1.2.0" --type=fix
```

### Process Commits for Auto-Resolution
```bash
# Check last 10 commits
php artisan feedback:process-deploy --count=10

# Check commits since last deploy
php artisan feedback:process-deploy --since=abc123
```

### Full Deploy Hook
```bash
# Run all deployment tasks including auto-resolution
php artisan deploy:hook --previous-sha=abc123
```

## Forge Deploy Script

Add this to your Laravel Forge deployment script:

```bash
cd /home/forge/your-site.com

# Store current SHA before pull
PREVIOUS_SHA=$(git rev-parse HEAD)

# Pull latest code
git pull origin $FORGE_SITE_BRANCH

# Install dependencies
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run deploy hook with previous SHA
$FORGE_PHP artisan deploy:hook --previous-sha=$PREVIOUS_SHA

# Reload PHP-FPM
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

## Resolution Types

When marking feedback as resolved, use one of these types:

| Type | Description |
|------|-------------|
| `fix` | Bug was fixed |
| `enhancement` | Feature was added/improved |
| `wontfix` | Decided not to implement |
| `duplicate` | Duplicate of another issue |
| `workaround` | Provided a workaround |

## Future Enhancements

### GitHub Issue Integration
Automatically create GitHub issues from feedback and link them:
```bash
# Create GitHub issue from feedback
php artisan feedback:create-issue 42

# This adds github_issue_url and github_issue_number to the feedback record
```

### Slack/Teams Notifications
Get notified when:
- New high-priority feedback is submitted
- Feedback is resolved
- Deploy completes with auto-resolutions

### AI-Powered Code Suggestions
The existing `AiCodeFixService` can analyze feedback and suggest code changes:
```bash
# Generate AI fix proposal
php artisan feedback:generate-fix 42
```

## Best Practices

1. **Reference feedback IDs in commits** - Makes tracking easy
2. **Use meaningful resolution notes** - Helps future debugging
3. **Set priority on feedback** - Critical items surface first
4. **Run deploy hook on every deploy** - Keeps feedback in sync
5. **Review "New" feedback weekly** - Don't let items pile up
