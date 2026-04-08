# Phone-First OTP + PIN Auth Test Plan

## Scope
Validate phone-first signup/login/reset flows, fallback email auth, onboarding handoff, and security controls.

## Environment Prerequisites
- SMS provider credentials configured (Hubtel).
- Queue and cache configured for OTP flow stability.
- Feature flag available for controlled rollout (`AUTH_PHONE_PIN_ENABLED`).

## Functional Test Matrix

### A. Signup (Phone OTP + PIN)
1. New phone -> OTP send succeeds -> verify -> set PIN -> account created.
2. Wrong OTP -> error shown, account not created.
3. Expired OTP -> error shown, resend path works.
4. Reused OTP -> blocked.
5. Existing phone on signup -> clear validation error.

### B. Login (Phone + PIN)
1. Valid phone + valid PIN -> login success.
2. Valid phone + wrong PIN -> login denied.
3. Multiple wrong PIN attempts -> lockout behavior enforced.
4. Unknown phone -> safe generic error.

### C. Phone OTP login alternative
1. Valid phone -> OTP send -> verify -> login success.
2. OTP attempts exceed limit -> throttled/locked.

### D. Forgot PIN
1. Known phone -> OTP -> verify -> set new PIN -> login with new PIN works.
2. Old PIN invalid after reset.
3. Unknown phone -> safe generic response.

### E. Email fallback
1. Email/password login still works.
2. Email registration path still works (if enabled).

### F. Onboarding handoff
1. First successful phone auth -> onboarding starts.
2. Profile step validates required business info.
3. User can continue to add product and payouts steps.

## Security and Abuse Tests
- OTP resend cooldown cannot be bypassed.
- OTP verify attempts capped per phone/IP window.
- PIN stored hashed only.
- CSRF/session behavior stable across auth transitions.
- Rate limits active on auth endpoints.

## UX Regression Checks
- Phone is default tab on signup/login.
- Email fallback is visible and functional.
- OTP input supports paste and mobile numeric keyboard.
- Error messages are clear and non-sensitive.

## Observability Checks
- Events/logs captured for:
  - OTP send success/fail
  - OTP verify success/fail
  - PIN login fail/lockout
  - PIN reset success/fail
- Alerts defined for abnormal OTP failure spikes.

## Release Gate
- All critical tests pass.
- No auth-blocking regression on existing email users.
- Smoke test passes on Render staging/production.

