<?php

namespace Tests\Feature\Auth;

use App\Models\SellerProfile;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhonePinAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_signup_and_pin_login_flow_works_when_enabled(): void
    {
        config()->set('auth_phone.enabled', true);

        $sms = \Mockery::mock(SmsOtpService::class);
        $sms->shouldReceive('sendOtp')->once()->andReturnTrue();
        $sms->shouldReceive('verifyOtp')->once()->andReturnTrue();
        $this->app->instance(SmsOtpService::class, $sms);

        $this->post(route('register.phone.send'), [
            'phone_country' => '+233',
            'phone_number' => '0541900229',
        ])->assertSessionHas('register_otp_status', 'sent');

        $response = $this->post(route('register.phone.complete'), [
            'phone_country' => '+233',
            'phone_number' => '+233541900229',
            'otp' => '123456',
            'name' => 'Phone Seller',
            'email' => 'phone-seller@example.com',
            'pin' => '2486',
            'pin_confirmation' => '2486',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'phone-seller@example.com')->firstOrFail();
        $this->assertNotNull($user->pin_hash);

        auth()->logout();

        $this->post(route('login.phone.pin'), [
            'phone_country' => '+233',
            'phone_number' => '0541900229',
            'pin' => '2486',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_phone_pin_reset_updates_pin(): void
    {
        config()->set('auth_phone.enabled', true);

        $user = User::factory()->create([
            'phone' => '+233541900229',
            'pin_hash' => Hash::make('2486'),
        ]);
        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Seller',
            'phone' => '+233541900229',
            'public_slug' => 'seller-'.$user->id,
        ]);

        $sms = \Mockery::mock(SmsOtpService::class);
        $sms->shouldReceive('sendOtp')->once()->andReturnTrue();
        $sms->shouldReceive('verifyOtp')->once()->andReturnTrue();
        $this->app->instance(SmsOtpService::class, $sms);

        $this->post(route('login.phone.pin.reset.send'), [
            'phone_country' => '+233',
            'phone_number' => '0541900229',
        ])->assertSessionHas('pin_reset_status', 'sent');

        $this->post(route('login.phone.pin.reset.complete'), [
            'phone_country' => '+233',
            'phone_number' => '+233541900229',
            'otp' => '123456',
            'reset_pin' => '8642',
            'reset_pin_confirmation' => '8642',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('8642', (string) $user->pin_hash));
        $this->assertFalse(Hash::check('2486', (string) $user->pin_hash));
    }

    public function test_phone_auth_routes_and_ui_fallback_when_disabled(): void
    {
        config()->set('auth_phone.enabled', false);

        $this->post(route('login.phone.pin'), [
            'phone_country' => '+233',
            'phone_number' => '0541900229',
            'pin' => '1234',
        ])->assertRedirect(route('login'));

        $this->post(route('register.phone.send'), [
            'phone_country' => '+233',
            'phone_number' => '0541900229',
        ])->assertRedirect(route('register'));

        $this->get(route('login'))
            ->assertStatus(200)
            ->assertDontSee('Phone PIN')
            ->assertSee('Sign in with email');

        $this->get(route('register'))
            ->assertStatus(200)
            ->assertDontSee('Phone signup is faster')
            ->assertSee('Create your account with email');
    }
}

