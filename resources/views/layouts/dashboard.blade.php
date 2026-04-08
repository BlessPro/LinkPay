<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#059669">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/icons/icon-192.svg" type="image/svg+xml">

        <title>{{ config('app.name', '8Kommerce') }}</title>
        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('lp_theme');
                    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var dark = saved ? saved === 'dark' : prefersDark;
                    document.documentElement.classList.toggle('theme-dark', dark);
                } catch (_) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />
        <style>
            body, aside, header, main, nav, section, div, a, button, input, textarea, select {
                transition: background-color .2s ease, color .2s ease, border-color .2s ease;
            }
            html.theme-dark body {
                background: linear-gradient(140deg, #020617 0%, #0f172a 45%, #022c22 100%);
                color: #e2e8f0;
            }
            html.theme-dark aside,
            html.theme-dark header,
            html.theme-dark .js-mobile-menu-panel {
                background-color: #0b1220 !important;
            }
            html.theme-dark .bg-white,
            html.theme-dark .bg-white\/70,
            html.theme-dark .bg-white\/90,
            html.theme-dark .bg-slate-50,
            html.theme-dark .bg-slate-50\/60,
            html.theme-dark .bg-slate-50\/70 {
                background-color: #0f172a !important;
            }
            html.theme-dark .bg-emerald-50,
            html.theme-dark .bg-emerald-50\/60,
            html.theme-dark .bg-emerald-50\/70 {
                background-color: rgba(16, 185, 129, 0.16) !important;
            }
            html.theme-dark .bg-blue-50,
            html.theme-dark .bg-blue-50\/60,
            html.theme-dark .bg-blue-50\/70 {
                background-color: rgba(59, 130, 246, 0.16) !important;
            }
            html.theme-dark .bg-indigo-50,
            html.theme-dark .bg-indigo-50\/60,
            html.theme-dark .bg-indigo-50\/70 {
                background-color: rgba(99, 102, 241, 0.18) !important;
            }
            html.theme-dark .bg-amber-50,
            html.theme-dark .bg-amber-50\/60,
            html.theme-dark .bg-amber-50\/70 {
                background-color: rgba(245, 158, 11, 0.18) !important;
            }
            html.theme-dark .bg-fuchsia-50,
            html.theme-dark .bg-fuchsia-50\/60,
            html.theme-dark .bg-fuchsia-50\/70 {
                background-color: rgba(217, 70, 239, 0.16) !important;
            }
            html.theme-dark .border-slate-200,
            html.theme-dark .border-slate-100,
            html.theme-dark .border-slate-300\/70,
            html.theme-dark .border-slate-700\/70 {
                border-color: #334155 !important;
            }
            html.theme-dark .border-emerald-200,
            html.theme-dark .border-emerald-100 { border-color: rgba(16, 185, 129, 0.45) !important; }
            html.theme-dark .border-blue-200,
            html.theme-dark .border-blue-100 { border-color: rgba(59, 130, 246, 0.45) !important; }
            html.theme-dark .border-indigo-200,
            html.theme-dark .border-indigo-100 { border-color: rgba(99, 102, 241, 0.45) !important; }
            html.theme-dark .border-amber-200,
            html.theme-dark .border-amber-100 { border-color: rgba(245, 158, 11, 0.45) !important; }
            html.theme-dark .border-fuchsia-200,
            html.theme-dark .border-fuchsia-100 { border-color: rgba(217, 70, 239, 0.45) !important; }
            html.theme-dark .text-slate-900 { color: #f8fafc !important; }
            html.theme-dark .text-slate-800 { color: #e2e8f0 !important; }
            html.theme-dark .text-slate-700 { color: #cbd5e1 !important; }
            html.theme-dark .text-slate-600 { color: #94a3b8 !important; }
            html.theme-dark .text-slate-500,
            html.theme-dark .text-slate-400 { color: #94a3b8 !important; }
            html.theme-dark .text-emerald-500,
            html.theme-dark .text-emerald-600,
            html.theme-dark .text-emerald-700,
            html.theme-dark .text-emerald-800 { color: #6ee7b7 !important; }
            html.theme-dark .text-blue-500,
            html.theme-dark .text-blue-600,
            html.theme-dark .text-blue-700,
            html.theme-dark .text-blue-800 { color: #93c5fd !important; }
            html.theme-dark .text-indigo-500,
            html.theme-dark .text-indigo-600,
            html.theme-dark .text-indigo-700,
            html.theme-dark .text-indigo-800 { color: #a5b4fc !important; }
            html.theme-dark .text-amber-500,
            html.theme-dark .text-amber-600,
            html.theme-dark .text-amber-700,
            html.theme-dark .text-amber-800,
            html.theme-dark .text-amber-900 { color: #fcd34d !important; }
            html.theme-dark .text-fuchsia-500,
            html.theme-dark .text-fuchsia-600,
            html.theme-dark .text-fuchsia-700,
            html.theme-dark .text-fuchsia-800 { color: #f0abfc !important; }
            html.theme-dark .js-mobile-menu-panel a,
            html.theme-dark aside a,
            html.theme-dark aside button {
                color: #cbd5e1 !important;
            }
            html.theme-dark aside .bg-emerald-50,
            html.theme-dark .js-mobile-menu-panel .bg-emerald-50 {
                background-color: rgba(16, 185, 129, 0.18) !important;
            }
            html.theme-dark .hover\:bg-emerald-50:hover { background-color: rgba(16, 185, 129, 0.12) !important; }
            html.theme-dark .shadow-sm,
            html.theme-dark .shadow-2xl,
            html.theme-dark .shadow-lg {
                box-shadow: 0 12px 34px rgba(2, 6, 23, 0.45) !important;
            }

            /* Forms: keep readable text and placeholders in all modes */
            input,
            textarea,
            select {
                background-color: #ffffff !important;
                color: #111827 !important;
            }
            input::placeholder,
            textarea::placeholder {
                color: #334155 !important;
                opacity: 1;
            }
            html.theme-dark input,
            html.theme-dark textarea,
            html.theme-dark select {
                background-color: #ffffff !important;
                color: #111827 !important;
                border-color: #cbd5e1 !important;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans bg-gradient-to-br from-slate-50 via-white to-emerald-50 text-slate-900">
        @php
            $user = auth()->user();
            $profile = $user->sellerProfile;
            $canPromotion = $user->canUsePromotionFeatures();
            $canUsePayments = $user->canUsePaymentsFeature();
            $publicUrl = $profile?->public_slug ? route('public.listing', $profile->public_slug) : null;
            $onboarding = app(\App\Services\OnboardingService::class)->forUser($user);
            $navClass = function (string $route) {
                return request()->routeIs($route)
                    ? 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold bg-emerald-50 text-emerald-700'
                    : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700';
            };
        @endphp

        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:fixed lg:left-0 lg:top-0 lg:z-30 lg:flex lg:h-screen lg:max-h-screen lg:w-64 lg:flex-col lg:overflow-hidden lg:border-r lg:border-slate-200 lg:bg-white">
                <div class="flex items-center gap-2 px-6 py-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">
                        8K
                    </div>
                    <div>
                        <p class="text-sm uppercase tracking-widest text-slate-400">8Kommerce</p>
                        <p class="text-base font-semibold text-slate-900">{{ $profile?->business_name ?? 'Seller' }}</p>
                    </div>
                </div>

                <nav class="space-y-1 px-4">
                    <a href="{{ route('dashboard') }}" class="{{ $navClass('dashboard') }} js-tour-dashboard-nav">Dashboard</a>
                    <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 js-quick-actions-open js-tour-quick-action">Quick Action</button>
                    <a href="{{ route('profile.edit') }}" class="{{ $navClass('profile.*') }}">Profile</a>
                    <a href="{{ route('products.index') }}" class="{{ $navClass('products.*') }} js-tour-products-nav">Products</a>
                    <a href="{{ route('coupons.index') }}" class="{{ $navClass('coupons.*') }}">Coupons</a>
                    <a href="{{ route('customers.index') }}" class="{{ $navClass('customers.*') }}">Customers</a>
                    @if($canUsePayments)
                        <a href="{{ route('invoices.index') }}" class="{{ $navClass('invoices.*') }}">Invoices</a>
                    <a href="{{ route('payments.index') }}" class="{{ $navClass('payments.*') }} js-tour-payments-nav">Payments</a>
                    @else
                        <a href="{{ route('billing.upgrade') }}" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                            <span>Invoices</span>
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Upgrade</span>
                        </a>
                        <a href="{{ route('billing.upgrade') }}" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                            <span>Payments</span>
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Upgrade</span>
                        </a>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="{{ $navClass('notifications.*') }}">Notifications</a>
                    <a href="{{ route('goals.index') }}" class="{{ $navClass('goals.*') }}">Goals and Target</a>
                    <a href="{{ route('insights.index') }}" class="{{ $navClass('insights.*') }}">Insights</a>
                    <a href="{{ route('billing.show') }}" class="{{ $navClass('billing.*') }}">Billing</a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="{{ $navClass('admin.*') }}">Admin</a>
                    @endif
                    @if($profile)
                        <a href="{{ route('public.preview') }}" class="{{ $navClass('public.preview') }}">Public page</a>
                    @endif
                    @if(! $onboarding['is_complete'])
                        <a href="{{ route('onboarding.index') }}" class="{{ $navClass('onboarding.*') }}">Onboarding</a>
                    @endif
                </nav>

                <div class="mt-auto px-6 py-5">
                    <p class="text-xs uppercase tracking-widest text-slate-400">Payout status</p>
                    @if($profile?->paystack_subaccount_code)
                        <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Paystack Connected</p>
                    @else
                        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">Not Connected</p>
                    @endif
                </div>
            </aside>

            <div class="flex-1 overflow-x-hidden lg:pl-64">
                <header class="fixed top-0 left-0 right-0 z-40 border-b border-slate-200 bg-white/70 backdrop-blur lg:left-64">
                    <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-400 sm:hidden">8Kommerce</p>
                            <p class="hidden text-xs uppercase tracking-widest text-slate-400 sm:block">Dashboard</p>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Overview' }}</h1>
                        </div>
                        <div class="hidden items-center sm:flex">
                            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-700 js-theme-toggle" aria-label="Toggle dark mode" title="Toggle theme">
                                    <svg class="h-4 w-4 js-theme-icon-moon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3c0 .3-.01.6-.01.91A8.99 8.99 0 0 0 21 12.79Z"/></svg>
                                    <svg class="hidden h-4 w-4 js-theme-icon-sun" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15.114 6.364-1.591-1.591M7.477 7.477 5.886 5.886m12.228 0-1.591 1.591M7.477 16.523l-1.591 1.591M12 16.5A4.5 4.5 0 1 0 12 7.5a4.5 4.5 0 0 0 0 9Z"/></svg>
                                </button>
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Account</p>
                                    <p class="text-sm font-medium text-slate-700">{{ auth()->user()->email }}</p>
                                </div>
                                <div class="h-7 w-px bg-slate-200"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:border-emerald-200 hover:text-emerald-700">Logout</button>
                                </form>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 sm:hidden">
                            <button
                                type="button"
                                aria-label="Toggle dark mode"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 js-theme-toggle"
                            >
                                <svg class="h-5 w-5 js-theme-icon-moon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3c0 .3-.01.6-.01.91A8.99 8.99 0 0 0 21 12.79Z"/></svg>
                                <svg class="hidden h-5 w-5 js-theme-icon-sun" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15.114 6.364-1.591-1.591M7.477 7.477 5.886 5.886m12.228 0-1.591 1.591M7.477 16.523l-1.591 1.591M12 16.5A4.5 4.5 0 1 0 12 7.5a4.5 4.5 0 0 0 0 9Z"/></svg>
                            </button>
                            <a
                                href="{{ route('notifications.index') }}"
                                aria-label="Notifications"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 17.25h5.25l-1.431-1.431A2.25 2.25 0 0 1 17.25 14.25V10.5a5.25 5.25 0 1 0-10.5 0v3.75a2.25 2.25 0 0 1-.819 1.569L4.5 17.25h5.25m4.5 0a2.25 2.25 0 1 1-4.5 0m4.5 0h-4.5"/></svg>
                            </a>
                            <button
                                type="button"
                                aria-label="Open menu"
                                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 js-mobile-menu-open"
                            >
                                Menu
                            </button>
                        </div>
                    </div>
                </header>

                <main class="px-4 py-6 pb-24 pt-24 sm:px-6 sm:pb-6 lg:px-8">
                    @php
                        $trialActive = $user->isOnTrial() && $user->trial_ends_at;
                        $daysLeft = $trialActive ? max(0, now()->diffInDays($user->trial_ends_at, false)) : null;
                    @endphp
                    @if($publicUrl || $trialActive)
                        <div class="mb-6 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    @if($trialActive)
                                        <p class="font-semibold text-emerald-800">
                                            Trial active - {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left (ends {{ $user->trial_ends_at->toFormattedDateString() }})
                                        </p>
                                        <a href="{{ route('billing.show') }}" class="mt-2 inline-flex rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                            Manage plan
                                        </a>
                                    @endif
                                </div>
                                @if($publicUrl)
                                    <div class="ml-auto flex flex-wrap items-center gap-3 sm:justify-end">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Public link</p>
                                        <a href="{{ $publicUrl }}" target="_blank" rel="noreferrer noopener" class="text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                                            {{ $publicUrl }}
                                        </a>
                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700 js-copy-public-link js-tour-copy-public-link"
                                            data-copy-value="{{ $publicUrl }}"
                                        >
                                            Copy link
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>
        <nav class="fixed bottom-3 left-3 right-3 z-40 lg:hidden">
            <div class="grid grid-cols-5 items-end rounded-[2rem] border border-slate-700/70 bg-slate-950/95 px-2 pb-2 pt-1 shadow-2xl backdrop-blur-xl">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[11px] {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-300' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 11.25 8.25-7.5 8.25 7.5v8.25a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75V15a2.25 2.25 0 1 0-4.5 0v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-8.25Z"/></svg>
                    <span class="font-medium">Home</span>
                </a>
                <a href="{{ $canPromotion ? route('products.orders') : route('billing.upgrade') }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[11px] {{ request()->routeIs('products.orders') ? 'text-emerald-400' : 'text-slate-300' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-15 3h13.5m-12 3h10.5m-9 3h7.5"/></svg>
                    <span class="font-medium">Orders</span>
                </a>
                <button type="button" class="group -mt-6 flex flex-col items-center gap-1 px-1 py-1 text-[11px] text-slate-200 js-quick-actions-open">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-slate-900 bg-blue-600 shadow-[0_10px_22px_rgba(37,99,235,0.5)] transition group-active:scale-95">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v13.5m6.75-6.75H5.25"/></svg>
                    </span>
                    <span class="font-semibold">Quick</span>
                </button>
                <a href="{{ $canPromotion ? route('products.index') : route('billing.upgrade') }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[11px] {{ request()->routeIs('products.*') ? 'text-emerald-400' : 'text-slate-300' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6.75h9a2.25 2.25 0 0 1 2.25 2.25v7.5A2.25 2.25 0 0 1 16.5 18.75h-9a2.25 2.25 0 0 1-2.25-2.25V9A2.25 2.25 0 0 1 7.5 6.75Zm3-3h3m-1.5 0v3"/></svg>
                    <span class="font-medium">Products</span>
                </a>
                <a href="{{ $canUsePayments ? route('payments.index') : route('billing.upgrade') }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[11px] {{ request()->routeIs('payments.*') ? 'text-emerald-400' : 'text-slate-300' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 18 18.75H6a2.25 2.25 0 0 1-2.25-2.25v-9ZM3.75 9.75h16.5"/></svg>
                    <span class="font-medium">Payments</span>
                </a>
            </div>
        </nav>
        <div class="pointer-events-none fixed inset-0 z-40 bg-slate-900/40 opacity-0 transition-opacity duration-300 js-mobile-menu-backdrop"></div>
        <aside class="fixed right-0 top-0 z-50 h-full w-[84%] max-w-xs translate-x-full border-l border-slate-200 bg-white p-4 shadow-2xl transition-transform duration-300 ease-out js-mobile-menu-panel lg:hidden">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-900">Menu</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-600 js-theme-toggle" aria-label="Toggle dark mode">
                        <svg class="h-4 w-4 js-theme-icon-moon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3c0 .3-.01.6-.01.91A8.99 8.99 0 0 0 21 12.79Z"/></svg>
                        <svg class="hidden h-4 w-4 js-theme-icon-sun" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15.114 6.364-1.591-1.591M7.477 7.477 5.886 5.886m12.228 0-1.591 1.591M7.477 16.523l-1.591 1.591M12 16.5A4.5 4.5 0 1 0 12 7.5a4.5 4.5 0 0 0 0 9Z"/></svg>
                    </button>
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 js-mobile-menu-close">Close</button>
                </div>
            </div>
            <nav class="space-y-2 text-sm">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Dashboard</a>
                <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 js-quick-actions-open">Quick Action</button>
                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Profile</a>
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Products</a>
                <a href="{{ route('coupons.index') }}" class="{{ request()->routeIs('coupons.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Coupons</a>
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Customers</a>
                @if($canUsePayments)
                    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Invoices</a>
                    <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Payments</a>
                @else
                    <a href="{{ route('billing.upgrade') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">Invoices <span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Upgrade</span></a>
                    <a href="{{ route('billing.upgrade') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">Payments <span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Upgrade</span></a>
                @endif
                <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Notifications</a>
                <a href="{{ route('goals.index') }}" class="{{ request()->routeIs('goals.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Goals and Target</a>
                <a href="{{ route('insights.index') }}" class="{{ request()->routeIs('insights.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Insights</a>
                <a href="{{ route('billing.show') }}" class="{{ request()->routeIs('billing.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Billing</a>
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Admin</a>
                @endif
                @if($profile)
                    <a href="{{ route('public.preview') }}" class="{{ request()->routeIs('public.preview') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Public page</a>
                @endif
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-slate-100 pt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Logout</button>
            </form>
        </aside>
        <div class="pointer-events-none fixed inset-0 z-50 bg-slate-900/40 opacity-0 transition-opacity duration-300 js-quick-actions-backdrop"></div>
        <section class="fixed inset-x-0 bottom-0 z-50 translate-y-full rounded-t-3xl border-t border-slate-200 bg-white p-5 shadow-2xl transition-transform duration-300 ease-out js-quick-actions-panel">
            <div class="mx-auto mb-4 h-1.5 w-14 rounded-full bg-slate-200"></div>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Quick Actions</h2>
                <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 js-quick-actions-close">Close</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ $canPromotion ? route('products.create') : route('billing.upgrade') }}" class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-slate-800">Add product</a>
                <a href="{{ $canPromotion ? route('products.orders') : route('billing.upgrade') }}" class="rounded-xl border border-blue-200 bg-blue-50/70 px-4 py-3 text-sm font-medium text-slate-800">Review orders</a>
                <a href="{{ $canUsePayments ? route('payments.index') : route('billing.upgrade') }}" class="rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm font-medium text-slate-800">Check payments</a>
                <a href="{{ $canUsePayments ? route('invoices.create') : route('billing.upgrade') }}" class="rounded-xl border border-fuchsia-200 bg-fuchsia-50/70 px-4 py-3 text-sm font-medium text-slate-800">Create invoice</a>
                <a href="{{ $canPromotion ? route('coupons.index') : route('billing.upgrade') }}" class="rounded-xl border border-cyan-200 bg-cyan-50/70 px-4 py-3 text-sm font-medium text-slate-800">Coupons</a>
                <a href="{{ $canPromotion ? route('products.index', ['stock' => \App\Models\Product::STATUS_LOW_STOCK]) : route('billing.upgrade') }}" class="rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm font-medium text-slate-800">Review low stock</a>
                <a href="{{ route('notifications.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800">Open notifications</a>
                <button type="button" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-medium text-slate-800 lg:block js-start-desktop-tour">Start tutorial</button>
            </div>
        </section>
        @if(! $onboarding['is_complete'] && !request()->routeIs('onboarding.*'))
            <aside class="js-onboarding-popup hidden lg:block fixed bottom-5 right-5 z-40 w-[360px] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl" data-dismissed="{{ data_get($onboarding, 'state.desktop_popup_dismissed') ? '1' : '0' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Onboarding</p>
                        <h3 class="mt-1 text-sm font-semibold text-slate-900">{{ $onboarding['completed_count'] }}/{{ $onboarding['total_count'] }} complete</h3>
                    </div>
                    <button type="button" class="js-onboarding-close rounded-full border border-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:border-slate-300">Close</button>
                </div>
                <div class="mt-3 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $onboarding['percent'] }}%"></div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($onboarding['steps'] as $step)
                        @php($isDone = (bool) ($step['effective_done'] ?? $step['completed']))
                        @if($isDone)
                            <div class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2">
                                <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] text-white">
                                    &check;
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-900">{{ $step['title'] }}</p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $step['description'] }}
                                        @if(!empty($step['skipped']))
                                            <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Skipped</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            <a href="{{ $step['action_url'] }}" class="group flex items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 hover:border-emerald-200 hover:bg-emerald-50/50">
                                <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] text-slate-600 group-hover:bg-emerald-200 group-hover:text-emerald-800">
                                    &bull;
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-900">{{ $step['title'] }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $step['description'] }}</p>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
                @if($onboarding['next_step'])
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ $onboarding['next_step']['action_url'] }}" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                            {{ $onboarding['next_step']['action_label'] }}
                        </a>
                        @if(($onboarding['next_step']['id'] ?? null) === 'share_store' && !empty($onboarding['next_step']['public_url']))
                            <button type="button" class="js-onboarding-copy rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700" data-copy-value="{{ $onboarding['next_step']['public_url'] }}">
                                Copy link
                            </button>
                        @endif
                        <button type="button" class="js-start-desktop-tour inline-flex items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                            Start tutorial
                        </button>
                    </div>
                @endif
            </aside>
            <a href="{{ route('onboarding.index') }}" class="fixed bottom-24 right-4 z-30 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 shadow-lg lg:hidden">
                Resume onboarding
            </a>
        @endif
        <div class="js-desktop-tour hidden lg:block">
            <div class="pointer-events-none fixed inset-0 z-[70] bg-slate-900/45 js-desktop-tour-overlay"></div>
            <div class="pointer-events-none fixed z-[71] rounded-xl border-2 border-emerald-300 shadow-[0_0_0_9999px_rgba(2,6,23,0.48)] transition-all duration-200 js-desktop-tour-highlight"></div>
            <section class="fixed z-[72] w-[340px] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl pointer-events-auto js-desktop-tour-card">
                <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400 js-desktop-tour-progress">Tutorial</p>
                <h3 class="mt-2 text-sm font-semibold text-slate-900 js-desktop-tour-title">Welcome</h3>
                <p class="mt-2 text-xs leading-5 text-slate-600 js-desktop-tour-description">Quick walkthrough to learn key actions.</p>
                <div class="mt-4 flex items-center justify-between gap-2">
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 js-desktop-tour-prev">Previous</button>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 js-desktop-tour-skip">Skip</button>
                        <button type="button" class="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white js-desktop-tour-next">Next</button>
                    </div>
                </div>
            </section>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const copyButton = document.querySelector('.js-copy-public-link');
                if (copyButton) {
                    const copyValue = copyButton.dataset.copyValue || '';
                    const setLabel = (text) => {
                        copyButton.textContent = text;
                        setTimeout(() => {
                            copyButton.textContent = 'Copy link';
                        }, 1200);
                    };

                    copyButton.addEventListener('click', async () => {
                        if (!copyValue) {
                            return;
                        }

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            try {
                                await navigator.clipboard.writeText(copyValue);
                                setLabel('Copied');
                                return;
                            } catch (_) {
                                // Fallback below.
                            }
                        }

                        const helper = document.createElement('textarea');
                        helper.value = copyValue;
                        helper.setAttribute('readonly', '');
                        helper.style.position = 'absolute';
                        helper.style.left = '-9999px';
                        document.body.appendChild(helper);
                        helper.select();
                        const ok = document.execCommand('copy');
                        document.body.removeChild(helper);
                        setLabel(ok ? 'Copied' : 'Copy failed');
                    });
                }
                const onboardingPopup = document.querySelector('.js-onboarding-popup');
                const onboardingClose = document.querySelector('.js-onboarding-close');
                const onboardingCopy = document.querySelector('.js-onboarding-copy');
                const onboardingState = @json($onboarding['state'] ?? []);
                const startDesktopTourButtons = document.querySelectorAll('.js-start-desktop-tour');

                const postOnboardingState = (payload) => {
                    return fetch('{{ route('onboarding.state') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    }).catch(() => {});
                };

                if (onboardingPopup) {
                    const dismissed = onboardingPopup.dataset.dismissed === '1';
                    if (!dismissed) {
                        onboardingPopup.classList.remove('hidden');
                    }

                    if (onboardingClose) {
                        onboardingClose.addEventListener('click', () => {
                            onboardingPopup.classList.add('hidden');
                            postOnboardingState({ desktop_popup_dismissed: true });
                        });
                    }

                    if (onboardingCopy) {
                        onboardingCopy.addEventListener('click', async () => {
                            const value = onboardingCopy.dataset.copyValue || '';
                            if (!value) {
                                return;
                            }
                            try {
                                await navigator.clipboard.writeText(value);
                                onboardingCopy.textContent = 'Copied';
                            } catch (_) {
                                onboardingCopy.textContent = 'Copy failed';
                            }
                            setTimeout(() => {
                                onboardingCopy.textContent = 'Copy link';
                            }, 1200);
                        });
                    }
                }

                const desktopTourRoot = document.querySelector('.js-desktop-tour');
                const desktopTourHighlight = document.querySelector('.js-desktop-tour-highlight');
                const desktopTourCard = document.querySelector('.js-desktop-tour-card');
                const desktopTourProgress = document.querySelector('.js-desktop-tour-progress');
                const desktopTourTitle = document.querySelector('.js-desktop-tour-title');
                const desktopTourDescription = document.querySelector('.js-desktop-tour-description');
                const desktopTourPrev = document.querySelector('.js-desktop-tour-prev');
                const desktopTourNext = document.querySelector('.js-desktop-tour-next');
                const desktopTourSkip = document.querySelector('.js-desktop-tour-skip');
                const desktopTourSteps = [
                    {
                        selector: '.js-tour-dashboard-nav',
                        title: 'Dashboard',
                        description: 'Use this to return to your main overview at any time.',
                    },
                    {
                        selector: '.js-tour-products-nav',
                        title: 'Products',
                        description: 'Manage stock, pricing, and sharing actions for your products.',
                    },
                    {
                        selector: '.js-tour-quick-action',
                        title: 'Quick Action',
                        description: 'Open fast actions for orders, invoices, coupons, and stock.',
                    },
                    {
                        selector: '.js-tour-payments-nav',
                        title: 'Payments',
                        description: 'Track payouts and transactions from your payment dashboard.',
                    },
                    {
                        selector: '.js-tour-copy-public-link',
                        title: 'Share Public Link',
                        description: 'Copy your store link and send it to customers instantly.',
                    },
                ].filter((step) => document.querySelector(step.selector));

                let desktopTourIndex = Math.min(
                    Math.max(0, Number(onboardingState.desktop_tour_step || 0)),
                    Math.max(0, desktopTourSteps.length - 1)
                );
                let desktopTourOpen = false;

                const setDesktopTourOpen = (open) => {
                    if (!desktopTourRoot) {
                        return;
                    }
                    desktopTourOpen = Boolean(open);
                    desktopTourRoot.classList.toggle('hidden', !desktopTourOpen);
                };

                const positionDesktopTour = () => {
                    if (!desktopTourOpen || !desktopTourSteps[desktopTourIndex] || !desktopTourHighlight || !desktopTourCard) {
                        return;
                    }

                    const target = document.querySelector(desktopTourSteps[desktopTourIndex].selector);
                    if (!target) {
                        return;
                    }
                    target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                    const rect = target.getBoundingClientRect();
                    const pad = 8;
                    const highlightTop = Math.max(8, rect.top - pad);
                    const highlightLeft = Math.max(8, rect.left - pad);
                    const highlightWidth = Math.min(window.innerWidth - 16 - highlightLeft, rect.width + (pad * 2));
                    const highlightHeight = Math.min(window.innerHeight - 16 - highlightTop, rect.height + (pad * 2));

                    desktopTourHighlight.style.top = `${highlightTop}px`;
                    desktopTourHighlight.style.left = `${highlightLeft}px`;
                    desktopTourHighlight.style.width = `${highlightWidth}px`;
                    desktopTourHighlight.style.height = `${highlightHeight}px`;

                    const cardWidth = Math.min(340, window.innerWidth - 24);
                    desktopTourCard.style.width = `${cardWidth}px`;
                    const cardHeight = desktopTourCard.offsetHeight || 180;
                    const preferBelow = highlightTop + highlightHeight + cardHeight + 16 < window.innerHeight;
                    const top = preferBelow
                        ? highlightTop + highlightHeight + 12
                        : Math.max(12, highlightTop - cardHeight - 12);
                    const left = Math.min(
                        Math.max(12, highlightLeft),
                        Math.max(12, window.innerWidth - cardWidth - 12)
                    );
                    desktopTourCard.style.top = `${top}px`;
                    desktopTourCard.style.left = `${left}px`;
                };

                const renderDesktopTour = () => {
                    if (!desktopTourSteps.length || !desktopTourTitle || !desktopTourDescription || !desktopTourProgress) {
                        return;
                    }
                    const step = desktopTourSteps[desktopTourIndex];
                    desktopTourProgress.textContent = `Tutorial ${desktopTourIndex + 1}/${desktopTourSteps.length}`;
                    desktopTourTitle.textContent = step.title;
                    desktopTourDescription.textContent = step.description;
                    if (desktopTourPrev) {
                        desktopTourPrev.disabled = desktopTourIndex === 0;
                        desktopTourPrev.classList.toggle('opacity-40', desktopTourIndex === 0);
                    }
                    if (desktopTourNext) {
                        desktopTourNext.textContent = desktopTourIndex === desktopTourSteps.length - 1 ? 'Finish' : 'Next';
                    }
                    window.requestAnimationFrame(positionDesktopTour);
                };

                const openDesktopTour = (reset = false) => {
                    if (!desktopTourSteps.length || !desktopTourRoot) {
                        return;
                    }
                    if (reset) {
                        desktopTourIndex = 0;
                    }
                    setDesktopTourOpen(true);
                    postOnboardingState({
                        desktop_tour_dismissed: false,
                        desktop_tour_completed: false,
                        desktop_tour_step: desktopTourIndex,
                    });
                    renderDesktopTour();
                };

                const closeDesktopTour = (dismissed = true) => {
                    setDesktopTourOpen(false);
                    if (dismissed) {
                        postOnboardingState({ desktop_tour_dismissed: true });
                    }
                };

                const completeDesktopTour = () => {
                    setDesktopTourOpen(false);
                    postOnboardingState({
                        desktop_tour_completed: true,
                        desktop_tour_dismissed: true,
                        desktop_tour_step: 0,
                    });
                };

                startDesktopTourButtons.forEach((button) => {
                    button.addEventListener('click', () => openDesktopTour(true));
                });

                if (desktopTourPrev) {
                    desktopTourPrev.addEventListener('click', () => {
                        if (desktopTourIndex <= 0) {
                            return;
                        }
                        desktopTourIndex -= 1;
                        postOnboardingState({ desktop_tour_step: desktopTourIndex });
                        renderDesktopTour();
                    });
                }

                if (desktopTourNext) {
                    desktopTourNext.addEventListener('click', () => {
                        if (desktopTourIndex >= desktopTourSteps.length - 1) {
                            completeDesktopTour();
                            return;
                        }
                        desktopTourIndex += 1;
                        postOnboardingState({ desktop_tour_step: desktopTourIndex });
                        renderDesktopTour();
                    });
                }

                if (desktopTourSkip) {
                    desktopTourSkip.addEventListener('click', () => closeDesktopTour(true));
                }

                window.addEventListener('resize', () => {
                    if (desktopTourOpen) {
                        positionDesktopTour();
                    }
                });

                const shouldAutoStartDesktopTour = window.matchMedia('(min-width: 1024px)').matches
                    && desktopTourSteps.length > 0
                    && !onboardingState.desktop_tour_completed
                    && !onboardingState.desktop_tour_dismissed
                    && {{ $onboarding['is_complete'] ? 'false' : 'true' }};

                if (shouldAutoStartDesktopTour) {
                    openDesktopTour(false);
                }

                const panel = document.querySelector('.js-quick-actions-panel');
                const backdrop = document.querySelector('.js-quick-actions-backdrop');
                const openButtons = document.querySelectorAll('.js-quick-actions-open');
                const closeButtons = document.querySelectorAll('.js-quick-actions-close');
                const themeToggles = document.querySelectorAll('.js-theme-toggle');
                const themeMoonIcons = document.querySelectorAll('.js-theme-icon-moon');
                const themeSunIcons = document.querySelectorAll('.js-theme-icon-sun');
                const mobileMenuPanel = document.querySelector('.js-mobile-menu-panel');
                const mobileMenuBackdrop = document.querySelector('.js-mobile-menu-backdrop');
                const mobileMenuOpenButtons = document.querySelectorAll('.js-mobile-menu-open');
                const mobileMenuCloseButtons = document.querySelectorAll('.js-mobile-menu-close');
                const root = document.documentElement;
                let quickActionsOpen = false;
                let quickDragStartY = 0;
                let quickDragCurrentY = 0;
                let quickDragging = false;
                const applyTheme = (mode) => {
                    const dark = mode === 'dark';
                    root.classList.toggle('theme-dark', dark);
                    themeMoonIcons.forEach((icon) => icon.classList.toggle('hidden', dark));
                    themeSunIcons.forEach((icon) => icon.classList.toggle('hidden', !dark));
                };
                const detectTheme = () => {
                    try {
                        const saved = localStorage.getItem('lp_theme');
                        if (saved === 'dark' || saved === 'light') {
                            return saved;
                        }
                    } catch (_) {}
                    return root.classList.contains('theme-dark') ? 'dark' : 'light';
                };
                let currentTheme = detectTheme();
                applyTheme(currentTheme);
                themeToggles.forEach((button) => {
                    button.addEventListener('click', () => {
                        currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        applyTheme(currentTheme);
                        try {
                            localStorage.setItem('lp_theme', currentTheme);
                        } catch (_) {}
                    });
                });
                const syncBodyLock = () => {
                    const quickOpen = panel && !panel.classList.contains('translate-y-full');
                    const menuOpen = mobileMenuPanel && !mobileMenuPanel.classList.contains('translate-x-full');
                    document.body.classList.toggle('overflow-hidden', Boolean(quickOpen || menuOpen));
                };

                const setQuickActions = (open) => {
                    if (!panel || !backdrop) {
                        return;
                    }

                    quickActionsOpen = open;
                    panel.classList.toggle('translate-y-full', !open);
                    if (!open) {
                        panel.style.transform = '';
                        panel.style.transition = '';
                    }
                    backdrop.classList.toggle('opacity-0', !open);
                    backdrop.classList.toggle('pointer-events-none', !open);
                    syncBodyLock();
                };
                const setMobileMenu = (open) => {
                    if (!mobileMenuPanel || !mobileMenuBackdrop) {
                        return;
                    }

                    mobileMenuPanel.classList.toggle('translate-x-full', !open);
                    mobileMenuBackdrop.classList.toggle('opacity-0', !open);
                    mobileMenuBackdrop.classList.toggle('pointer-events-none', !open);
                    syncBodyLock();
                };

                openButtons.forEach((button) => {
                    button.addEventListener('click', () => setQuickActions(true));
                });
                mobileMenuOpenButtons.forEach((button) => {
                    button.addEventListener('click', () => setMobileMenu(true));
                });

                closeButtons.forEach((button) => {
                    button.addEventListener('click', () => setQuickActions(false));
                });
                mobileMenuCloseButtons.forEach((button) => {
                    button.addEventListener('click', () => setMobileMenu(false));
                });

                if (backdrop) {
                    backdrop.addEventListener('click', () => setQuickActions(false));
                }
                if (mobileMenuBackdrop) {
                    mobileMenuBackdrop.addEventListener('click', () => setMobileMenu(false));
                }

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        setQuickActions(false);
                        setMobileMenu(false);
                        if (desktopTourOpen) {
                            closeDesktopTour(true);
                        }
                    }
                });

                // Mobile touch drag to dismiss quick actions panel.
                if (panel) {
                    panel.addEventListener('touchstart', (event) => {
                        if (!quickActionsOpen || event.touches.length !== 1) {
                            return;
                        }
                        quickDragging = true;
                        quickDragStartY = event.touches[0].clientY;
                        quickDragCurrentY = quickDragStartY;
                        panel.style.transition = 'none';
                    }, { passive: true });

                    panel.addEventListener('touchmove', (event) => {
                        if (!quickDragging || event.touches.length !== 1) {
                            return;
                        }
                        quickDragCurrentY = event.touches[0].clientY;
                        const delta = Math.max(0, quickDragCurrentY - quickDragStartY);
                        panel.style.transform = `translateY(${delta}px)`;
                        if (delta > 0) {
                            event.preventDefault();
                        }
                    }, { passive: false });

                    panel.addEventListener('touchend', () => {
                        if (!quickDragging) {
                            return;
                        }
                        quickDragging = false;
                        const delta = Math.max(0, quickDragCurrentY - quickDragStartY);
                        panel.style.transition = 'transform 220ms ease';
                        if (delta > 90) {
                            setQuickActions(false);
                            return;
                        }
                        panel.style.transform = 'translateY(0)';
                        window.setTimeout(() => {
                            if (quickActionsOpen) {
                                panel.style.transform = '';
                                panel.style.transition = '';
                            }
                        }, 230);
                    });

                    panel.addEventListener('touchcancel', () => {
                        quickDragging = false;
                        if (!quickActionsOpen) {
                            panel.style.transform = '';
                            panel.style.transition = '';
                            return;
                        }
                        panel.style.transition = 'transform 220ms ease';
                        panel.style.transform = 'translateY(0)';
                        window.setTimeout(() => {
                            if (quickActionsOpen) {
                                panel.style.transform = '';
                                panel.style.transition = '';
                            }
                        }, 230);
                    });
                }
            });
        </script>
    </body>
</html>
