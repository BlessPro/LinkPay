<?php

namespace Tests\Feature;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_and_terms_pages_are_publicly_available(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSeeText('Privacy Policy');

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSeeText('Terms of Service');
    }

    public function test_authenticated_user_can_submit_data_deletion_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('legal.data-deletion.store'), [
                'note' => 'Please remove my data.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'data-deletion-requested');

        $this->assertDatabaseHas('data_deletion_requests', [
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => DataDeletionRequest::STATUS_PENDING,
            'note' => 'Please remove my data.',
        ]);
    }
}

