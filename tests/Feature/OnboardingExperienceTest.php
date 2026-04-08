<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_with_incomplete_onboarding_is_redirected_to_onboarding_screen(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('2486'),
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => null,
            'public_slug' => 'seller-'.$user->id,
        ]);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile')
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.index'));
    }

    public function test_desktop_user_with_incomplete_onboarding_can_still_access_dashboard(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('2486'),
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => null,
            'public_slug' => 'seller-'.$user->id,
        ]);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->get(route('dashboard'))
            ->assertStatus(200);
    }

    public function test_onboarding_page_renders_for_incomplete_user(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('2486'),
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => null,
            'public_slug' => 'seller-'.$user->id,
        ]);

        $this->actingAs($user)
            ->get(route('onboarding.index'))
            ->assertStatus(200)
            ->assertSee('Onboarding checklist');
    }

    public function test_onboarding_state_endpoint_persists_desktop_and_mobile_state(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('2486'),
            'onboarding_state' => null,
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => null,
            'public_slug' => 'seller-'.$user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('onboarding.state'), [
                'desktop_popup_dismissed' => true,
                'mobile_step' => 2,
                'desktop_tour_step' => 1,
                'desktop_tour_completed' => false,
                'desktop_tour_dismissed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $state = $user->fresh()->onboarding_state;
        $this->assertTrue((bool) ($state['desktop_popup_dismissed'] ?? false));
        $this->assertSame(2, (int) ($state['mobile_step'] ?? 0));
        $this->assertSame(1, (int) ($state['desktop_tour_step'] ?? 0));
        $this->assertFalse((bool) ($state['desktop_tour_completed'] ?? true));
        $this->assertTrue((bool) ($state['desktop_tour_dismissed'] ?? false));
    }

    public function test_onboarding_state_skips_only_optional_steps(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('2486'),
            'onboarding_state' => null,
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => null,
            'public_slug' => 'seller-'.$user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('onboarding.state'), [
                'skip_step' => 'complete_profile',
            ])
            ->assertOk();

        $service = app(OnboardingService::class);
        $steps = collect($service->forUser($user->fresh())['steps'])->keyBy('id');

        $this->assertFalse((bool) ($steps['complete_profile']['skipped'] ?? false));

        $this->actingAs($user)
            ->postJson(route('onboarding.state'), [
                'skip_step' => 'share_store',
            ])
            ->assertOk();

        $steps = collect($service->forUser($user->fresh())['steps'])->keyBy('id');
        $this->assertTrue((bool) ($steps['share_store']['skipped'] ?? false));
    }
}
