<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'LinkPay') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans bg-gradient-to-br from-amber-50 via-white to-emerald-50 text-slate-900">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">LP</div>
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">LinkPay</p>
                    <p class="text-base font-semibold text-slate-900">Pay by WhatsApp</p>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-600">
                <a href="{{ route('login') }}" class="rounded-full border border-slate-200 px-4 py-2 hover:border-emerald-200 hover:text-emerald-700">Seller login</a>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 pb-16">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </body>
</html>
