@php($title = 'Notifications')
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Notifications</h2>
        <div class="mt-4 space-y-3">
            @forelse($notifications as $notification)
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->format('M d, Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No notifications yet.</p>
            @endforelse
        </div>
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
