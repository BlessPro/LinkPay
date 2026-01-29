@php($title = 'Public Page Preview')
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Public page preview</h2>
                <p class="text-sm text-slate-500">Switch templates to preview how customers will see your page.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm">
                    <a href="{{ route('public.preview', ['template' => 'products']) }}" class="{{ $template === 'products' ? 'rounded-full bg-emerald-50 px-4 py-2 font-semibold text-emerald-700' : 'rounded-full px-4 py-2 font-semibold text-slate-500 hover:text-emerald-700' }}">
                        Products
                    </a>
                    <a href="{{ route('public.preview', ['template' => 'services']) }}" class="{{ $template === 'services' ? 'rounded-full bg-emerald-50 px-4 py-2 font-semibold text-emerald-700' : 'rounded-full px-4 py-2 font-semibold text-slate-500 hover:text-emerald-700' }}">
                        Services
                    </a>
                </div>
                <a href="{{ route('public.listing', $profile->public_slug) }}" target="_blank" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    View live page
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6">
        @include('public.partials.listing-content')
    </div>
@endsection
