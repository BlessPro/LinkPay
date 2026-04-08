@php($title = 'Onboarding')
@extends('layouts.dashboard')

@section('content')
    <style>
        .onb-step-enter {
            animation: onbStepEnter .28s ease-out;
        }
        @keyframes onbStepEnter {
            from { opacity: 0; transform: translateX(12px) scale(.98); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        .onb-press {
            transition: transform .12s ease;
        }
        .onb-press:active {
            transform: scale(.97);
        }
    </style>

    @if($isMobile && ! $onboarding['is_complete'])
        <div class="mx-auto max-w-xl pb-28">
            @if(session('onboarding_required'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('onboarding_required') }}
                </div>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Onboarding</p>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        {{ $onboarding['completed_count'] }}/{{ $onboarding['total_count'] }}
                    </span>
                </div>
                <div class="mt-3 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $onboarding['percent'] }}%"></div>
                </div>

                <div class="js-mobile-onboarding mt-4 overflow-hidden rounded-2xl">
                    <div class="js-mobile-track flex transition-transform duration-300 ease-out">
                        @foreach($onboarding['steps'] as $index => $step)
                            <article
                                class="js-mobile-step w-full shrink-0 rounded-2xl border px-4 py-5 {{ $step['completed'] ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }}"
                                data-step-id="{{ $step['id'] }}"
                                data-action-url="{{ $step['action_url'] }}"
                                data-action-label="{{ $step['action_label'] }}"
                                data-public-url="{{ $step['public_url'] ?? '' }}"
                                data-required="{{ $step['required'] ? '1' : '0' }}"
                            >
                                <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Step {{ $index + 1 }} of {{ count($onboarding['steps']) }}</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $step['description'] }}</p>
                                <div class="mt-4 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $step['completed'] ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $step['completed'] ? 'Completed' : 'Pending' }}
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex justify-center gap-2">
                    @foreach($onboarding['steps'] as $index => $step)
                        <button type="button" class="js-mobile-dot h-2.5 w-2.5 rounded-full {{ $index === $currentIndex ? 'bg-emerald-600' : 'bg-slate-300' }}" data-index="{{ $index }}" aria-label="Go to step {{ $index + 1 }}"></button>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <button type="button" class="js-mobile-prev rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">Previous</button>
                    <button type="button" class="js-mobile-next rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">Next</button>
                </div>
            </section>

            <div class="fixed bottom-20 left-0 right-0 z-30 px-4">
                <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl">
                    <a href="{{ $currentStep['action_url'] }}" class="js-mobile-primary-action inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        {{ $currentStep['action_label'] }}
                    </a>
                    <button type="button" class="js-mobile-copy onb-press mt-2 hidden w-full rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">
                        Copy link
                    </button>
                    <button type="button" class="js-mobile-skip onb-press mt-2 hidden w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">
                        Skip for now
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl">
            @if(session('onboarding_required'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('onboarding_required') }}
                </div>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Getting Started</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Onboarding checklist</h2>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        {{ $onboarding['completed_count'] }}/{{ $onboarding['total_count'] }} complete
                    </span>
                </div>

                <div class="mt-4 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $onboarding['percent'] }}%"></div>
                </div>

                @if($onboarding['is_complete'])
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <p class="text-sm font-semibold text-emerald-800">All onboarding steps completed.</p>
                        <a href="{{ route('dashboard') }}" class="mt-3 inline-flex rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                            Go to dashboard
                        </a>
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-6">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">
                            Step {{ $currentIndex + 1 }} of {{ count($onboarding['steps']) }}
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $currentStep['title'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $currentStep['description'] }}</p>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <a href="{{ $currentStep['action_url'] }}" class="inline-flex rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                {{ $currentStep['action_label'] }}
                            </a>
                            @if(($currentStep['id'] ?? null) === 'share_store' && !empty($currentStep['public_url']))
                                <button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700 js-onboarding-copy" data-copy-value="{{ $currentStep['public_url'] }}">
                                    Copy link
                                </button>
                            @endif
                        </div>

                        <div class="mt-5 flex items-center justify-between text-xs">
                            @if($prevStepIndex !== null)
                                <a href="{{ route('onboarding.index', ['step' => $prevStepIndex]) }}" class="rounded-full border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:border-slate-300">
                                    Previous
                                </a>
                            @else
                                <span></span>
                            @endif

                            @if($nextStepIndex !== null)
                                <a href="{{ route('onboarding.index', ['step' => $nextStepIndex]) }}" class="rounded-full border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:border-slate-300">
                                    Next
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2">
                        @foreach($onboarding['steps'] as $index => $step)
                            <a href="{{ route('onboarding.index', ['step' => $index]) }}" class="flex items-center justify-between rounded-xl border px-3 py-2 {{ $step['completed'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-white' }}">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[11px] {{ $step['completed'] ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                        {!! $step['completed'] ? '&check;' : (string) ($index + 1) !!}
                                    </span>
                                    <span class="text-sm font-medium text-slate-800">{{ $step['title'] }}</span>
                                </div>
                                <span class="text-[11px] font-semibold {{ $step['completed'] ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ $step['completed'] ? 'Done' : 'Pending' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const copyButton = document.querySelector('.js-onboarding-copy');
            if (copyButton) {
                copyButton.addEventListener('click', async () => {
                    const value = copyButton.dataset.copyValue || '';
                    if (!value) {
                        return;
                    }
                    try {
                        await navigator.clipboard.writeText(value);
                        copyButton.textContent = 'Copied';
                    } catch (_) {
                        copyButton.textContent = 'Copy failed';
                    }
                    setTimeout(() => {
                        copyButton.textContent = 'Copy link';
                    }, 1200);
                });
            }

            const mobileRoot = document.querySelector('.js-mobile-onboarding');
            if (!mobileRoot) {
                return;
            }

            const track = mobileRoot.querySelector('.js-mobile-track');
            const slides = Array.from(mobileRoot.querySelectorAll('.js-mobile-step'));
            const dots = Array.from(document.querySelectorAll('.js-mobile-dot'));
            const prevBtn = document.querySelector('.js-mobile-prev');
            const nextBtn = document.querySelector('.js-mobile-next');
            const primaryAction = document.querySelector('.js-mobile-primary-action');
            const copyBtn = document.querySelector('.js-mobile-copy');
            const skipBtn = document.querySelector('.js-mobile-skip');
            let index = Math.max(0, {{ (int) $currentIndex }});
            let touchStartX = 0;
            let touchCurrentX = 0;

            const syncState = (payload) => {
                return fetch('{{ route('onboarding.state') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).catch(() => {});
            };

            const updateUI = () => {
                track.style.transform = `translateX(-${index * 100}%)`;

                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('bg-emerald-600', dotIndex === index);
                    dot.classList.toggle('bg-slate-300', dotIndex !== index);
                });

                if (prevBtn) {
                    prevBtn.disabled = index === 0;
                    prevBtn.classList.toggle('opacity-40', index === 0);
                }
                if (nextBtn) {
                    const atEnd = index === slides.length - 1;
                    nextBtn.disabled = atEnd;
                    nextBtn.classList.toggle('opacity-40', atEnd);
                }

                const current = slides[index];
                const actionUrl = current?.dataset.actionUrl || '{{ route('dashboard') }}';
                const actionLabel = current?.dataset.actionLabel || 'Continue';
                const publicUrl = current?.dataset.publicUrl || '';
                const required = (current?.dataset.required || '0') === '1';

                if (primaryAction) {
                    primaryAction.href = actionUrl;
                    primaryAction.textContent = actionLabel;
                    primaryAction.classList.add('onb-press');
                }
                if (copyBtn) {
                    copyBtn.classList.toggle('hidden', !publicUrl);
                    copyBtn.dataset.copyValue = publicUrl;
                }
                if (skipBtn) {
                    skipBtn.classList.toggle('hidden', required);
                }

                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('onb-step-enter', slideIndex === index);
                });

                syncState({ mobile_step: index });
            };

            const goTo = (target) => {
                index = Math.max(0, Math.min(slides.length - 1, target));
                updateUI();
            };

            dots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    goTo(Number(dot.dataset.index || 0));
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', () => goTo(index - 1));
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', () => goTo(index + 1));
            }

            mobileRoot.addEventListener('touchstart', (event) => {
                if (event.touches.length !== 1) {
                    return;
                }
                touchStartX = event.touches[0].clientX;
                touchCurrentX = touchStartX;
            }, { passive: true });

            mobileRoot.addEventListener('touchmove', (event) => {
                if (event.touches.length !== 1) {
                    return;
                }
                touchCurrentX = event.touches[0].clientX;
            }, { passive: true });

            mobileRoot.addEventListener('touchend', () => {
                const delta = touchCurrentX - touchStartX;
                if (Math.abs(delta) < 40) {
                    return;
                }
                if (delta < 0) {
                    goTo(index + 1);
                } else {
                    goTo(index - 1);
                }
            });

            if (copyBtn) {
                copyBtn.addEventListener('click', async () => {
                    const value = copyBtn.dataset.copyValue || '';
                    if (!value) {
                        return;
                    }
                    try {
                        await navigator.clipboard.writeText(value);
                        copyBtn.textContent = 'Copied';
                    } catch (_) {
                        copyBtn.textContent = 'Copy failed';
                    }
                    setTimeout(() => {
                        copyBtn.textContent = 'Copy link';
                    }, 1200);
                });
            }

            if (skipBtn) {
                skipBtn.addEventListener('click', () => {
                    const current = slides[index];
                    const stepId = current?.dataset.stepId || '';
                    if (stepId) {
                        syncState({ skip_step: stepId });
                    }
                    if (index < slides.length - 1) {
                        goTo(index + 1);
                    }
                });
            }

            updateUI();
        });
    </script>
@endsection
