# Phone-First OTP + PIN Auth Roadmap

## Goal
Make phone-first onboarding/login the default for sellers so auth feels native and fast on mobile, while keeping email/password as a fallback path.

## Core Product Rules
- Default auth entry: phone number.
- Default verification: OTP via SMS.
- Default credential after verification: 4-digit PIN.
- Email/password remains available as a secondary fallback path.
- Forgot PIN resets through phone OTP only.

## User Experience (Target)
1. Sign up:
- Enter phone number.
- Receive OTP.
- Verify OTP.
- Set 4-digit PIN.
- Enter dashboard onboarding checklist.

2. Sign in:
- Default tab: phone + PIN.
- Secondary options: phone + OTP, email + password.

3. Forgot PIN:
- Enter business phone.
- Receive OTP.
- Verify OTP.
- Set new PIN.

4. Post-auth onboarding:
- Complete profile essentials:
  - business name
  - business phone
  - business email
- Continue guided activation:
  - add first product
  - connect payouts
  - share public store link

## Security Constraints
- OTP:
  - TTL: 5-10 minutes
  - resend cooldown: minimum 30 seconds
  - max verify attempts per window
  - temporary lockout after repeated failures
- PIN:
  - exactly 4 numeric digits
  - stored as hash only (never plain text)
  - optional weak-PIN denylist (`0000`, `1234`, `1111`, etc.)
- Phone:
  - normalize to `+233XXXXXXXXX`
  - uniqueness enforced on normalized value

## Rollout Phases

### Current Progress
- Phase 0: complete
- Phase 1: complete
- Phase 2: complete
- Phase 3: complete
- Phase 4: complete
- Phase 5: complete
- Phase 6: complete
- Phase 7: complete

### Phase 0 - Docs and design baseline
- Add roadmap + test plan docs.
- Add explicit changelog entry for in-progress rollout.

### Phase 1 - Data and config foundation
- Add `users.pin_hash` (nullable).
- Confirm/strengthen normalized unique phone behavior.
- Add OTP config values (TTL, resend, lockout, attempts).

### Phase 2 - Phone OTP signup (default)
- Make phone OTP default on signup UI.
- Keep email signup fallback path.
- On OTP success, collect name and set PIN.

### Phase 3 - Phone + PIN login (default)
- Default login path becomes phone + PIN.
- Keep phone OTP alternative and email/password fallback.

### Phase 4 - Forgot PIN flow
- Implement phone OTP PIN reset journey.
- Add reset audit logging.

### Phase 5 - Onboarding integration
- Route newly activated users through guided onboarding checklist.
- Step 1 profile completion (business name/phone/email).

### Phase 6 - Security hardening
- Add/verify throttles and lockouts across auth endpoints.
- Add abuse telemetry and alert thresholds.

### Phase 7 - Testing + rollout
- Add feature tests for all phone/PIN flows.
- Add controlled rollout flag (`AUTH_PHONE_PIN_ENABLED`).
- Add rollback path back to current auth defaults.

## Proposed Env Surface
- `AUTH_PHONE_PIN_ENABLED=false`
- `OTP_TTL_SECONDS=600`
- `OTP_RESEND_COOLDOWN_SECONDS=30`
- `OTP_MAX_VERIFY_ATTEMPTS=5`
- `OTP_LOCKOUT_SECONDS=900`
- SMS provider vars (Hubtel) already in use.

## Operational Notes
- Monitor OTP send success/failure and delivery delays.
- Track auth conversion:
  - phone entered
  - OTP sent
  - OTP verified
  - PIN set
  - first successful PIN login

## Rollback Strategy
- Feature flag disabled -> existing auth defaults remain active.
- Keep email/password routes and templates intact during rollout.
