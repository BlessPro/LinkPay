@php
    $title = 'Order feedback';
@endphp
@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Order confirmation</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Delivery feedback</h1>

            @if(session('status') === 'feedback-received')
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Thanks. Your order has been marked as delivered.
                </div>
            @endif
            @if(session('status') === 'feedback-reported')
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    We received your report. Admin will review this appeal.
                </div>
            @endif

            @if($expired)
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    This link is expired or already used.
                </div>
            @else
                <p class="mt-3 text-sm text-slate-600">
                    Order: <span class="font-semibold text-slate-900">{{ $order?->reference }}</span>
                </p>
                <p class="mt-1 text-sm text-slate-600">Have you received your product?</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <button type="button" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500 js-feedback-yes">
                        Yes, I received it
                    </button>
                    <button type="button" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100 js-feedback-report">
                        Report an issue
                    </button>
                </div>

                <form method="POST" action="{{ route('public.order.feedback.received', $token->token) }}" class="mt-5 hidden space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 js-feedback-yes-form">
                    @csrf
                    <p class="text-sm font-semibold text-emerald-800">Rate this seller</p>
                    <div class="flex items-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="inline-flex items-center gap-1 text-sm text-slate-700">
                                <input type="radio" name="rating" value="{{ $i }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                {{ $i }}★
                            </label>
                        @endfor
                    </div>
                    <textarea name="note" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Any notes (optional)"></textarea>
                    <button type="submit" class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                        Submit confirmation
                    </button>
                </form>

                <form method="POST" action="{{ route('public.order.feedback.report', $token->token) }}" enctype="multipart/form-data" class="mt-5 hidden space-y-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-4 js-feedback-report-form">
                    @csrf
                    <p class="text-sm font-semibold text-amber-900">Report an issue</p>
                    <textarea name="issue_note" rows="4" required class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-400 focus:ring-amber-400" placeholder="Describe the issue"></textarea>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Attach photo (optional)</label>
                        <input type="file" name="issue_photo" accept="image/*" class="block w-full text-xs text-slate-600" />
                    </div>
                    <button type="submit" class="rounded-full bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-500">
                        Submit report
                    </button>
                </form>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const yesBtn = document.querySelector('.js-feedback-yes');
            const reportBtn = document.querySelector('.js-feedback-report');
            const yesForm = document.querySelector('.js-feedback-yes-form');
            const reportForm = document.querySelector('.js-feedback-report-form');

            yesBtn?.addEventListener('click', () => {
                yesForm?.classList.remove('hidden');
                reportForm?.classList.add('hidden');
            });
            reportBtn?.addEventListener('click', () => {
                reportForm?.classList.remove('hidden');
                yesForm?.classList.add('hidden');
            });
        });
    </script>
@endsection

