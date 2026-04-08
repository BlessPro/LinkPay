# 8Kommerce

8Kommerce is a WhatsApp-first payment facilitation platform for sellers. It lets sellers create public mini listing pages with Pay Now buttons, generate one-time invoice links that accept full or partial payments, and track payments and notifications in a clean dashboard. The platform uses a single Paystack account and creates subaccounts for each seller so payouts are routed automatically with a platform fee deducted.

## What the app does
- Seller authentication with profile and payout setup.
- WhatsApp OTP login with Twilio Verify (phone optional at sign up).
- Public listing page per seller with product payments and “Interested” lead capture.
- One-time invoice links with full or partial payment support.
- Paystack checkout, webhook verification, and server-side transaction verification.
- Seller dashboard with products, invoices, payments, notifications, and insights.
- Product analytics (views, clicks, payments, revenue, conversion) with Chart.js.
- Inventory exports (CSV and PDF with embedded chart).
- Admin dashboard with system KPIs and health stats.

## Tech stack
- Laravel 11/12 (Blade)
- Tailwind CSS
- PostgreSQL
- Paystack API (platform account + seller subaccounts)
- Chart.js
- Twilio Verify + Twilio WhatsApp Messaging

## Local setup (quick)
1) Install dependencies:
   - `composer install`
   - `npm install && npm run dev`
2) Configure `.env` for PostgreSQL and Paystack.
3) Run migrations:
   - `php artisan migrate`
4) Start the app:
   - `php artisan serve`

## Versioning
- The app now uses Semantic Versioning (`MAJOR.MINOR.PATCH`).
- Canonical version is stored in [`VERSION`](./VERSION).
- Release process is documented in [`docs/VERSIONING.md`](./docs/VERSIONING.md).

## Future projections
- Multi-country phone login and verification.
- Enhanced change log tracking for key platform actions.
- Unified notifications + chat history for end-to-end messaging (WhatsApp + Instagram).
- Deeper insights: attribution, device mix, and conversion funnels.
- Expanded analytics and attribution insights.
