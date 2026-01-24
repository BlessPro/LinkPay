<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;

class AnalyticsService
{
    public function trackEvent(
        Request $request,
        int $userId,
        string $eventType,
        ?string $entityType = null,
        ?string $entityId = null
    ): void {
        $utm = $this->captureUtm($request);
        $referrerHost = $this->getReferrerHost($request);
        $sessionHash = $this->hashValue($request->session()->getId());
        $ipHash = $this->hashValue($request->ip());
        $userAgent = $request->userAgent();

        AnalyticsEvent::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'session_hash' => $sessionHash,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $this->hashValue($userAgent),
            'device_type' => $this->detectDevice($userAgent),
            'referrer_host' => $referrerHost,
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
        ]);
    }

    private function captureUtm(Request $request): array
    {
        $utm = [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
        ];

        if (array_filter($utm)) {
            $request->session()->put('utm_params', $utm);
            return $utm;
        }

        return $request->session()->get('utm_params', []);
    }

    private function getReferrerHost(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');
        if (! $referrer) {
            return null;
        }

        $parts = parse_url($referrer);

        return $parts['host'] ?? null;
    }

    private function hashValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function detectDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $agent = strtolower($userAgent);
        if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
