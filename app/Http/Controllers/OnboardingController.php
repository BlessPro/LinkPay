<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(Request $request, OnboardingService $onboardingService): View
    {
        $user = $request->user();
        $onboarding = $onboardingService->forUser($user);

        if ($onboarding['is_complete']) {
            return view('onboarding.index', [
                'onboarding' => $onboarding,
                'currentStep' => null,
                'currentIndex' => 0,
                'nextStepIndex' => null,
                'prevStepIndex' => null,
                'isMobile' => $this->isMobile($request),
            ]);
        }

        $steps = collect($onboarding['steps'])->values();
        $firstPendingIndex = $steps->search(fn (array $step) => ! ($step['effective_done'] ?? false));
        $defaultIndex = is_int($firstPendingIndex) ? $firstPendingIndex : 0;

        $stateStep = max(0, (int) data_get($onboarding, 'state.mobile_step', 0));
        $requestedIndex = max(0, (int) $request->query('step', $stateStep ?: $defaultIndex));
        $currentIndex = min($requestedIndex, max(0, $steps->count() - 1));
        $currentStep = $steps->get($currentIndex);

        $nextStepIndex = $currentIndex < ($steps->count() - 1) ? $currentIndex + 1 : null;
        $prevStepIndex = $currentIndex > 0 ? $currentIndex - 1 : null;

        return view('onboarding.index', [
            'onboarding' => $onboarding,
            'currentStep' => $currentStep,
            'currentIndex' => $currentIndex,
            'nextStepIndex' => $nextStepIndex,
            'prevStepIndex' => $prevStepIndex,
            'isMobile' => $this->isMobile($request),
        ]);
    }

    public function updateState(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'desktop_popup_dismissed' => ['nullable', 'boolean'],
            'mobile_step' => ['nullable', 'integer', 'min:0', 'max:100'],
            'skip_step' => ['nullable', 'string', 'max:50'],
            'unskip_step' => ['nullable', 'string', 'max:50'],
            'desktop_tour_completed' => ['nullable', 'boolean'],
            'desktop_tour_dismissed' => ['nullable', 'boolean'],
            'desktop_tour_step' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $state = is_array($user->onboarding_state) ? $user->onboarding_state : [];
        $state['skipped_steps'] = is_array($state['skipped_steps'] ?? null) ? $state['skipped_steps'] : [];
        $optionalStepIds = collect(app(OnboardingService::class)->forUser($user)['steps'])
            ->filter(fn (array $step) => ! $step['required'])
            ->pluck('id')
            ->values()
            ->all();

        if (array_key_exists('desktop_popup_dismissed', $data)) {
            $state['desktop_popup_dismissed'] = (bool) $data['desktop_popup_dismissed'];
        }
        if (array_key_exists('mobile_step', $data)) {
            $state['mobile_step'] = max(0, (int) $data['mobile_step']);
        }
        if (array_key_exists('desktop_tour_completed', $data)) {
            $state['desktop_tour_completed'] = (bool) $data['desktop_tour_completed'];
        }
        if (array_key_exists('desktop_tour_dismissed', $data)) {
            $state['desktop_tour_dismissed'] = (bool) $data['desktop_tour_dismissed'];
        }
        if (array_key_exists('desktop_tour_step', $data)) {
            $state['desktop_tour_step'] = max(0, (int) $data['desktop_tour_step']);
        }
        if (! empty($data['skip_step']) && in_array($data['skip_step'], $optionalStepIds, true)) {
            $state['skipped_steps'][] = $data['skip_step'];
            $state['skipped_steps'] = array_values(array_unique($state['skipped_steps']));
        }
        if (! empty($data['unskip_step'])) {
            $state['skipped_steps'] = array_values(array_filter(
                $state['skipped_steps'],
                fn (string $stepId) => $stepId !== $data['unskip_step']
            ));
        }

        $user->onboarding_state = $state;
        $user->save();

        return response()->json([
            'ok' => true,
            'state' => $state,
        ]);
    }

    private function isMobile(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        return str_contains($userAgent, 'mobile')
            || str_contains($userAgent, 'android')
            || str_contains($userAgent, 'iphone')
            || str_contains($userAgent, 'ipad');
    }
}
