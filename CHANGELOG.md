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

### Changed
- Refined dashboard UI and analytics layout for products.
- Rebranded public-facing UI language to `8Kommerce` and reused brand lockup.
- Reworked public landing header/menu and full-page visual structure.
- Updated dashboard KPI cards to seller-focused metrics: `Orders`, `Amount Received`, `Traffic`, `New Customers`, and `Conversion Rate`.
- Added subtle color differentiation for KPI cards.
- Mobile KPI behavior updated to two-cards-per-row and a flat marquee summary strip.
- Dashboard mobile navigation restyled to a floating dark glass bar with icon-first items and raised center action button.
- Replaced Quick Action standalone page navigation with in-layout offcanvas interaction.
- Paystack service expanded to fetch transactions by date range for reconciliation workflows.
- Admin layout navigation updated with dedicated `Reconciliation` entry.
- Reconciliation exceptions are now severity-prioritized with aged (`>24h`) issues listed first.
- Public listing flow now tracks additional analytics events: `add_to_cart` and `checkout_started`.
- Payment success now decrements stock for direct product payments and cart order items.
- Quick Action offcanvas now includes a `Review low stock` shortcut.

### Fixed
- Fixed public landing crash when top product slug is missing by falling back to seller page link.
- Fixed cart remove method mismatch handling and route usage around listing cart actions.
- Fixed customer status derivation failure caused by missing `days_since_last` key path.
- Hardened users phone migration to be idempotent to reduce migration failures across environments.
