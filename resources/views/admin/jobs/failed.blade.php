@php($title = 'Failed Jobs')
@extends('layouts.admin')

@section('content')
    @if(session('status') === 'failed-job-retried')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Failed job retried successfully.
        </div>
    @endif
    @if(session('status') === 'failed-job-forgotten')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            Failed job removed from failed queue.
        </div>
    @endif
    @if(session('status') === 'failed-jobs-retried-all')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Triggered retry for all failed jobs.
        </div>
    @endif
    @if($errors->has('failed_jobs'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first('failed_jobs') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Failed jobs</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $failedJobsTotal }}</p>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-rose-700">Failed (24h)</p>
            <p class="mt-3 text-2xl font-semibold text-rose-900">{{ $failedJobs24h }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Operations</p>
            <form method="POST" action="{{ route('admin.jobs.failed.retry-all') }}" class="mt-3">
                @csrf
                <button type="submit" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                    Retry all failed jobs
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Failed queue entries</h2>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Latest</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($failedJobs as $job)
                <article class="rounded-xl border border-slate-100 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $job->uuid }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $job->failed_at }}</p>
                            <p class="mt-2 text-xs text-rose-700">{{ \Illuminate\Support\Str::limit($job->exception, 360) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.jobs.failed.retry', $job->id) }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                    Retry
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.jobs.failed.forget', $job->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:border-rose-300">
                                    Forget
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">No failed jobs in queue.</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $failedJobs->links() }}
        </div>
    </div>
@endsection
