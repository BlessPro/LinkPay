<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '8Kommerce') }} Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans bg-gradient-to-br from-slate-50 via-white to-slate-100 text-slate-900">
        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white">
                <div class="flex items-center gap-2 px-6 py-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white font-semibold">
                        AD
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-widest text-slate-400">Admin</p>
                        <p class="text-base font-semibold text-slate-900">System view</p>
                    </div>
                </div>
                <nav class="flex-1 space-y-1 px-4">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold bg-slate-900 text-white' : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-900 hover:text-white' }}">Overview</a>
                    <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices.*') ? 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold bg-slate-900 text-white' : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-900 hover:text-white' }}">Invoices</a>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">Seller dashboard</a>
                </nav>
                <div class="px-6 py-5">
                    <p class="text-xs uppercase tracking-widest text-slate-400">Signed in</p>
                    <p class="mt-2 text-sm text-slate-700">{{ auth()->user()->email }}</p>
                </div>
            </aside>

            <div class="flex-1">
                <header class="border-b border-slate-200 bg-white/70 backdrop-blur">
                    <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-400">Admin</p>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Overview' }}</h1>
                        </div>
                        <div class="hidden items-center gap-3 sm:flex">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:border-slate-300">Logout</button>
                            </form>
                        </div>
                        <details class="relative sm:hidden">
                            <summary class="cursor-pointer rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">Menu</summary>
                            <div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                                <nav class="space-y-2 text-sm">
                                    <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-900 hover:text-white">Overview</a>
                                    <a href="{{ route('admin.invoices.index') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-900 hover:text-white">Invoices</a>
                                    <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">Seller dashboard</a>
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
