<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '8Kommerce') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans bg-gradient-to-br from-slate-50 via-white to-emerald-50 text-slate-900">
        @php
            $user = auth()->user();
            $profile = $user->sellerProfile;
            $canPromotion = $user->canUsePromotionFeatures();
            $canUsePayments = $user->canUsePaymentsFeature();
            $publicUrl = $profile?->public_slug ? route('public.listing', $profile->public_slug) : null;
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
                    <a href="{{ route('dashboard') }}" class="{{ $navClass('dashboard') }}">Dashboard</a>
                    <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 js-quick-actions-open">Quick Action</button>
                    <a href="{{ route('profile.edit') }}" class="{{ $navClass('profile.*') }}">Profile</a>
                    <a href="{{ route('products.index') }}" class="{{ $navClass('products.*') }}">Products</a>
                    <a href="{{ route('coupons.index') }}" class="{{ $navClass('coupons.*') }}">Coupons</a>
                    <a href="{{ route('customers.index') }}" class="{{ $navClass('customers.*') }}">Customers</a>
                    @if($canUsePayments)
                        <a href="{{ route('invoices.index') }}" class="{{ $navClass('invoices.*') }}">Invoices</a>
                        <a href="{{ route('payments.index') }}" class="{{ $navClass('payments.*') }}">Payments</a>
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
                            <p class="text-xs uppercase tracking-widest text-slate-400">Dashboard</p>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Overview' }}</h1>
                        </div>
                        <div class="hidden items-center sm:flex">
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
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
                        <details class="relative sm:hidden">
                            <summary class="cursor-pointer rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">Menu</summary>
                            <div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
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
                            </div>
                        </details>
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
                                            class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700 js-copy-public-link"
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
            </div>
        </section>
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

                const panel = document.querySelector('.js-quick-actions-panel');
                const backdrop = document.querySelector('.js-quick-actions-backdrop');
                const openButtons = document.querySelectorAll('.js-quick-actions-open');
                const closeButtons = document.querySelectorAll('.js-quick-actions-close');

                const setQuickActions = (open) => {
                    if (!panel || !backdrop) {
                        return;
                    }

                    panel.classList.toggle('translate-y-full', !open);
                    backdrop.classList.toggle('opacity-0', !open);
                    backdrop.classList.toggle('pointer-events-none', !open);
                    document.body.classList.toggle('overflow-hidden', open);
                };

                openButtons.forEach((button) => {
                    button.addEventListener('click', () => setQuickActions(true));
                });

                closeButtons.forEach((button) => {
                    button.addEventListener('click', () => setQuickActions(false));
                });

                if (backdrop) {
                    backdrop.addEventListener('click', () => setQuickActions(false));
                }

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        setQuickActions(false);
                    }
                });
            });
        </script>
    </body>
</html>
