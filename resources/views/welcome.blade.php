<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>8Kommerce - Sell with a Link</title>
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
<body class="min-h-screen bg-[#cfd6df] font-sans text-slate-900">
  <main class="px-4 py-4 md:px-6 md:py-6">
    <div class="mx-auto max-w-[1500px] overflow-hidden rounded-[2rem] bg-[#f8f6f4] shadow-card">
      <section class="relative overflow-hidden bg-gradient-to-br from-[#f6f8fb] via-[#f8f5f1] to-[#f4e9ea] px-5 pb-14 pt-6 md:px-8 lg:px-12 lg:pb-20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,183,0.20),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(147,197,253,0.18),transparent_35%)]"></div>

        <header class="relative z-20 flex items-center justify-between gap-4">
          <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-lg font-extrabold text-white">8K</div>
            <div>
              <p class="text-lg font-semibold leading-none">8Kommerce</p>
              <p class="text-xs text-slate-500">Pay by WhatsApp</p>
            </div>
          </a>

          <nav class="hidden rounded-full bg-white/90 px-2 py-2 shadow-soft backdrop-blur md:flex md:items-center md:gap-1">
            <a class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" href="#">Home</a>
            <a class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" href="{{ route('marketplace.sellers') }}">Sellers</a>
            <a class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" href="#products">Products</a>
            <a class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" href="#how-it-works">How it Works</a>
            <a class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" href="#faq">FAQ</a>
          </nav>

          <div class="flex items-center gap-2 md:gap-3">
            <a href="{{ route('pricing') }}" class="hidden rounded-full bg-white px-5 py-3 text-sm font-medium shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:inline-flex">Pricing</a>
            <a href="{{ route('login') }}" class="flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-soft" aria-label="Login">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg>
            </a>
          </div>
        </header>

        <div class="relative z-10 mt-10 text-center">
          <h1 class="mx-auto max-w-4xl text-balance text-4xl font-semibold tracking-tight text-slate-950 md:text-6xl lg:text-7xl">Stylish storefronts for modern sellers</h1>
          <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 text-slate-600 md:text-base">8Kommerce helps sellers upload products, share a single store link, get paid, and grow with real performance insights.</p>
          <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="rounded-full bg-slate-950 px-8 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800">Start Selling</a>
            <a href="{{ route('marketplace.sellers') }}" class="rounded-full border border-slate-300 bg-white px-8 py-3.5 text-sm font-semibold text-slate-800 transition hover:shadow-soft">Discover Sellers</a>
          </div>
        </div>

        <div class="relative z-10 mt-12 grid min-h-[420px] items-end gap-4 md:grid-cols-4">
          <div class="h-56 rounded-t-[2rem] bg-white/80 shadow-soft md:h-72"></div>
          <div class="h-64 rounded-t-[2rem] bg-white shadow-soft md:h-80"></div>
          <div class="relative h-72 rounded-t-[2.5rem] bg-white shadow-card md:h-[22rem]">
            <div class="absolute -left-9 bottom-16 rounded-2xl bg-white px-4 py-3 shadow-soft">
              <p class="text-xs text-slate-500">Interactive view</p>
              <p class="text-sm font-semibold">Open</p>
            </div>
            @if(isset($topProducts[0]) && !empty($topProducts[0]['image_path']))
              <img src="{{ asset('storage/'.$topProducts[0]['image_path']) }}" alt="{{ $topProducts[0]['name'] }}" class="absolute inset-5 h-[calc(100%-2.5rem)] w-[calc(100%-2.5rem)] rounded-2xl object-cover" />
            @else
              <div class="absolute inset-5 rounded-2xl bg-gradient-to-br from-[#d6dbd0] to-[#9aa89b]"></div>
            @endif
          </div>
          <div class="h-48 rounded-t-[2rem] bg-white/80 shadow-soft md:h-60"></div>

          @if(isset($topProducts[0]))
            <div class="absolute right-2 top-12 z-20 rounded-2xl bg-white px-4 py-3 shadow-soft md:right-10">
              <p class="text-[10px] text-slate-400">Top product</p>
              <p class="text-sm font-semibold">{{ \App\Support\Money::format((string) $topProducts[0]['price'], $currency) }}</p>
              <p class="text-xs text-slate-500">{{ $topProducts[0]['name'] }}</p>
            </div>
          @endif
        </div>
      </section>

      <section id="featured-sellers" class="rounded-t-[2.75rem] bg-white px-5 py-16 md:px-8 lg:px-12 lg:py-20">
        <div class="flex items-end justify-between gap-4">
          <div>
            <span class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-500">Featured sellers</span>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight md:text-5xl">Discover trusted sellers customers already love.</h2>
          </div>
          <a href="{{ route('marketplace.sellers') }}" class="hidden rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 md:inline-flex">View all sellers</a>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
          @forelse($featuredSellers as $seller)
            @php
              $bgPool = ['bg-[#f5ebe8]', 'bg-[#f5eef4]', 'bg-[#eef5f8]', 'bg-[#edf4f1]'];
              $colorPool = ['from-[#8a5237] to-[#2d1d17]', 'from-[#d6bead] to-[#ad8d7a]', 'from-[#d7dfe7] to-[#8996a7]', 'from-[#111827] to-[#475569]'];
              $idx = $loop->index % 4;
            @endphp
            <article class="group overflow-hidden rounded-[1.75rem] {{ $bgPool[$idx] }} p-4 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
              <div class="flex h-64 items-end rounded-[1.4rem] bg-gradient-to-br {{ $colorPool[$idx] }} p-4 text-white">
                <div class="w-full rounded-full bg-white/20 px-4 py-2 text-sm backdrop-blur">{{ $seller['business_name'] }}</div>
              </div>
              <div class="mt-4 flex items-center justify-between">
                <div>
                  <p class="font-semibold">{{ $seller['category'] }}</p>
                  <p class="text-sm text-slate-500">{{ $seller['product_count'] }} products · {{ number_format($seller['rating'], 1) }} rating</p>
                </div>
                <a href="{{ route('public.listing', $seller['slug']) }}" class="rounded-full bg-white px-4 py-2 text-sm font-medium shadow-soft">Open</a>
              </div>
            </article>
          @empty
            <div class="col-span-full rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500">No featured sellers available yet.</div>
          @endforelse
        </div>
      </section>

      <section id="how-it-works" class="bg-white px-5 py-10 md:px-8 lg:px-12 lg:py-14">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          <article class="rounded-2xl bg-[#f7efef] p-6 shadow-soft">
            <h3 class="text-lg font-semibold">Superior seller quality</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Products appear in clean storefronts designed for conversion and trust.</p>
          </article>
          <article class="rounded-2xl bg-[#edf5fb] p-6 shadow-soft">
            <h3 class="text-lg font-semibold">Cutting-edge insights</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Understand what customers click, what they buy, and what to improve.</p>
          </article>
          <article class="rounded-2xl bg-[#f8f1f4] p-6 shadow-soft">
            <h3 class="text-lg font-semibold">Unmatched comfort and fit</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">A checkout and browsing experience that feels light and effortless.</p>
          </article>
          <article class="rounded-2xl bg-[#eef6f6] p-6 shadow-soft">
            <h3 class="text-lg font-semibold">Sleek and stylish design</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Modern UI blocks that make your products and brand look premium.</p>
          </article>
        </div>
      </section>

      <section class="bg-white px-5 py-12 md:px-8 lg:px-12 lg:py-16">
        <div class="overflow-hidden rounded-[2rem] bg-gradient-to-r from-[#7f7268] via-[#54463b] to-[#2c251f] p-8 text-white shadow-card md:p-10">
          <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-end">
            <h2 class="text-4xl font-semibold leading-tight md:text-5xl">30-Day Money-Back Guarantee</h2>
            <a href="{{ route('pricing') }}" class="inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950">Discover More</a>
          </div>
          <p class="mt-4 max-w-2xl text-sm text-white/85">If your business does not grow in clarity and conversion, we stand behind the product experience.</p>
        </div>
      </section>

      <section id="products" class="rounded-t-[2.75rem] bg-white px-5 py-16 md:px-8 lg:px-12 lg:py-20">
        <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
          <div>
            <span class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-500">Top marketplace products</span>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight md:text-5xl">One winning product from each seller, all in one place.</h2>
            <p class="mt-5 max-w-xl text-sm leading-7 text-slate-600 md:text-base">We highlight standout products from selected vendors so customers can discover quality items fast while every seller gets fair exposure.</p>

            @if(isset($topProducts[0]))
              <div class="mt-8 rounded-[1.75rem] bg-[#f8f3f0] p-6 shadow-soft">
                <p class="text-sm text-slate-500">Highlighted product</p>
                <h3 class="mt-1 text-2xl font-semibold">{{ $topProducts[0]['name'] }}</h3>
                <p class="mt-2 text-sm text-slate-600">by {{ $topProducts[0]['seller_name'] }}</p>
                <p class="mt-3 text-sm text-slate-700">{{ \App\Support\Money::format((string) $topProducts[0]['price'], $currency) }}</p>
                <a href="{{ !empty($topProducts[0]['slug']) ? route('public.product', ['product_slug' => $topProducts[0]['slug']]) : route('public.listing', $topProducts[0]['seller_slug']) }}" class="mt-4 inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white">View Product</a>
              </div>
            @endif
          </div>

          <div class="grid gap-5 sm:grid-cols-2">
            @forelse($topProducts as $product)
              <article class="rounded-[1.75rem] bg-[#f7f3ee] p-5 shadow-soft">
                @if($product['image_path'])
                  <img src="{{ asset('storage/'.$product['image_path']) }}" alt="{{ $product['name'] }}" class="h-56 w-full rounded-[1.3rem] object-cover">
                @else
                  <div class="h-56 rounded-[1.3rem] bg-gradient-to-br from-[#dfceb7] to-[#8f6841]"></div>
                @endif
                <div class="mt-4 flex items-start justify-between gap-4">
                  <div>
                    <h3 class="font-semibold">{{ $product['name'] }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $product['seller_name'] }}</p>
                  </div>
                  <p class="font-semibold">{{ \App\Support\Money::format((string) $product['price'], $currency) }}</p>
                </div>
                <div class="mt-3 flex gap-2">
                  @if(!empty($product['slug']))
                    <a href="{{ route('public.product', ['product_slug' => $product['slug']]) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Open</a>
                  @else
                    <a href="{{ route('public.listing', $product['seller_slug']) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Open</a>
                  @endif
                  <a href="{{ route('public.listing', $product['seller_slug']) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Seller</a>
                </div>
              </article>
            @empty
              <div class="col-span-full rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500">No top products available yet.</div>
            @endforelse
          </div>
        </div>
      </section>

      <section id="faq" class="bg-white px-5 py-12 md:px-8 lg:px-12 lg:py-16">
        <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
          <div>
            <span class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-500">FAQ</span>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight md:text-5xl">Frequently asked questions</h2>
          </div>
          <div class="space-y-4">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="font-semibold">How do sellers start?</h3>
              <p class="mt-2 text-sm text-slate-600">Create an account, upload products, and share your public store link.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="font-semibold">How are featured sellers selected?</h3>
              <p class="mt-2 text-sm text-slate-600">Featured sellers are selected from top performance using successful sales history.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="font-semibold">Can customers view all products?</h3>
              <p class="mt-2 text-sm text-slate-600">Yes. Customers can open each seller page to browse complete product catalogs.</p>
            </article>
          </div>
        </div>
      </section>

      <footer class="bg-[#dfe8f0] px-5 pb-7 pt-12 md:px-8 lg:px-12">
        <div class="grid gap-10 lg:grid-cols-[1.1fr_0.7fr_0.8fr_1.2fr]">
          <div>
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-lg font-extrabold text-white">8K</div>
              <div>
                <p class="text-lg font-semibold leading-none">8Kommerce</p>
                <p class="text-xs text-slate-500">Pay by WhatsApp</p>
              </div>
            </div>
            <div class="mt-6 space-y-1 text-sm text-slate-600">
              <p>Accra, Ghana</p>
              <p>support@8kommerce.com</p>
            </div>
          </div>

          <div>
            <h3 class="text-sm font-semibold">Menu</h3>
            <ul class="mt-5 space-y-3 text-sm text-slate-600">
              <li><a href="#" class="hover:text-slate-950">Home</a></li>
              <li><a href="{{ route('marketplace.sellers') }}" class="hover:text-slate-950">Sellers</a></li>
              <li><a href="#products" class="hover:text-slate-950">Products</a></li>
              <li><a href="#faq" class="hover:text-slate-950">FAQ</a></li>
            </ul>
          </div>

          <div>
            <h3 class="text-sm font-semibold">Operational</h3>
            <ul class="mt-5 space-y-3 text-sm text-slate-600">
              <li>Every day: 9:00 - 22:00</li>
              <li>Seller support available</li>
              <li>Marketplace discovery active</li>
            </ul>
          </div>

          <div>
            <h3 class="text-3xl font-semibold tracking-tight">Subscribe to our newsletter</h3>
            <form class="mt-6 flex flex-col gap-3 sm:flex-row">
              <input class="w-full rounded-full border border-slate-300 bg-white px-5 py-3.5 text-sm outline-none" type="email" placeholder="Email" />
              <button class="rounded-full bg-slate-950 px-6 py-3.5 text-sm font-semibold text-white" type="button">Subscribe</button>
            </form>
          </div>
        </div>

        <div class="mt-10 border-t border-slate-300/70 pt-6 text-xs text-slate-500 md:flex md:items-center md:justify-between">
          <p>Copyright © 8Kommerce. All rights reserved.</p>
          <div class="mt-4 flex items-center gap-2 md:mt-0">
            <span class="rounded bg-white px-2 py-1 shadow-soft">Visa</span>
            <span class="rounded bg-white px-2 py-1 shadow-soft">Mastercard</span>
            <span class="rounded bg-white px-2 py-1 shadow-soft">MoMo</span>
            <span class="rounded bg-white px-2 py-1 shadow-soft">Paystack</span>
          </div>
        </div>
      </footer>
    </div>
  </main>
</body>
</html>
