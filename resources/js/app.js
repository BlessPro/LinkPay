import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

const stripLeadingZero = (input) => {
    if (!input || input.dataset.stripLeadingZero !== 'true') {
        return;
    }
    const value = input.value || '';
    if (value.startsWith('0')) {
        input.value = value.replace(/^0+/, '');
    }
};

document.addEventListener('input', (event) => {
    stripLeadingZero(event.target);
});

document.addEventListener('blur', (event) => {
    stripLeadingZero(event.target);
}, true);

const telemetryEndpoint = '/telemetry/client';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const telemetry = {
    track(event, payload = {}, level = 'info') {
        if (!event) return;
        const body = JSON.stringify({
            event,
            level,
            metric_ms: payload.metricMs ?? null,
            meta: {
                ...payload,
                metricMs: undefined,
            },
        });
        fetch(telemetryEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
            keepalive: true,
            credentials: 'same-origin',
        }).catch(() => {});
    },
};
window.lpTelemetry = telemetry;

const setupPwa = () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    let hasPendingRefresh = false;
    let updateBanner = null;
    const VERSION_KEY = 'lp_seen_version';

    const hideUpdateBanner = () => {
        if (updateBanner) {
            updateBanner.remove();
            updateBanner = null;
        }
    };

    const showUpdateBanner = ({ registration = null, staleOnly = false } = {}) => {
        if (updateBanner) {
            return;
        }
        updateBanner = document.createElement('div');
        updateBanner.className = 'fixed bottom-20 left-4 right-4 z-[75] rounded-2xl border border-amber-200 bg-white p-3 text-slate-800 shadow-xl sm:left-auto sm:right-4 sm:w-[min(92vw,22rem)] sm:bottom-6';
        updateBanner.innerHTML = `
            <p class="text-xs font-semibold text-amber-700">New version available</p>
            <p class="mt-1 text-xs text-slate-600">Refresh to get the latest fixes and improvements.</p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" data-refresh-now class="inline-flex flex-1 items-center justify-center rounded-full bg-amber-600 px-3 py-2 text-xs font-semibold text-white">Refresh now</button>
                <button type="button" data-refresh-later class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Later</button>
            </div>
        `;

        const refreshNow = updateBanner.querySelector('[data-refresh-now]');
        const refreshLater = updateBanner.querySelector('[data-refresh-later]');

        refreshNow?.addEventListener('click', () => {
            telemetry.track('pwa_update_refresh_clicked', { screen: window.location.pathname });
            if (registration?.waiting) {
                hasPendingRefresh = true;
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                return;
            }
            window.location.reload();
        });

        refreshLater?.addEventListener('click', () => {
            telemetry.track('pwa_update_refresh_later', { screen: window.location.pathname }, 'warn');
            hideUpdateBanner();
        });

        document.body.appendChild(updateBanner);
        telemetry.track(staleOnly ? 'pwa_stale_refresh_prompt_shown' : 'pwa_update_available', {
            screen: window.location.pathname,
        });
    };

    const observeRegistration = (registration) => {
        if (!registration) return;

        if (registration.waiting) {
            showUpdateBanner({ registration });
        }

        registration.addEventListener('updatefound', () => {
            const installing = registration.installing;
            if (!installing) return;
            installing.addEventListener('statechange', () => {
                if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                    showUpdateBanner({ registration });
                }
            });
        });

        // Periodic update check for long-lived sessions.
        window.setInterval(() => {
            registration.update().catch(() => {});
        }, 5 * 60 * 1000);
    };

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!hasPendingRefresh) {
            return;
        }
        hasPendingRefresh = false;
        window.location.reload();
    });

    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            observeRegistration(registration);
        } catch (error) {
            // non-fatal
            console.warn('Service worker registration failed', error);
            telemetry.track('pwa_sw_register_failed', { screen: window.location.pathname }, 'warn');
        }
    });

    const checkVersionDrift = async () => {
        try {
            const response = await fetch(`/version.json?ts=${Date.now()}`, {
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) return;
            const payload = await response.json();
            const currentVersion = String(payload?.version || '').trim();
            if (!currentVersion) return;

            const previousVersion = localStorage.getItem(VERSION_KEY);
            if (!previousVersion) {
                localStorage.setItem(VERSION_KEY, currentVersion);
                return;
            }

            if (previousVersion !== currentVersion) {
                localStorage.setItem(VERSION_KEY, currentVersion);
                // If SW update path didn't trigger, still suggest reload.
                if (!updateBanner) {
                    showUpdateBanner({ staleOnly: true });
                }
            }
        } catch (_) {
            // ignore version check failures
        }
    };

    window.addEventListener('load', () => {
        checkVersionDrift();
        window.setInterval(checkVersionDrift, 10 * 60 * 1000);
    });

    let deferredPrompt = null;
    let installBanner = null;
    let iosInstallBanner = null;
    const INSTALL_DISMISS_KEY = 'lp_install_prompt_dismissed_until';
    const IOS_INSTALL_DISMISS_KEY = 'lp_ios_install_prompt_dismissed_until';
    const REPROMPT_MS = 3 * 24 * 60 * 60 * 1000;

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

    const isSafari = () => {
        const ua = window.navigator.userAgent.toLowerCase();
        return /safari/.test(ua) && !/crios|fxios|edgios|opr|opera/.test(ua);
    };

    const getDismissUntil = (key) => {
        try {
            return Number(localStorage.getItem(key) || 0);
        } catch (_) {
            return 0;
        }
    };

    const setDismissUntil = (key, untilMs) => {
        try {
            localStorage.setItem(key, String(untilMs));
        } catch (_) {
            // ignore localStorage issues
        }
    };

    const shouldShowPrompt = (key) => Date.now() >= getDismissUntil(key);

    const hideInstallBanner = () => {
        if (installBanner) {
            installBanner.remove();
            installBanner = null;
        }
    };

    const hideIosInstallBanner = () => {
        if (iosInstallBanner) {
            iosInstallBanner.remove();
            iosInstallBanner = null;
        }
    };

    const showInstallBanner = () => {
        if (installBanner || isStandalone() || !deferredPrompt || !shouldShowPrompt(INSTALL_DISMISS_KEY)) {
            return;
        }

        installBanner = document.createElement('div');
        installBanner.className = 'fixed bottom-20 right-4 z-[70] w-[min(92vw,20rem)] rounded-2xl border border-emerald-200 bg-white p-3 text-slate-800 shadow-xl sm:bottom-6';
        installBanner.innerHTML = `
            <p class="text-xs font-semibold text-emerald-700">Install 8Kommerce</p>
            <p class="mt-1 text-xs text-slate-600">Add to your home screen for faster access.</p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" data-install-now class="inline-flex flex-1 items-center justify-center rounded-full bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Install</button>
                <button type="button" data-install-later class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Later</button>
            </div>
        `;

        const installNow = installBanner.querySelector('[data-install-now]');
        const installLater = installBanner.querySelector('[data-install-later]');

        installNow?.addEventListener('click', async () => {
            if (!deferredPrompt) {
                return;
            }
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            if (choice?.outcome === 'accepted') {
                telemetry.track('pwa_install_prompt_accepted', { screen: window.location.pathname });
                hideInstallBanner();
            } else {
                setDismissUntil(INSTALL_DISMISS_KEY, Date.now() + REPROMPT_MS);
                telemetry.track('pwa_install_prompt_dismissed', { screen: window.location.pathname }, 'warn');
            }
            deferredPrompt = null;
        });

        installLater?.addEventListener('click', () => {
            setDismissUntil(INSTALL_DISMISS_KEY, Date.now() + REPROMPT_MS);
            hideInstallBanner();
            telemetry.track('pwa_install_prompt_later', { screen: window.location.pathname });
        });

        document.body.appendChild(installBanner);
    };

    const showIosInstallBanner = () => {
        if (iosInstallBanner || !isIos() || !isSafari() || isStandalone() || !shouldShowPrompt(IOS_INSTALL_DISMISS_KEY)) {
            return;
        }

        iosInstallBanner = document.createElement('div');
        iosInstallBanner.className = 'fixed bottom-20 left-4 right-4 z-[70] rounded-2xl border border-blue-200 bg-white p-3 text-slate-800 shadow-xl sm:left-auto sm:right-4 sm:w-[min(92vw,22rem)] sm:bottom-6';
        iosInstallBanner.innerHTML = `
            <p class="text-xs font-semibold text-blue-700">Add 8Kommerce to Home Screen</p>
            <p class="mt-1 text-xs text-slate-600">On iPhone: tap <strong>Share</strong> then <strong>Add to Home Screen</strong>.</p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" data-ios-install-done class="inline-flex flex-1 items-center justify-center rounded-full bg-blue-600 px-3 py-2 text-xs font-semibold text-white">Got it</button>
                <button type="button" data-ios-install-later class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Later</button>
            </div>
        `;

        const doneBtn = iosInstallBanner.querySelector('[data-ios-install-done]');
        const laterBtn = iosInstallBanner.querySelector('[data-ios-install-later]');

        doneBtn?.addEventListener('click', () => {
            setDismissUntil(IOS_INSTALL_DISMISS_KEY, Date.now() + REPROMPT_MS);
            hideIosInstallBanner();
            telemetry.track('pwa_ios_install_hint_acknowledged', { screen: window.location.pathname });
        });

        laterBtn?.addEventListener('click', () => {
            setDismissUntil(IOS_INSTALL_DISMISS_KEY, Date.now() + REPROMPT_MS);
            hideIosInstallBanner();
            telemetry.track('pwa_ios_install_hint_dismissed', { screen: window.location.pathname }, 'warn');
        });

        document.body.appendChild(iosInstallBanner);
        telemetry.track('pwa_ios_install_hint_shown', { screen: window.location.pathname });
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        telemetry.track('pwa_install_prompt_shown', { screen: window.location.pathname });
        showInstallBanner();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideInstallBanner();
        hideIosInstallBanner();
        telemetry.track('pwa_installed', { screen: window.location.pathname });
    });

    showIosInstallBanner();
};

setupPwa();

const setupRuntimeHealth = () => {
    let healthBanner = null;
    let refreshPromptShown = false;
    let consecutiveApiFailures = 0;
    let recentApiFailureTimestamps = [];
    const API_FAILURE_THRESHOLD = 3;
    const API_FAILURE_WINDOW_MS = 60 * 1000;
    const HEALTH_PROMPT_COOLDOWN_MS = 10 * 60 * 1000;
    const HEALTH_PROMPT_KEY = 'lp_health_prompt_last_at';

    const nowMs = () => Date.now();

    const shouldThrottlePrompt = () => {
        try {
            const last = Number(localStorage.getItem(HEALTH_PROMPT_KEY) || 0);
            return nowMs() - last < HEALTH_PROMPT_COOLDOWN_MS;
        } catch (_) {
            return false;
        }
    };

    const markPromptShown = () => {
        try {
            localStorage.setItem(HEALTH_PROMPT_KEY, String(nowMs()));
        } catch (_) {
            // ignore storage issues
        }
    };

    const hideHealthBanner = () => {
        if (healthBanner) {
            healthBanner.remove();
            healthBanner = null;
        }
    };

    const showHealthBanner = (reason) => {
        if (refreshPromptShown || healthBanner || shouldThrottlePrompt()) {
            return;
        }

        refreshPromptShown = true;
        markPromptShown();
        healthBanner = document.createElement('div');
        healthBanner.className = 'fixed bottom-20 left-4 right-4 z-[76] rounded-2xl border border-rose-200 bg-white p-3 text-slate-800 shadow-xl sm:left-auto sm:right-4 sm:w-[min(92vw,24rem)] sm:bottom-6';
        healthBanner.innerHTML = `
            <p class="text-xs font-semibold text-rose-700">App seems out of sync</p>
            <p class="mt-1 text-xs text-slate-600">Refreshing can make things smoother.</p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" data-health-refresh class="inline-flex flex-1 items-center justify-center rounded-full bg-rose-600 px-3 py-2 text-xs font-semibold text-white">Refresh app</button>
                <button type="button" data-health-dismiss class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Not now</button>
            </div>
        `;

        const refreshBtn = healthBanner.querySelector('[data-health-refresh]');
        const dismissBtn = healthBanner.querySelector('[data-health-dismiss]');

        refreshBtn?.addEventListener('click', () => {
            telemetry.track('runtime_health_refresh_clicked', {
                screen: window.location.pathname,
                reason,
            });
            window.location.reload();
        });

        dismissBtn?.addEventListener('click', () => {
            telemetry.track('runtime_health_refresh_dismissed', {
                screen: window.location.pathname,
                reason,
            }, 'warn');
            hideHealthBanner();
        });

        document.body.appendChild(healthBanner);
        telemetry.track('runtime_health_refresh_prompt_shown', {
            screen: window.location.pathname,
            reason,
        }, 'warn');
    };

    const trackApiFailure = (reason) => {
        const ts = nowMs();
        consecutiveApiFailures += 1;
        recentApiFailureTimestamps.push(ts);
        recentApiFailureTimestamps = recentApiFailureTimestamps.filter((value) => ts - value <= API_FAILURE_WINDOW_MS);

        if (consecutiveApiFailures >= API_FAILURE_THRESHOLD || recentApiFailureTimestamps.length >= API_FAILURE_THRESHOLD) {
            showHealthBanner(reason);
        }
    };

    const trackApiSuccess = () => {
        consecutiveApiFailures = 0;
        recentApiFailureTimestamps = [];
    };

    const isTrackableApiRequest = (input) => {
        try {
            const url = typeof input === 'string' ? new URL(input, window.location.origin) : new URL(input.url, window.location.origin);
            if (url.origin !== window.location.origin) return false;
            if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) return false;
            return true;
        } catch (_) {
            return false;
        }
    };

    const nativeFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
        const [input] = args;
        const track = isTrackableApiRequest(input);
        try {
            const response = await nativeFetch(...args);
            if (track && !response.ok && response.status >= 500) {
                trackApiFailure(`http_${response.status}`);
            } else if (track && response.ok) {
                trackApiSuccess();
            }
            return response;
        } catch (error) {
            if (track) {
                trackApiFailure('network_error');
            }
            throw error;
        }
    };

    window.addEventListener('offline', () => {
        telemetry.track('runtime_health_offline', { screen: window.location.pathname }, 'warn');
    });

    window.addEventListener('online', () => {
        telemetry.track('runtime_health_online', { screen: window.location.pathname });
        trackApiSuccess();
    });

    window.addEventListener('error', () => {
        trackApiFailure('window_error');
    });

    window.addEventListener('unhandledrejection', () => {
        trackApiFailure('unhandled_rejection');
    });
};

setupRuntimeHealth();

(() => {
    try {
        const offlineHit = localStorage.getItem('lp_offline_hit');
        if (offlineHit) {
            telemetry.track('pwa_offline_fallback_hit', {
                screen: window.location.pathname,
                offlineAt: offlineHit,
            }, 'warn');
            localStorage.removeItem('lp_offline_hit');
        }
    } catch (_) {
        // ignore localStorage issues
    }
})();

window.addEventListener('load', () => {
    try {
        const nav = performance.getEntriesByType('navigation')[0];
        const ua = navigator.userAgent.toLowerCase();
        const isMobile = /android|iphone|mobile|ipad/.test(ua);
        if (!nav || !isMobile) return;

        const key = `lp_mobile_timing_${window.location.pathname}`;
        if (sessionStorage.getItem(key)) return;
        sessionStorage.setItem(key, '1');

        telemetry.track('mobile_flow_timing', {
            screen: window.location.pathname,
            metricMs: Math.round(nav.loadEventEnd || nav.duration || 0),
            domContentLoadedMs: Math.round(nav.domContentLoadedEventEnd || 0),
            loadEventEndMs: Math.round(nav.loadEventEnd || 0),
            type: nav.type || 'navigate',
        });
    } catch (_) {
        // ignore
    }
});
