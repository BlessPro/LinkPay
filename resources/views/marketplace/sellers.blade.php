<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>8Kommerce - Sellers Directory</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
          },
          boxShadow: {
            soft: '0 10px 30px rgba(15, 23, 42, 0.08)',
            card: '0 18px 46px rgba(15, 23, 42, 0.12)'
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[#eef2f7] font-sans text-slate-900">
  <div class="mx-auto max-w-[1500px] px-4 py-5 md:px-6 md:py-6">
    <header class="sticky top-3 z-20 rounded-2xl border border-slate-200/70 bg-white/90 px-5 py-4 shadow-soft backdrop-blur md:px-6">
      <div class="flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-lg font-extrabold text-white">8K</div>
          <div>
            <p class="text-lg font-semibold leading-none">8Kommerce</p>
            <p class="text-xs text-slate-500">Sellers Directory</p>
          </div>
        </a>
        <a href="{{ url('/') }}" class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Go Home</a>
      </div>
    </header>

    <main class="mt-6 rounded-3xl bg-white p-5 shadow-card md:p-7">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Marketplace</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight md:text-4xl">All Sellers</h1>
          <p class="mt-2 text-sm text-slate-600">Sorted alphabetically by default. Filter by category, search by name, or rank by performance.</p>
        </div>
      </div>

      @php
        $baseQuery = [
          'q' => $search,
          'sort' => $sort,
        ];
      @endphp

      <div class="mt-6 overflow-x-auto">
        <div class="inline-flex min-w-full items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
          @php
            $allQuery = array_filter($baseQuery, fn ($value) => $value !== '' && $value !== null);
          @endphp
          <a
            href="{{ route('marketplace.sellers', $allQuery) }}"
            class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold {{ $selectedCategory === '' ? 'bg-slate-950 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
          >
            All
          </a>
          @foreach($categories as $category)
            @php
              $categoryQuery = array_filter(array_merge($baseQuery, ['category' => $category]), fn ($value) => $value !== '' && $value !== null);
            @endphp
            <a
              href="{{ route('marketplace.sellers', $categoryQuery) }}"
              class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold {{ $selectedCategory === $category ? 'bg-slate-950 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
            >
              {{ $category }}
            </a>
          @endforeach
        </div>
      </div>

      <form method="GET" action="{{ route('marketplace.sellers') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1.25fr_0.9fr_0.7fr_auto] md:items-end">
        <div>
          <label for="q" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
          <input id="q" name="q" value="{{ $search }}" type="text" placeholder="Business name or category" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-emerald-600">
        </div>
        <div>
          <label for="category" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
          <select id="category" name="category" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-emerald-600">
            <option value="">All categories</option>
            @foreach($categories as $category)
              <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="sort" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort</label>
          <select id="sort" name="sort" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-emerald-600">
            <option value="alpha" @selected($sort === 'alpha')>Alphabetical (A-Z)</option>
            <option value="performance" @selected($sort === 'performance')>Top Performance</option>
          </select>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Apply</button>
          <a href="{{ route('marketplace.sellers') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Reset</a>
        </div>
      </form>

      <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($sellers as $seller)
          @php
            $bgPool = ['bg-[#f5ebe8]', 'bg-[#f5eef4]', 'bg-[#eef5f8]', 'bg-[#edf4f1]'];
            $colorPool = ['from-[#8a5237] to-[#2d1d17]', 'from-[#d6bead] to-[#ad8d7a]', 'from-[#d7dfe7] to-[#8996a7]', 'from-[#111827] to-[#475569]'];
            $idx = $loop->index % 4;
          @endphp
          <article class="group overflow-hidden rounded-[1.75rem] {{ $bgPool[$idx] }} p-4 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
            <div class="flex h-52 items-end rounded-[1.35rem] bg-gradient-to-br {{ $colorPool[$idx] }} p-4 text-white">
              <div class="w-full rounded-full bg-white/20 px-4 py-2 text-sm backdrop-blur">{{ $seller['business_name'] }}</div>
            </div>
            <div class="mt-4">
              <p class="text-base font-semibold">{{ $seller['business_name'] }}</p>
              <p class="mt-1 text-sm text-slate-600">{{ $seller['category'] }}</p>
              <p class="mt-2 text-xs text-slate-500">{{ $seller['product_count'] }} products • {{ number_format($seller['rating'], 1) }} rating</p>
              <a href="{{ route('public.listing', $seller['slug']) }}" class="mt-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-soft">Open Seller</a>
            </div>
          </article>
        @empty
          <div class="col-span-full rounded-2xl border border-slate-200 bg-slate-50 p-10 text-center text-sm text-slate-600">
            No sellers matched your filters.
          </div>
        @endforelse
      </div>

      @if($sellers->hasPages())
        <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
          {{ $sellers->onEachSide(1)->links() }}
        </div>
      @endif
    </main>
  </div>
</body>
</html>
