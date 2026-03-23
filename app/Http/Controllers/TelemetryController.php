<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'max:64'],
            'level' => ['nullable', 'string', 'in:info,warn,error'],
            'metric_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'meta' => ['nullable', 'array'],
        ]);

        $event = (string) $data['event'];
        $level = (string) ($data['level'] ?? 'info');
        $metricMs = $data['metric_ms'] ?? null;
        $meta = $data['meta'] ?? [];
        $user = $request->user();

        $context = [
            'event' => $event,
            'level' => $level,
            'metric_ms' => $metricMs,
            'meta' => $meta,
            'path' => $request->path(),
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];

        match ($level) {
            'error' => Log::error('Client telemetry event', $context),
            'warn' => Log::warning('Client telemetry event', $context),
            default => Log::info('Client telemetry event', $context),
        };

        if ($user) {
            AnalyticsEvent::create([
                'user_id' => $user->id,
                'event_type' => 'telemetry_'.$event,
                'entity_type' => 'telemetry',
                'entity_id' => isset($meta['screen']) ? (string) $meta['screen'] : null,
                'session_hash' => $this->hashValue($request->session()->getId()),
                'ip_hash' => $this->hashValue($request->ip()),
                'user_agent_hash' => $this->hashValue($request->userAgent()),
                'device_type' => $this->detectDevice($request->userAgent()),
                'referrer_host' => $this->referrerHost($request),
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
            ]);
        }

        return response()->json(['ok' => true], 202);
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

    private function referrerHost(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');
        if (! $referrer) {
            return null;
        }

        $parts = parse_url($referrer);

        return $parts['host'] ?? null;
    }
}

