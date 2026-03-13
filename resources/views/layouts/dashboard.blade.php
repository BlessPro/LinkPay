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
                    <a href="{{ route('profile.edit') }}" class="{{ $navClass('profile.*') }}">Profile</a>
                    <a href="{{ route('products.index') }}" class="{{ $navClass('products.*') }}">Products</a>
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
                                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Profile</a>
                                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Products</a>
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

                <main class="px-4 py-6 pt-24 sm:px-6 lg:px-8">
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
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const copyButton = document.querySelector('.js-copy-public-link');
                if (!copyButton) {
                    return;
                }

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
            });
        </script>
    </body>
</html>
