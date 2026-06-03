<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_uses_requested_organization_nav_context(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Sunshine HOA']);

        $user->organizations()->attach($organization->id, ['role' => 'admin']);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit', ['organization' => $organization->slug]));

        $response
            ->assertOk()
            ->assertSee('Sunshine HOA')
            ->assertSee(route('organizations.index', $organization), false);
    }

    public function test_profile_page_falls_back_to_latest_user_organization_nav_context(): void
    {
        $user = User::factory()->create();
        $older = Organization::factory()->create(['name' => 'Older HOA']);
        $newer = Organization::factory()->create(['name' => 'Newer HOA']);

        $user->organizations()->attach($older->id, ['role' => 'admin']);
        $this->travel(1)->second();
        $user->organizations()->attach($newer->id, ['role' => 'admin']);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('Newer HOA')
            ->assertSee(route('organizations.index', $newer), false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
