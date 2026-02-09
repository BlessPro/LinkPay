@extends('layouts.public')

@section('content')
    <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-emerald-100 bg-white/80 p-8 shadow-sm">
            <p class="text-xs uppercase tracking-[0.35em] text-emerald-500">WhatsApp-first payments</p>
            <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl">
                8Kommerce helps you sell and collect payments from WhatsApp.
            </h1>
            <p class="mt-4 text-base text-slate-600">
                Create a mini product page and share pay links in seconds. Collect full or partial payments with Paystack,
                and track views, clicks, and revenue from one clean dashboard.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('register') }}" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                    Start free trial
                </a>
                <a href="{{ route('pricing') }}" class="rounded-full border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    View pricing
                </a>
                <a href="{{ route('login') }}" class="rounded-full border border-transparent px-6 py-3 text-sm font-semibold text-slate-600 hover:text-emerald-700">
                    Seller login
                </a>
            </div>
        </div>
        <div class="grid gap-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Seller tools</p>
                <h2 class="mt-3 text-2xl font-semibold text-slate-900">Mini listings + Pay Now</h2>
                <p class="mt-3 text-sm text-slate-600">
                    Showcase services with clean cards and instant Paystack checkout links. Built for mobile customers.
                </p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Smart invoices</p>
                <h2 class="mt-3 text-2xl font-semibold text-slate-900">Partial payments included</h2>
                <p class="mt-3 text-sm text-slate-600">
                    Send one-time invoice links, collect deposits, and keep the same link until the balance is cleared.
                </p>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-emerald-50/70 p-6">
                <p class="text-xs uppercase tracking-[0.25em] text-emerald-500">No Paystack account needed</p>
                <p class="mt-3 text-sm text-emerald-700">
                    8Kommerce creates and manages subaccounts for every seller, with platform fees automatically applied.
                </p>
            </div>
        </div>
    </section>

    <section class="mt-12">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Pricing</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Choose what you want 8Kommerce to do</h2>
                <p class="mt-2 text-sm text-slate-600">Start with a 7-day free trial. Upgrade anytime.</p>
            </div>
            <a href="{{ route('pricing') }}" class="hidden rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700 sm:inline-flex">
                Full pricing
            </a>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Payments</h3>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Most popular</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">Collect payments + invoices + records.</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">{{ $plans['payments']['price_range'] ?? 'GHS 30-50 / month' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $plans['payments']['commission_text'] ?? '1% per successful payment' }}</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>Pay Now buttons</li>
                    <li>Invoice links + partial payments</li>
                    <li>Payments dashboard + notifications</li>
                </ul>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Promotion</h3>
                <p class="mt-2 text-sm text-slate-600">Public pages + products + analytics-lite.</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">{{ $plans['promotion']['price_range'] ?? 'GHS 60-100 / month' }}</p>
                <p class="mt-1 text-sm text-slate-500">No payments through 8Kommerce</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>Unlimited products/services</li>
                    <li>WhatsApp chat CTA</li>
                    <li>Views + clicks</li>
                </ul>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Enterprise</h3>
                <p class="mt-2 text-sm text-slate-600">Teams + advanced analytics (coming later).</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">Contact sales</p>
                <p class="mt-1 text-sm text-slate-500">Custom setup</p>
                <a href="{{ route('pricing') }}" class="mt-6 inline-flex rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Learn more
                </a>
            </div>
        </div>
    </section>
@endsection
