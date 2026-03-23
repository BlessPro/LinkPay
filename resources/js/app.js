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

    window.addEventListener('load', async () => {
        try {
            await navigator.serviceWorker.register('/sw.js');
        } catch (error) {
            // non-fatal
            console.warn('Service worker registration failed', error);
            telemetry.track('pwa_sw_register_failed', { screen: window.location.pathname }, 'warn');
        }
    });

    let deferredPrompt = null;
    let installButton = null;

    const hideInstallButton = () => {
        if (installButton) {
            installButton.remove();
            installButton = null;
        }
    };

    const showInstallButton = () => {
        if (installButton || window.matchMedia('(display-mode: standalone)').matches) {
            return;
        }

        installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.textContent = 'Install app';
        installButton.className = 'fixed bottom-20 right-4 z-[70] rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-lg hover:bg-emerald-500 sm:bottom-6';
        installButton.addEventListener('click', async () => {
            if (!deferredPrompt) {
                return;
            }
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            if (choice?.outcome === 'accepted') {
                telemetry.track('pwa_install_prompt_accepted', { screen: window.location.pathname });
                hideInstallButton();
            } else {
                telemetry.track('pwa_install_prompt_dismissed', { screen: window.location.pathname }, 'warn');
            }
            deferredPrompt = null;
        });
        document.body.appendChild(installButton);
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        telemetry.track('pwa_install_prompt_shown', { screen: window.location.pathname });
        showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideInstallButton();
        telemetry.track('pwa_installed', { screen: window.location.pathname });
    });
};

setupPwa();

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
