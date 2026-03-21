# DB Backup and Restore Drill (PostgreSQL)

## Goal
Validate you can backup and restore LinkPay data within an acceptable recovery window.

## Backup
Run from a secure environment with DB access:

```bash
pg_dump -h <host> -p <port> -U <user> -d <database> -Fc -f linkpay_backup.dump
```

Verify artifact:

```bash
pg_restore -l linkpay_backup.dump
```

## Restore Drill (staging DB)
1. Create/prepare target DB.
2. Restore:

```bash
pg_restore -h <host> -p <port> -U <user> -d <target_database> --clean --if-exists linkpay_backup.dump
```

3. Run app migrations against restored DB:

```bash
php artisan migrate --force
```

4. Verify critical records:
- users
- products
- payments
- webhook_events
- failed_jobs

## Acceptance
- Backup completes successfully.
- Restore completes successfully.
- App starts and key dashboard/admin pages load.
- Payment reconciliation and inventory pages show expected data.

## Frequency
- Weekly backup verification.
- Monthly full restore drill.

## Drill log
Record each run in `docs/OPERATIONS_DRILL_LOG.md` with:
- date/time
- backup artifact name and size
- restore target
- restore duration
- verification outcome
