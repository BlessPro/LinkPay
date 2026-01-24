# LinkPay

LinkPay is a WhatsApp-first payment facilitation platform for sellers. It lets sellers create public mini listing pages with Pay Now buttons, generate one-time invoice links that accept full or partial payments, and track payments and notifications in a clean dashboard. The platform uses a single Paystack account and creates subaccounts for each seller so payouts are routed automatically with a platform fee deducted.

## What the app does
- Seller authentication with profile and payout setup.
- Public listing page per seller with product payments.
- One-time invoice links with full or partial payment support.
- Paystack checkout, webhook verification, and server-side transaction verification.
- Seller dashboard with products, invoices, payments, notifications, and insights.
- Admin dashboard with system KPIs and health stats.

## Tech stack
- Laravel 11 (Blade)
- Tailwind CSS
- PostgreSQL
- Paystack API (platform account + seller subaccounts)

## Local setup (quick)
1) Install dependencies:
   - `composer install`
   - `npm install && npm run dev`
2) Configure `.env` for PostgreSQL and Paystack.
3) Run migrations:
   - `php artisan migrate`
4) Start the app:
   - `php artisan serve`

## Future projections
- Phone login with OTP verification.
- Enhanced change log tracking for key platform actions.
- Unified notifications + chat history for end-to-end messaging (WhatsApp + Instagram).
- Expanded analytics and attribution insights.

