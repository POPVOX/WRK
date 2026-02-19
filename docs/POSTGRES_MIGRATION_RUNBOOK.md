# Postgres Migration Runbook (WRK v2 Path)

## Objective
Move WRK from SQLite to Postgres without breaking current workflows, while keeping SQLite usable for local development/tests.

## Outcome
- staging/production run on Postgres
- SQLite remains valid for quick local testing
- knowledge base indexing/search works on both drivers

## Phase 0: Preflight
1. Freeze schema-changing feature work during migration window.
2. Ensure backups exist:
   - SQLite file snapshot (`database/database.sqlite`)
   - storage directories for uploaded files
3. Confirm team can provision a Postgres instance for staging.

## Phase 1: Environment Setup
Set environment values in `.env` (staging/prod):

```dotenv
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=wrk
DB_USERNAME=...
DB_PASSWORD=...
```

If you need to run SQLite->Postgres migration from one app instance, set split DB names:

```dotenv
DB_SQLITE_DATABASE=/absolute/path/to/database/database.sqlite
DB_PGSQL_DATABASE=wrk
```

Optional SSL settings (if required by provider):

```dotenv
DB_URL=pgsql://user:pass@host:5432/wrk?sslmode=require
```

## Phase 2: Schema Compatibility
KB index migration is now driver-aware in:
- `database/migrations/2025_12_28_050000_create_kb_index_fts5.php`

Behavior:
- `sqlite`: creates FTS5 virtual table
- `pgsql`: creates `kb_index` table + GIN full-text index
- other drivers: creates plain fallback table

## Phase 3: Data Migration
Recommended pattern:
1. Provision clean Postgres DB.
2. Run migrations:
   - `php artisan migrate --force`
3. Load seed data (if needed):
   - `php artisan db:seed --force`
4. Migrate production data from SQLite with built-in command:

```bash
# optional: inspect candidate sqlite file first
php artisan db:inspect-sqlite /absolute/path/to/old.sqlite

# dry run first
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --dry-run

# dry run with explicit sqlite file path
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --source-file=/absolute/path/to/old.sqlite --dry-run

# actual copy (safe default: no truncate)
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --chunk=1000

# actual copy with explicit sqlite file path
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --source-file=/absolute/path/to/old.sqlite --chunk=1000

# if rerunning from scratch on target
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --truncate --chunk=1000

# rerun only specific tables (for partial retry/fixup)
php artisan db:migrate-sqlite-to-pgsql --source=sqlite --target=pgsql --source-file=/absolute/path/to/old.sqlite --tables=press_clips,press_clip_person --truncate --chunk=1000
```

Command notes:
- preserves IDs by copying explicit row values
- uses FK-aware table ordering for this schema
- copies only shared columns between source and target tables
- sanitizes invalid UTF-8 before insert and trims strings to target varchar limits

Note:
- Keep this first cut as a one-time migration.
- After cutover, block writes to SQLite to avoid dual-write drift.

## Phase 4: KB Reindex
After data is in Postgres, reindex documents:
1. Identify all knowledge-base docs (`project_documents.is_knowledge_base = true`).
2. Dispatch `IndexDocumentContent` for all docs.
3. Verify index counts in `kb_index`.

## Phase 5: Validation Checklist
- `php artisan migrate:status` shows all migrations as ran.
- app boot and login succeed.
- key modules load: dashboard, contacts, meetings, projects, travel, funders.
- knowledge search returns results.
- AI chat fallback includes KB matches.
- queue jobs succeed (especially KB indexing jobs).

## Phase 6: Cutover
1. Maintenance window.
2. Final SQLite backup.
3. Deploy code + set `DB_CONNECTION=pgsql`.
4. Run migrations.
5. Run KB reindex.
6. Smoke test critical user journeys.

### Forge SSH Cutover Commands (copy/paste)

Run over SSH on the server (not in Forge Commands UI):

```bash
cd /home/forge/wrk-popvox.on-forge.com/current
php -m | grep -i pgsql
ls -l .env
cp .env ".env.backup-$(date +%Y%m%d-%H%M%S)"
```

Edit `.env` and set:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=<your-rds-endpoint>
DB_PORT=5432
DB_DATABASE=wrk
DB_PGSQL_DATABASE=wrk
DB_USERNAME=<db-user>
DB_PASSWORD=<db-password>
```

Then run:

```bash
cd /home/forge/wrk-popvox.on-forge.com/current
php artisan down
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
php artisan up
```

Verify active DB connection:

```bash
cd /home/forge/wrk-popvox.on-forge.com/current
php artisan tinker --execute="dump([
  'default' => config('database.default'),
  'driver' => DB::connection()->getDriverName(),
  'db' => DB::connection()->getDatabaseName(),
  'host' => config('database.connections.pgsql.host'),
  'users' => App\Models\User::count(),
  'organizations' => App\Models\Organization::count(),
  'meetings' => App\Models\Meeting::count(),
  'press_clips' => DB::table('press_clips')->count(),
]);"
```

Optional write test (insert/delete temporary cache row):

```bash
cd /home/forge/wrk-popvox.on-forge.com/current
php artisan tinker --execute="\$k='pg_cutover_test_'.time(); DB::table('cache')->insert(['key'=>\$k,'value'=>'ok','expiration'=>time()+300]); dump(DB::connection()->getDriverName(), DB::table('cache')->where('key', \$k)->exists()); DB::table('cache')->where('key', \$k)->delete();"
```

## Rollback Plan
If cutover fails:
1. Restore previous release.
2. Revert environment to SQLite.
3. Restore latest SQLite backup.
4. Capture failed migration logs and query errors for follow-up patch.

## Known Constraints
- Current test suite runs SQLite in-memory by default (`phpunit.xml`).
- Postgres-specific behavior should be validated in a staging environment.
- AI/model improvements are out of scope for DB migration and should be tracked separately.
