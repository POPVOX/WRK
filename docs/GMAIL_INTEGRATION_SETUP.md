# Gmail Integration Setup (WRK)

This guide covers the first-pass Gmail hookup in WRK: OAuth, metadata sync, and scheduler wiring.

## 1. Google Cloud OAuth Configuration

Use the same OAuth app already used for calendar.

Required scopes:

- `https://www.googleapis.com/auth/calendar.readonly`
- `https://www.googleapis.com/auth/gmail.readonly`

Authorized redirect URI:

- `https://wrk-popvox.on-forge.com/google/callback`

## 2. Forge Environment Variables

Set on the WRK site:

- `GOOGLE_CLIENT_ID=...`
- `GOOGLE_CLIENT_SECRET=...`
- `GOOGLE_REDIRECT_URI=https://wrk-popvox.on-forge.com/google/callback`
- `GOOGLE_WORKSPACE_SCOPES=https://www.googleapis.com/auth/calendar.readonly,https://www.googleapis.com/auth/gmail.readonly`

If users connected Google before Gmail scope was added, they need to disconnect/reconnect to grant Gmail access.

## 3. Run Database Migration

```bash
php artisan migrate --force
```

This adds:

- `users.gmail_import_date`
- `users.gmail_history_id`
- `gmail_messages` table

## 4. First Manual Sync

Sync all connected users inline:

```bash
php artisan gmail:sync --sync --days=60 --max=300
```

Or single user:

```bash
php artisan gmail:sync --user=<USER_ID> --sync --days=60 --max=300
```

## 5. Ongoing Background Sync

Scheduler entries are included in `routes/console.php`:

- `gmail:sync` every 15 minutes (queued)
- `gmail:sync --sync --days=90 --max=500` daily backstop

Ensure Forge scheduler and queue workers are running.

## 6. Workspace Usage

In Workspace:

- Click `Sync Gmail` button, or
- type `/sync gmail` in the conversation bar.

The sync stores Gmail metadata only (subject, participants, snippet, sent time, labels), not full message bodies.
