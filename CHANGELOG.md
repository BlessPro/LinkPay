# Changelog

All notable changes to this project will be documented here.

## [Unreleased]
### Changelog Discipline
- Update this file in the same task/PR whenever code, UI, routes, data logic, or behavior changes.
- Add new entries under `Unreleased` with clear impact-focused notes.
- Move `Unreleased` items into a versioned section during release cut.

### Added
- WhatsApp OTP login via Twilio Verify.
- Seller and customer WhatsApp notifications via Twilio Messaging.
- Product inventory statuses (in stock, low stock, pre-order, sold out, unavailable).
- Product quick edit panel and export tools (CSV + PDF with chart snapshot).
- Product performance chart (Chart.js) with multi-metric toggles.
- Public page preview inside the seller dashboard.
- Interest capture on listings (lead capture + notifications).
- Admin OTP login and operations console for seller/payment controls.
- Admin payment fallback confirmation workflow for non-success callback fallbacks.
- Goals and Target page in dashboard with data-derived performance sections.
- Customers pages (list + profile details) with segmentation/status logic.
- Marketplace landing page with dynamic featured sellers and top products.
- Sellers directory page with search, category filter tabs, sort options, and pagination.
- Mobile bottom navigation with Home, Orders, Quick Action, Products, and Payments.
- Quick Action offcanvas bottom sheet with slide-up animation and fast actions.
- Admin payment reconciliation report page with mismatch classification and filters.
- Daily reconciliation command: `payments:reconcile` with scheduled execution.
- One-click admin reconciliation actions for `Retry verify`, `Mark success`, and `Mark failed`.
- Reconciliation CSV export endpoint for exception download and external review.
- Seller funnel analytics in Insights: `Views -> Clicks -> Add to Cart -> Checkout -> Paid` with stage cards and drop-off table.
- Product-level funnel table and UTM source funnel table in Insights.
- Rule-based action hints panel in Insights generated from funnel drop-offs and stage conversion thresholds.
- Inventory automation fields on products: `stock_quantity`, `low_stock_threshold`, `stock_alert_state`, `stock_alerted_at`.
- Automated inventory service to sync product status (`in_stock`/`low_stock`/`sold_out`) and send low-stock or sold-out alerts.
- Dashboard inventory alert widget with quick restock/edit actions.
- Product stock filter tabs and quick edit controls for quantity/threshold.
- Admin failed jobs page with retry / retry-all / forget actions.
- Render deployment checklist and rollback runbook.
- Database backup/restore drill guide for PostgreSQL operations.
- Webhook event hash tracking for duplicate-delivery idempotency handling.
- Monitoring config scaffold (`MONITORING_ERROR_TRACKING`) with Sentry-ready exception forwarding hook.
- Ops alert command: `ops:failed-jobs:alert` with hourly schedule and admin audit entry when thresholds are exceeded.
- Feature tests for webhook idempotency behavior (duplicate processed payload ignored, duplicate failed payload retried successfully).
- Feature smoke test for cart checkout flow (order/payment creation + cart clear behavior).
- Feature test for failed-job alert command audit logging.
- Reconciliation report now supports exception-type and aged-only filters, plus severity bucket summaries.
- Admin manual `mark failed` action now requires a meaningful note to improve traceability.
- Public checkout flows now enforce friendlier phone validation messages and stock-aware quantity guards before payment initialization.
- Feature test added for cart stock guard behavior when requested quantity exceeds available stock.
- Reconciliation command now emits threshold-breach audit alerts using configurable critical/high limits.
- Reconciliation admin page now shows threshold alert banners and per-exception expandable details.
- Public listing and invoice checkout forms now preserve input values and show inline field-level validation errors.
- Reconciliation admin page now supports bulk actions on currently visible exceptions (`Retry visible`, `Mark visible failed`).
- Added exception playbook hints directly in reconciliation UI to speed operator decision-making.
- Seller coupons feature added (create/activate/deactivate) with dedicated dashboard page.
- Cart checkout now supports `coupon_code` with percent/fixed discounts, min-order checks, and discounted payment initialization.
- Orders now store coupon usage (`coupon_code`, `discount_amount`) for downstream analytics and auditability.
- Weekly seller performance email command added: `sellers:weekly-performance-email` (scheduled weekly).
- Added coupon checkout feature test to validate discounted order/payment totals.
- Added coupon redemption tracking table with per-customer fingerprint uniqueness and IP address capture.
- Coupon validation now blocks re-use by the same customer and supports configurable IP-based re-use blocking window.
- Added tests for coupon redemption guardrails and redemption persistence on successful payment.
- Public buyer order tracking page added with reference + phone lookup and timeline states (`created`, `payment confirmed`, `accepted/cannot fulfill/pending`).
- Added feature tests covering successful buyer order tracking lookup and mismatched-phone guard behavior.
- Added persistent public cart storage keyed by browser token (`lp_cart_token`) with database-backed restore across session resets.
- Added feature tests for saved-cart restoration and post-checkout cart persistence cleanup.
- Added legal pages for `Privacy Policy` and `Terms of Service` with public routes.
- Added formal data deletion request flow (authenticated seller endpoint + compliance table + profile UI form).
- Added feature tests for legal page availability and data deletion request creation.
- Added deterministic demo data seeder (`DemoDataSeeder`) and `demo:seed` command for repeatable staging/local demos.
- Added launch smoke commands: `ops:smoke:http` (route checks) and `ops:smoke:test-suite` (auth/checkout/webhook smoke tests).
- Added `ops:failed-jobs:retry-latest` command for controlled bulk retry operations with audit logging.
- Added `MonitoringServiceProvider` with environment-driven provider/DSN config (`MONITORING_PROVIDER`, `MONITORING_DSN`) and Sentry auto-binding when SDK is installed.
- Added operations drill log template (`docs/OPERATIONS_DRILL_LOG.md`) and expanded deployment/backup docs with executable runbook commands.
- Added `docs/SECURITY_CHECKLIST.md` to track prioritized future security hardening work.
- Race-condition hardening:
- `PaymentService::markSuccess` now uses a per-payment lock to prevent duplicate side effects under concurrent webhook/success callbacks.
- Webhook event storage now enforces unique `(provider, event_hash)` with migration-time dedupe of older duplicates.
- Paystack webhook handler now safely handles unique-conflict races by fetching existing hashed events.
- Added idempotency test to verify stock and coupon side effects run once even if `markSuccess` is called twice.

### Changed
- Refined dashboard UI and analytics layout for products.
- Rebranded public-facing UI language to `8Kommerce` and reused brand lockup.
- Reworked public landing header/menu and full-page visual structure.
- Updated dashboard KPI cards to seller-focused metrics: `Orders`, `Amount Received`, `Traffic`, `New Customers`, and `Conversion Rate`.
- Added subtle color differentiation for KPI cards.
- Mobile KPI behavior updated to two-cards-per-row and a flat marquee summary strip.
- Dashboard mobile navigation restyled to a floating dark glass bar with icon-first items and raised center action button.
- Product OG image layout refreshed to prioritize a large product image with the price shown beneath for stronger share previews.
- Replaced Quick Action standalone page navigation with in-layout offcanvas interaction.
- Paystack service expanded to fetch transactions by date range for reconciliation workflows.
- Admin layout navigation updated with dedicated `Reconciliation` entry.
- Reconciliation exceptions are now severity-prioritized with aged (`>24h`) issues listed first.
- Public listing flow now tracks additional analytics events: `add_to_cart` and `checkout_started`.
- Payment success now decrements stock for direct product payments and cart order items.
- Quick Action offcanvas now includes a `Review low stock` shortcut.
- Admin overview now surfaces failed job count with quick link to queue operations.
- Paystack webhook handler now reuses previous failed webhook entries and ignores already-processed duplicate payloads.
- Public success page now links cart buyers directly to order tracking, and public header includes a quick `Track order` entry.
- Applied explicit request throttles on sensitive public checkout/cart/payment routes and critical admin mutation routes.
- Admin audit trail expanded with failure-path logs and user-agent metadata for stronger operational forensics.
- Exception monitoring handler now supports provider-specific capture flow (`sentry`/`bugsnag`) with explicit fallback logging when provider bindings are unavailable.

### Fixed
- Fixed public landing crash when top product slug is missing by falling back to seller page link.
- Fixed cart remove method mismatch handling and route usage around listing cart actions.
- Fixed customer status derivation failure caused by missing `days_since_last` key path.
- Hardened users phone migration to be idempotent to reduce migration failures across environments.
- Public add-to-cart now defaults missing quantity to `1` instead of failing validation.
- Public listing error banner now shows exact validation messages (not only generic text).
- Added HTTPS URL forcing support (`APP_FORCE_HTTPS`) to prevent insecure form action URLs in production deployments.
