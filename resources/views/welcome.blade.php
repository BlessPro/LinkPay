@extends('layouts.public')

@section('content')
    <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-emerald-100 bg-white/80 p-8 shadow-sm">
            <p class="text-xs uppercase tracking-[0.35em] text-emerald-500">WhatsApp-first payments</p>
            <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl">
                Get paid faster with shareable invoice links.
            </h1>
            <p class="mt-4 text-base text-slate-600">
                LinkPay lets sellers create mini storefronts and smart invoices that are ready for WhatsApp sharing.
                Accept full or partial payments while we route funds to your Paystack subaccount automatically.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('register') }}" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                    Create seller account
                </a>
                <a href="{{ route('login') }}" class="rounded-full border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Sign in
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
                    LinkPay creates and manages subaccounts for every seller, with platform fees automatically applied.
                </p>
            </div>
        </div>
    </section>
@endsection
