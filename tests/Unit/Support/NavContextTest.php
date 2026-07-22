<?php

namespace Tests\Unit\Support;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\NavContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_with_no_org_and_no_user(): void
    {
        $ctx = NavContext::build(null, null);

        $this->assertNull($ctx->organization);
        $this->assertNull($ctx->user);
        $this->assertNull($ctx->role);
        $this->assertFalse($ctx->isAdmin);
        $this->assertSame('?', $ctx->userInitials);
    }

    public function test_builds_admin_context(): void
    {
        $user = User::factory()->create(['name' => 'Karl Candelario']);
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => OrganizationRole::Admin->value]);

        $ctx = NavContext::build($org, $user);

        $this->assertSame(OrganizationRole::Admin, $ctx->role);
        $this->assertTrue($ctx->isAdmin);
        $this->assertSame('KC', $ctx->userInitials);
    }

    public function test_builds_treasurer_context(): void
    {
        $user = User::factory()->create(['name' => 'Maria Santos']);
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => OrganizationRole::Treasurer->value]);

        $ctx = NavContext::build($org, $user);

        $this->assertSame(OrganizationRole::Treasurer, $ctx->role);
        $this->assertFalse($ctx->isAdmin);
        $this->assertSame('MS', $ctx->userInitials);
    }

    public function test_builds_viewer_context(): void
    {
        $user = User::factory()->create(['name' => 'Juan']);
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => OrganizationRole::Viewer->value]);

        $ctx = NavContext::build($org, $user);

        $this->assertSame(OrganizationRole::Viewer, $ctx->role);
        $this->assertFalse($ctx->isAdmin);
        $this->assertSame('J', $ctx->userInitials);
    }

    public function test_initials_use_first_two_words(): void
    {
        $user = User::factory()->create(['name' => 'Karl Lyndon Candelario']);
        $ctx = NavContext::build(null, $user);

        $this->assertSame('KL', $ctx->userInitials);
    }

    public function test_initials_handle_empty_name(): void
    {
        $user = User::factory()->create(['name' => '']);
        $ctx = NavContext::build(null, $user);

        $this->assertSame('?', $ctx->userInitials);
    }

    public function test_initials_handle_single_word(): void
    {
        $user = User::factory()->create(['name' => 'Cher']);
        $ctx = NavContext::build(null, $user);

        $this->assertSame('C', $ctx->userInitials);
    }

    public function test_user_without_membership_yields_no_role(): void
    {
        $user = User::factory()->create(['name' => 'Stranger']);
        $org = Organization::factory()->create();

        $ctx = NavContext::build($org, $user);

        $this->assertNull($ctx->role);
        $this->assertFalse($ctx->isAdmin);
    }

    public function test_legacy_role_string_member_yields_null_role(): void
    {
        $user = User::factory()->create(['name' => 'Old']);
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'member']);

        $ctx = NavContext::build($org, $user);

        $this->assertNull($ctx->role);
        $this->assertFalse($ctx->isAdmin);
    }
}
