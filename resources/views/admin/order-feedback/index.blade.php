@php
    $title = 'Order Feedback';
@endphp
@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        @if(session('status') === 'order-feedback-refund-approved')
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Refund approved successfully.
            </div>
        @endif
        @if(session('status') === 'order-feedback-ignored')
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Complaint marked as invalid.
            </div>
        @endif
        @if($errors->has('feedback'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('feedback') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Reported delivery issues</h2>
            <p class="mt-1 text-sm text-slate-500">Approve refund ({{ number_format($refundPercent * 100, 0) }}%) or ignore complaint.</p>

            <div class="mt-4 space-y-4">
                @forelse($feedbacks as $feedback)
                    @php
                        $order = $feedback->order;
                        $seller = $order?->user?->sellerProfile?->business_name ?? $order?->user?->name ?? 'Seller';
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $order?->reference }}</p>
                                <p class="text-xs text-slate-500">{{ $seller }} · {{ $order?->customer_name }} · {{ $order?->customer_phone }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $feedback->admin_status === \App\Models\OrderFeedback::ADMIN_PENDING ? 'bg-amber-100 text-amber-700' : ($feedback->admin_status === \App\Models\OrderFeedback::ADMIN_REFUND_APPROVED ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ str_replace('_', ' ', $feedback->admin_status) }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">{{ $feedback->issue_note }}</p>
                        @if($feedback->issue_photo_path)
                            <a href="{{ asset('storage/'.$feedback->issue_photo_path) }}" target="_blank" rel="noreferrer noopener" class="mt-2 inline-flex text-xs font-semibold text-emerald-700 hover:text-emerald-600">
                                View attached photo
                            </a>
                        @endif
                        @if($feedback->admin_note)
                            <p class="mt-2 text-xs text-slate-500">Admin note: {{ $feedback->admin_note }}</p>
                        @endif

                        @if($feedback->admin_status === \App\Models\OrderFeedback::ADMIN_PENDING)
                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                <form method="POST" action="{{ route('admin.order-feedback.refund', $feedback) }}" class="space-y-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                                    @csrf
                                    <label class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Refund note</label>
                                    <textarea name="admin_note" rows="2" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Optional note to seller/customer"></textarea>
                                    <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                        Approve {{ number_format($refundPercent * 100, 0) }}% refund
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.order-feedback.ignore', $feedback) }}" class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                    @csrf
                                    <label class="text-xs font-semibold uppercase tracking-wide text-amber-800">Reason (required)</label>
                                    <textarea name="admin_note" rows="2" required class="w-full rounded-lg border-slate-200 text-sm" placeholder="Why complaint is invalid"></textarea>
                                    <button type="submit" class="rounded-full bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500">
                                        Ignore complaint
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No reported issues yet.</p>
                @endforelse
            </div>
        </div>

        <div>
            {{ $feedbacks->links() }}
        </div>
    </div>
@endsection

