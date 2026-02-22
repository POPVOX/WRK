# Gmail Integration Setup (WRK)

This guide covers Gmail hookup in WRK: OAuth, metadata sync, and compose/send setup.

## 1. Google Cloud OAuth Configuration

Use the same OAuth app already used for calendar.

Required scopes:

- `https://www.googleapis.com/auth/calendar.readonly`
- `https://www.googleapis.com/auth/gmail.readonly`
- `https://www.googleapis.com/auth/gmail.send`
- `https://www.googleapis.com/auth/gmail.compose`

Authorized redirect URI:

- `https://wrk-popvox.on-forge.com/google/callback`

## 2. Forge Environment Variables

Set on the WRK site:

- `GOOGLE_CLIENT_ID=...`
- `GOOGLE_CLIENT_SECRET=...`
- `GOOGLE_REDIRECT_URI=https://wrk-popvox.on-forge.com/google/callback`
- `GOOGLE_WORKSPACE_SCOPES=https://www.googleapis.com/auth/calendar.readonly,https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.compose`

If users connected Google before send/compose scopes were added, they need to disconnect/reconnect to grant updated Gmail permissions.

## 3. Run Database Migration

```bash
php artisan migrate --force
```

This adds/uses:

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

## 7. Compose and Send

Inbox now supports:

- Compose new email (To/Cc/Bcc/Subject/Body)
- Save Gmail draft
- Send Gmail message
- Send thread reply directly from WRK

If send/draft actions fail with an "insufficient scope" error, disconnect and reconnect Google so the user grants the new Gmail scopes.
