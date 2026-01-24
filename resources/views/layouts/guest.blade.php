<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-100 via-white to-slate-100"></div>
            <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-emerald-200/60 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-slate-200/70 blur-3xl"></div>

            <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
                <div class="w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white/80 shadow-2xl backdrop-blur">
                    <div class="grid lg:grid-cols-5">
                        <div class="hidden flex-col justify-between bg-emerald-600 px-10 py-12 text-white lg:flex lg:col-span-2">
                            <div>
                                <a href="/" class="inline-flex items-center gap-3 text-white">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-base font-semibold">LP</span>
                                    <span class="text-xs uppercase tracking-[0.4em]">LinkPay</span>
                                </a>
                                <h2 class="mt-10 text-2xl font-semibold leading-tight">Sell and get paid directly from WhatsApp.</h2>
                                <p class="mt-4 text-sm text-emerald-50/90">
                                    Create mini listings, share invoices, and track payments in one clean workspace.
                                </p>
                            </div>
                            <div class="space-y-3 text-sm text-emerald-50/90">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-white"></span>
                                    <span>Instant Paystack checkout</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-white"></span>
                                    <span>Smart invoice tracking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-white"></span>
                                    <span>Mobile-first dashboards</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-8 sm:p-10 lg:col-span-3">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
