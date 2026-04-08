<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\User;

class OnboardingService
{
    public function forUser(User $user): array
    {
        $state = $this->state($user);
        $skippedSteps = collect($state['skipped_steps'] ?? [])->filter()->values()->all();

        $hasPin = filled($user->pin_hash);
        $hasProfile = $user->hasCompletedProfileOnboarding();
        $hasProduct = $user->products()
            ->where('status', '!=', Product::STATUS_TRASHED)
            ->exists();
        $hasSharedStore = $user->analyticsEvents()
            ->whereIn('event_type', [
                AnalyticsEvent::TYPE_LISTING_VIEW,
                AnalyticsEvent::TYPE_PRODUCT_CLICK,
            ])
            ->exists();

        $publicSlug = $user->sellerProfile?->public_slug;
        $publicUrl = $publicSlug ? route('public.listing', $publicSlug) : null;

        $steps = [
            [
                'id' => 'set_pin',
                'title' => 'Set your PIN',
                'description' => 'Create a 4-digit PIN for fast sign in.',
                'completed' => $hasPin,
                'action_label' => 'Set PIN',
                'action_url' => route('pin.setup.show'),
                'required' => true,
            ],
            [
                'id' => 'complete_profile',
                'title' => 'Complete business profile',
                'description' => 'Add business name, phone, and email.',
                'completed' => $hasProfile,
                'action_label' => 'Complete profile',
                'action_url' => route('profile.edit', ['onboarding' => 'profile']),
                'required' => true,
            ],
            [
                'id' => 'add_first_product',
                'title' => 'Add your first product',
                'description' => 'Create your first item so customers can buy.',
                'completed' => $hasProduct,
                'action_label' => 'Add product',
                'action_url' => route('products.create'),
                'required' => false,
            ],
            [
                'id' => 'share_store',
                'title' => 'Share your store link',
                'description' => 'Share your public link and start getting traffic.',
                'completed' => $hasSharedStore,
                'action_label' => $publicUrl ? 'Copy store link' : 'Open profile',
                'action_url' => $publicUrl ?: route('profile.edit'),
                'required' => false,
                'public_url' => $publicUrl,
            ],
        ];

        $steps = collect($steps)->map(function (array $step) use ($skippedSteps) {
            $step['skipped'] = ! $step['required'] && in_array($step['id'], $skippedSteps, true);
            $step['effective_done'] = (bool) ($step['completed'] || $step['skipped']);

            return $step;
        })->values()->all();

        $completedCount = collect($steps)->where('effective_done', true)->count();
        $totalCount = count($steps);
        $percent = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;

        $nextStep = collect($steps)->first(fn (array $step) => ! $step['effective_done']);

        return [
            'steps' => $steps,
            'completed_count' => $completedCount,
            'total_count' => $totalCount,
            'percent' => $percent,
            'next_step' => $nextStep,
            'is_complete' => $completedCount === $totalCount,
            'state' => $state,
        ];
    }

    private function state(User $user): array
    {
        $state = $user->onboarding_state;
        if (! is_array($state)) {
            $state = [];
        }

        $state['skipped_steps'] = is_array($state['skipped_steps'] ?? null) ? $state['skipped_steps'] : [];
        $state['mobile_step'] = max(0, (int) ($state['mobile_step'] ?? 0));
        $state['desktop_popup_dismissed'] = (bool) ($state['desktop_popup_dismissed'] ?? false);
        $state['desktop_tour_completed'] = (bool) ($state['desktop_tour_completed'] ?? false);
        $state['desktop_tour_dismissed'] = (bool) ($state['desktop_tour_dismissed'] ?? false);
        $state['desktop_tour_step'] = max(0, (int) ($state['desktop_tour_step'] ?? 0));

        return $state;
    }
}
