<section class="space-y-4">
    <header>
        <h3 class="text-base font-semibold text-slate-900">Request Data Deletion</h3>
        <p class="mt-1 text-sm text-slate-600">
            Submit a formal deletion request. This creates a compliance record before permanent deletion processing.
        </p>
    </header>

    @if (session('status') === 'data-deletion-requested')
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Data deletion request submitted.
        </div>
    @endif

    <form method="post" action="{{ route('legal.data-deletion.store') }}" class="space-y-3">
        @csrf
        <div>
            <label for="deletion_note" class="text-sm font-medium text-slate-700">Reason (optional)</label>
            <textarea
                id="deletion_note"
                name="note"
                rows="3"
                class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500"
                placeholder="Tell us why you want your data deleted."
            >{{ old('note') }}</textarea>
            @error('note')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
            Submit deletion request
        </button>
    </form>
</section>

