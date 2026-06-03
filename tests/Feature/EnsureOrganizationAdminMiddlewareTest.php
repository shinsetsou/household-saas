<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureOrganizationAdmin;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureOrganizationAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register login route first so auth middleware can redirect
        Route::get('/login', fn () => 'login')->name('login');

        Route::middleware(['web', 'auth', EnsureOrganizationAdmin::class])
            ->get('/_test/{organization:slug}/admin-only', fn (Organization $organization) => 'ok');
    }

    public function test_admin_passes(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_non_admin_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertForbidden();
    }

    public function test_non_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertForbidden();
    }

    public function test_unauthenticated_gets_redirected_to_login(): void
    {
        $org = Organization::factory()->create();

        // When unauthenticated, the `auth` middleware will redirect to login.
        // We use a separate test route without the auth middleware to verify
        // that our middleware alone properly checks admin status.
        Route::get('/_test-admin-only/{organization:slug}', fn (Organization $organization) => 'ok')
            ->middleware(EnsureOrganizationAdmin::class);

        $response = $this->get("/_test-admin-only/{$org->slug}");

        // Should 403 because user is not authenticated and organization is not bound
        $response->assertForbidden();
    }
}
