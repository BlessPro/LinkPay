# Render Deployment Checklist + Rollback

## Pre-deploy
- Confirm `.env` has:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL` set to Render URL
  - `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_CALLBACK_URL`
  - `TWILIO_*` keys
  - `MONITORING_ERROR_TRACKING=true` (if enabled)
  - `MONITORING_PROVIDER=sentry` (or `bugsnag`)
  - `MONITORING_DSN=<provider_dsn>`
- Confirm worker process is running queue listener.
- Confirm cron/scheduler is active for daily reconciliation.

## Deploy
1. Deploy latest `main`.
2. Run migrations:
   - `php artisan migrate --force`
3. Warm caches:
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
4. Optional demo data seed for staging/demo:
   - `php artisan demo:seed --reset`

## Smoke tests (production)
1. HTTP route smoke:
   - `php artisan ops:smoke:http --base-url=https://<your-render-domain>`
2. Core feature smoke (run in CI/staging where test deps are available):
   - `php artisan ops:smoke:test-suite`
3. Payment callback/webhook verification:
   - successful test payment settles in app
   - reconciliation page shows expected status
4. Admin checks:
   - fallback queue visible
   - failed jobs page visible

## Rollback
1. Re-deploy previous stable commit in Render.
2. If rollback requires DB reversal, run targeted down migration only when safe:
   - `php artisan migrate:rollback --step=1`
3. Clear and rebuild caches.
4. Re-run smoke tests.
5. Record incident + rollback reason in release notes.

## Post-deploy checks
- Review `storage/logs/laravel.log` for new exceptions.
- Check admin reconciliation exceptions.
- Check failed jobs queue size.
- Run:
  - `php artisan ops:failed-jobs:alert --hours=24 --threshold=1`
  - `php artisan ops:failed-jobs:retry-latest --limit=25 --dry-run`
