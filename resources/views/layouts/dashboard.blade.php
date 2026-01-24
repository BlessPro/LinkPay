<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LinkPay') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans bg-gradient-to-br from-slate-50 via-white to-emerald-50 text-slate-900">
        @php
            $profile = auth()->user()->sellerProfile;
            $navClass = function (string $route) {
                return request()->routeIs($route)
                    ? 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold bg-emerald-50 text-emerald-700'
                    : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700';
            };
        @endphp

        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white">
                <div class="flex items-center gap-2 px-6 py-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">
                        LP
                    </div>
                    <div>
                        <p class="text-sm uppercase tracking-widest text-slate-400">LinkPay</p>
                        <p class="text-base font-semibold text-slate-900">{{ $profile?->business_name ?? 'Seller' }}</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 px-4">
                    <a href="{{ route('dashboard') }}" class="{{ $navClass('dashboard') }}">Dashboard</a>
                    <a href="{{ route('profile.edit') }}" class="{{ $navClass('profile.*') }}">Profile</a>
                    <a href="{{ route('products.index') }}" class="{{ $navClass('products.*') }}">Products</a>
                    <a href="{{ route('invoices.index') }}" class="{{ $navClass('invoices.*') }}">Invoices</a>
                    <a href="{{ route('payments.index') }}" class="{{ $navClass('payments.*') }}">Payments</a>
                    <a href="{{ route('notifications.index') }}" class="{{ $navClass('notifications.*') }}">Notifications</a>
                    <a href="{{ route('insights.index') }}" class="{{ $navClass('insights.*') }}">Insights</a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="{{ $navClass('admin.*') }}">Admin</a>
                    @endif
                    @if($profile)
                        <a href="{{ route('public.listing', $profile->public_slug) }}" class="{{ $navClass('public.listing') }}">Public page</a>
                    @endif
                </nav>
                
                <div class="px-6 py-5">
                    <p class="text-xs uppercase tracking-widest text-slate-400">Payout status</p>
                    @if($profile?->paystack_subaccount_code)
                        <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Paystack Connected</p>
                    @else
                        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">Not Connected</p>
                    @endif
                </div>
            </aside>

            <div class="flex-1">
                <header class="border-b border-slate-200 bg-white/70 backdrop-blur">
                    <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-400">Dashboard</p>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Overview' }}</h1>
                        </div>
                        <div class="hidden items-center gap-3 sm:flex">
                            <span class="text-sm text-slate-600">{{ auth()->user()->email }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:border-emerald-200 hover:text-emerald-700">Logout</button>
                            </form>
                        </div>
                        <details class="relative sm:hidden">
                            <summary class="cursor-pointer rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">Menu</summary>
                            <div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                                <nav class="space-y-2 text-sm">
                                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Dashboard</a>
                                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Profile</a>
                                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Products</a>
                                    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Invoices</a>
                                    <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Payments</a>
                                    <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Notifications</a>
                                    <a href="{{ route('insights.index') }}" class="{{ request()->routeIs('insights.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Insights</a>
                                    @if(auth()->user()->is_admin)
                                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Admin</a>
                                    @endif
                                    @if($profile)
                                        <a href="{{ route('public.listing', $profile->public_slug) }}" class="{{ request()->routeIs('public.listing') ? 'block rounded-lg px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold' : 'block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">Public page</a>
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

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
