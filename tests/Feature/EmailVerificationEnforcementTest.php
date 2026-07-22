<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_implements_must_verify_email(): void
    {
        $user = new User;

        $this->assertInstanceOf(MustVerifyEmail::class, $user);
    }

    public function test_register_event_triggers_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'fresh@example.com',
            'name' => 'Fresh',
        ]);

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_can_reach_dashboard(): void
    {
        $user = User::factory()->create();
        Organization::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect();
    }

    public function test_unverified_user_is_bounced_from_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_is_bounced_from_tenant_dashboard(): void
    {
        $user = User::factory()->unverified()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_is_bounced_from_onboarding(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('onboarding.organization.create'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_is_bounced_from_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_can_still_see_verify_notice_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    public function test_unverified_user_can_still_resend_verification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_can_still_log_out(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect();
        $this->assertGuest();
    }

    public function test_already_verified_user_does_not_get_duplicate_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        event(new Registered($user));

        Notification::assertNothingSent();
    }
}
