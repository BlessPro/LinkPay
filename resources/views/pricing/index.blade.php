@php
    $title = 'Pricing';
    $starter = $plans['starter'] ?? [];
    $growth = $plans['growth'] ?? [];
    $enterprise = $plans['enterprise'] ?? [];
@endphp

@extends('layouts.public')

@section('content')
    <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
        <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Pricing</p>
        <h1 class="mt-3 text-4xl font-semibold text-slate-900">Simple subscription plans</h1>
        <p class="mt-3 max-w-3xl text-base text-slate-600">
            Choose the plan that matches your business stage. Subscriptions are monthly and billed from Mobile Money.
        </p>

        <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">Included in every plan</p>
            <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                <p>Unlimited orders</p>
                <p>Storefront link</p>
                <p>Checkout and payments</p>
                <p>Invoice payments</p>
                <p>WhatsApp sharing and chat</p>
                <p>No setup fee</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <article class="rounded-3xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $starter['name'] ?? 'Starter' }}</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Best for new sellers</span>
                </div>
                <p class="mt-5 text-3xl font-semibold text-slate-900">{{ $starter['price_text'] ?? 'GHS 15 / month' }}</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-600">
                    <li>Up to {{ data_get($starter, 'limits.products', 100) }} products</li>
                    <li>{{ data_get($starter, 'limits.orders', 'Unlimited') }} orders</li>
                    <li>Basic dashboard</li>
                    <li>{{ data_get($starter, 'limits.admins', 1) }} admin user</li>
                    <li>Transaction fee: {{ data_get($starter, 'transaction_fee', '1.9%') }}</li>
                </ul>
                <a href="{{ route('billing.show') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Choose Starter
                </a>
            </article>

            <article class="rounded-3xl border border-emerald-200 bg-emerald-50/40 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $growth['name'] ?? 'Growth' }}</h2>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Most popular</span>
                </div>
                <p class="mt-5 text-3xl font-semibold text-slate-900">{{ $growth['price_text'] ?? 'GHS 30 / month' }}</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-600">
                    <li>Up to {{ data_get($growth, 'limits.products', 300) }} products</li>
                    <li>{{ data_get($growth, 'limits.orders', 'Unlimited') }} orders</li>
                    <li>Improved dashboard</li>
                    <li>Faster support</li>
                    <li>Transaction fee: {{ data_get($growth, 'transaction_fee', '1.25%') }}</li>
                </ul>
                <a href="{{ route('billing.show') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Choose Growth
                </a>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $enterprise['name'] ?? 'Enterprise' }}</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">For bigger operations</span>
                </div>
                <p class="mt-5 text-3xl font-semibold text-slate-900">{{ $enterprise['price_text'] ?? 'GHS 70 / month' }}</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-600">
                    <li>{{ data_get($enterprise, 'limits.products', 'Unlimited') }} products</li>
                    <li>{{ data_get($enterprise, 'limits.orders', 'Unlimited') }} orders</li>
                    <li>Advanced dashboard</li>
                    <li>Team access up to {{ data_get($enterprise, 'limits.admins', 10) }} members</li>
                    <li>Priority support</li>
                    <li>Transaction fee: {{ data_get($enterprise, 'transaction_fee', '1%') }}</li>
                </ul>
                <a href="{{ route('billing.show') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Choose Enterprise
                </a>
            </article>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900">What all plans include</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-sm font-semibold text-slate-900">Public storefront</p>
                <p class="mt-1 text-xs text-slate-500">Share products with direct links.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-sm font-semibold text-slate-900">Checkout + payments</p>
                <p class="mt-1 text-xs text-slate-500">Take orders and payments online.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-sm font-semibold text-slate-900">Order management</p>
                <p class="mt-1 text-xs text-slate-500">Track and process customer orders.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-sm font-semibold text-slate-900">Customer tools</p>
                <p class="mt-1 text-xs text-slate-500">Manage customer records and activity.</p>
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-slate-900">Plan comparison</h2>
            <span class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Monthly</span>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="grid grid-cols-4 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 sm:px-6">
                <div>Feature</div>
                <div class="text-center">Starter</div>
                <div class="text-center">Growth</div>
                <div class="text-center">Enterprise</div>
            </div>

            @php
                $compareRows = [
                    ['Price', 'GHS 15', 'GHS 30', 'GHS 70'],
                    ['Best for', data_get($starter, 'best_for', 'Individuals starting out'), data_get($growth, 'best_for', 'Growing sellers'), data_get($enterprise, 'best_for', 'Businesses & teams')],
                    ['Product limit', '100', '300', 'Unlimited'],
                    ['Order limit', 'Unlimited', 'Unlimited', 'Unlimited'],
                    ['Storefront link', 'Yes', 'Yes', 'Yes'],
                    ['Checkout and payments', 'Yes', 'Yes', 'Yes'],
                    ['Invoice payments', 'Yes', 'Yes', 'Yes'],
                    ['WhatsApp sharing and chat', 'Yes', 'Yes', 'Yes'],
                    ['Dashboard', 'Basic', 'Improved', 'Advanced'],
                    ['Support', 'Standard', 'Faster', 'Priority'],
                    ['Team members', 'No', 'No', 'Up to 10'],
                    ['Transaction fee', data_get($starter, 'transaction_fee', '1.9%'), data_get($growth, 'transaction_fee', '1.25%'), data_get($enterprise, 'transaction_fee', '1%')],
                ];
            @endphp

            <div class="divide-y divide-slate-100 bg-white">
                @foreach($compareRows as $row)
                    <div class="grid grid-cols-4 items-center px-4 py-3 text-sm sm:px-6">
                        <div class="font-medium text-slate-700">{{ $row[0] }}</div>
                        <div class="text-center text-slate-600">{{ $row[1] }}</div>
                        <div class="text-center text-slate-600">{{ $row[2] }}</div>
                        <div class="text-center text-slate-600">{{ $row[3] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Transaction fee is charged per successful payment. Processor charges may apply where applicable.
        </p>
    </section>
@endsection
