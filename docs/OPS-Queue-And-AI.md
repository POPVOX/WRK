# Ops Guide — Queue Workers and AI

## Requirements
- Queue connection: database (configured)
- Workers:
  - Local/dev: php artisan queue:work --queue=default --sleep=1 --max-time=3600
  - Production: supervise 2–4 workers minimum

Example Supervisor (Ubuntu) — /etc/supervisor/conf.d/pvox-queue.conf:
[program:pvox-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --queue=default --sleep=1 --tries=2 --timeout=120
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/supervisor/pvox-queue.log
stopwaitsecs=5

Reload:
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl restart pvox-queue:*

## Environment Variables
- ANTHROPIC_API_KEY=your_api_key
- AI_FEATURES_ENABLED=true|false (optional feature flag)
- AI_HTTP_TIMEOUT=120 (optional override)

## Rate Limiting (Guidance)
- Apply throttle middleware to AI endpoints or Livewire actions invoking AI.
- Suggested: 30 requests / 1 minute per user for chat; 10 / 5 minutes for style checks.
- Log drop events and show friendly UI error on limit.

## Observability
- App\Support\AI\AnthropicClient logs success/fail + duration.
- Monitor: queue:failed, horizon/supervisor logs, and HTTP 429/5xx rates.

## Safety
- Only allow expected file types.
- Validate file paths stay within project directory.
- Escape/strip raw HTML in markdown where possible.
- Store AI outputs and structured suggestions in storage/app/style_checks.
