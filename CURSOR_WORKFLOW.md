# Cursor AI Development Workflow

This document describes how to efficiently work with Cursor AI to fix bugs and implement features using the automated feedback system.

## Quick Start

### Option 1: Export Bugs Directly (Recommended)

Run the export command and paste the output directly into Cursor chat:

```bash
# Export bugs for Cursor (best format for AI)
php artisan feedback:export --type=bug --format=cursor

# Export all unresolved feedback
php artisan feedback:export --status=all --format=cursor --include-resolved

# Export as detailed markdown
php artisan feedback:export --format=markdown --limit=20
```

Then start your Cursor session with:

> "Please fix these bugs: [paste exported content]"

### Option 2: Reference GitHub Issues

If GitHub integration is enabled, bugs are automatically created as issues. Share them with:

> "Fix issues #12, #15, and #18 from our GitHub repo POPVOX/WRK"

### Option 3: Quick One-Liner

For urgent single bugs, use this format:

> "Fix: [Bug description] on page [route/URL]"

Example:
> "Fix: Edit button doesn't work on grants.show page (/funders/6)"

---

## Export Command Reference

```bash
php artisan feedback:export [options]
```

### Options

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--type` | `bug`, `suggestion`, `all` | `all` | Filter by feedback type |
| `--status` | `new`, `reviewed`, `in_progress`, `all` | `new` | Filter by status |
| `--limit` | integer | `10` | Max items to export |
| `--format` | `markdown`, `json`, `cursor` | `markdown` | Output format |
| `--include-resolved` | flag | false | Include addressed/dismissed items |

### Format Examples

#### Cursor Format (Best for AI)
```bash
php artisan feedback:export --format=cursor --type=bug
```
Produces a structured format optimized for pasting into Cursor chat.

#### Markdown Format (Human Readable)
```bash
php artisan feedback:export --format=markdown
```
Good for reviewing feedback or sharing in documentation.

#### JSON Format (Machine Readable)
```bash
php artisan feedback:export --format=json
```
Useful for integrations or programmatic processing.

---

## GitHub Integration

### Setup

1. Create a GitHub Personal Access Token with `repo` scope
2. Add to your `.env`:
   ```
   GITHUB_TOKEN=ghp_your_token_here
   GITHUB_REPO=POPVOX/WRK
   GITHUB_AUTO_CREATE_ISSUES=true
   ```

3. Bug reports will automatically create GitHub issues when AI analysis runs

### Features
- Auto-creates issues for bug reports
- Includes AI analysis and recommendations
- Links back to feedback in admin panel
- Applies labels based on priority

---

## Workflow Automation

### 1. Daily Bug Review
```bash
# Check for new bugs
php artisan feedback:export --type=bug --status=new --format=cursor
```

### 2. Sprint Planning
```bash
# Export all suggestions for review
php artisan feedback:export --type=suggestion --status=all --format=markdown --limit=50
```

### 3. Before Deployment Check
```bash
# Check for critical/high priority bugs
php artisan feedback:export --type=bug --format=cursor | grep -A 5 "🔴\|🟠"
```

---

## Best Practices for Cursor Sessions

### Starting a Session
1. Export relevant feedback first
2. Include the full export in your first message
3. Let Cursor create a TODO list for tracking

### During Development
- Cursor will update TODOs as it fixes issues
- Review changes before committing
- Test fixes locally if possible

### Ending a Session
- Ensure all TODOs are completed or noted
- Commit changes with descriptive messages
- Push to GitHub for deployment

### Sample First Message Template

```
Please fix the following bugs from our beta feedback system:

[paste feedback:export output here]

Please:
1. Create a TODO list of all issues
2. Fix them in priority order (critical/high first)
3. Run Laravel Pint after changes
4. Commit with descriptive messages
```

---

## Admin Panel

Access the feedback management at: `/admin/feedback`

Features:
- View all feedback with filters
- Update status and priority
- Add admin notes
- Export as JSON/CSV
- See AI analysis and recommendations
- View linked GitHub issues

---

## Troubleshooting

### Command Not Found
```bash
composer dump-autoload
```

### No Feedback Showing
- Check database connection
- Ensure feedback widget is enabled
- Verify user is authenticated

### GitHub Issues Not Creating
- Verify `GITHUB_TOKEN` is set correctly
- Check `GITHUB_AUTO_CREATE_ISSUES=true`
- Review Laravel logs for errors

---

## File Locations

| Purpose | Location |
|---------|----------|
| Feedback Model | `app/Models/Feedback.php` |
| Export Command | `app/Console/Commands/ExportFeedbackForCursor.php` |
| AI Analysis Job | `app/Jobs/AnalyzeFeedback.php` |
| Admin Management | `app/Livewire/Admin/FeedbackManagement.php` |
| Feedback Widget | `app/Livewire/FeedbackWidget.php` |
| This Document | `CURSOR_WORKFLOW.md` |


