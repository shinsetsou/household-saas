<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionVerificationViewTest extends TestCase
{
    use RefreshDatabase;

    private function member(Organization $org): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        return $user;
    }

    public function test_status_filter_shows_only_matching_contributions(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id, 'name' => 'Reyes']);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'pending', 'amount' => 111]);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'verified', 'amount' => 222]);

        $pending = $this->actingAs($user)->get(route('contributions.index', [$org, 'status' => 'pending']));
        $pending->assertOk();
        $pending->assertSee('111.00');
        $pending->assertDontSee('222.00');

        $all = $this->actingAs($user)->get(route('contributions.index', $org));
        $all->assertSee('111.00');
        $all->assertSee('222.00');
    }
}
