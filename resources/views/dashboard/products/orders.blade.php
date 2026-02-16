@php
    $title = 'Manage Orders';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Manage orders</h2>
            <p class="text-sm text-slate-600">Track customer orders, contact buyers, and review order items.</p>
        </div>
        <a href="{{ route('products.index') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
            Back to products
        </a>
    </div>

    @include('dashboard.products.partials.orders-by-customer')
@endsection
