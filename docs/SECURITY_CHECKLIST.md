# Security Checklist (Future Steps)

This file tracks security next steps only. It is a planning artifact and does not indicate active implementation work.

## Current Snapshot
- Strong baseline already exists for webhook verification, payment idempotency, route throttling, admin audit logs, and legal pages.

## Next Few Steps (Planned)

### P0 (Do Soon)
- Enforce stronger admin MFA policy (second factor beyond OTP where possible).
- Finalize production monitoring integration (Sentry/Bugsnag package + DSN verification + alert routing).
- Define and enforce payout/account data encryption-at-rest policy.

### P1 (Next Iteration)
- Add bot/abuse controls for public high-risk forms (checkout/interest endpoints).
- Implement immutable/tamper-evident audit retention strategy (archival + integrity checks).
- Create data retention policy with scheduled cleanup for stale PII and expired operational logs.

### P2 (After Launch Stabilization)
- Add adaptive fraud rules (velocity checks by IP/device/fingerprint).
- Add geo/risk-based step-up verification for suspicious seller actions.
- Run periodic dependency and infrastructure security review with documented remediation SLAs.

## Operating Cadence
- Review this checklist weekly during launch hardening.
- Move completed items into changelog/release notes when implemented.
