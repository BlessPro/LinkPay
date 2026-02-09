@php
    $title = 'Pricing';
    $payments = $plans['payments'] ?? [];
    $promotion = $plans['promotion'] ?? [];
@endphp

@extends('layouts.public')

@section('content')
    <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
        <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Pricing</p>
        <h1 class="mt-3 text-4xl font-semibold text-slate-900">Simple plans for WhatsApp selling</h1>
        <p class="mt-3 max-w-2xl text-base text-slate-600">
            Trial includes all features for 7 days. After that, pick the plan that matches how you want to use 8Kommerce.
        </p>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Most popular</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">Everything, including Pay Now + invoices.</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">{{ $payments['price_range'] ?? 'GHS 30-50 / month' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $payments['commission_text'] ?? '1% per successful payment' }}</p>
                <form method="POST" action="{{ route('billing.activate', ['plan' => 'PAYMENTS']) }}" class="mt-6">
                    @csrf
                    <button class="w-full rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Choose Payments (MVP)
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Promotion</h2>
                <p class="mt-2 text-sm text-slate-600">Public pages + products. No Pay Now.</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">{{ $promotion['price_range'] ?? 'GHS 60-100 / month' }}</p>
                <p class="mt-1 text-sm text-slate-500">Higher subscription because payments are not processed here.</p>
                <form method="POST" action="{{ route('billing.activate', ['plan' => 'PROMOTION']) }}" class="mt-6">
                    @csrf
                    <button class="w-full rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                        Choose Promotion (MVP)
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Enterprise</h2>
                <p class="mt-2 text-sm text-slate-600">Teams + advanced analytics (coming later).</p>
                <p class="mt-5 text-2xl font-semibold text-slate-900">Contact sales</p>
                <a href="mailto:sales@example.com" class="mt-6 inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Contact sales
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
        <div class="max-w-3xl">
            <h2 class="text-2xl font-semibold text-slate-900">Why two paid plans?</h2>
            <p class="mt-3 text-sm text-slate-600">
                If you process payments through 8Kommerce, you pay a lower subscription and a small commission per successful payment.
                If you only want promotion (no payments), you pay a higher subscription because 8Kommerce earns no commission.
            </p>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200">
            <div class="grid grid-cols-3 bg-slate-50 px-6 py-3 text-xs font-semibold text-slate-500">
                <div>Feature</div>
                <div class="text-center">Promotion</div>
                <div class="text-center">Payments</div>
            </div>

            @php
                $rows = [
                    ['Public page', true, true],
                    ['Unlimited products/services', true, true],
                    ['Product link previews', true, true],
                    ['WhatsApp chat button', true, true],
                    ['Analytics-lite (views/clicks)', true, true],
                    ['Pay Now buttons', false, true],
                    ['Invoice links', false, true],
                    ['Partial payments', false, true],
                    ['Payment records/notifications', false, true],
                    ['Teams/Advanced analytics', false, false],
                ];
            @endphp

            <div class="divide-y divide-slate-100 bg-white">
                @foreach($rows as $row)
                    <div class="grid grid-cols-3 items-center px-6 py-4 text-sm">
                        <div class="text-slate-700">{{ $row[0] }}</div>
                        <div class="text-center">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row[1] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $row[1] ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="text-center">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row[2] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $row[2] ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Note: Enterprise features are shown for roadmap visibility and are not selectable in MVP checkout.
        </p>
    </section>
@endsection
