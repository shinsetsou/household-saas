<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContributionBillingMonthTest extends TestCase
{
    use RefreshDatabase;

    private function member(Organization $org): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        return $user;
    }

    private function pngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'png').'.png';
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQAY3Y2wAAAAAElFTkSuQmCC'
        ));

        return new UploadedFile($path, 'receipt.png', 'image/png', null, true);
    }

    public function test_store_stamps_submitted_month_for_on_each_row(): void
    {
        Storage::fake('s3');

        $org = Organization::factory()->create(['monthly_due_amount' => 200]);
        $household = Household::factory()->create(['organization_id' => $org->id, 'block' => 1, 'lot' => 1]);

        $response = $this->post(route('contributions.store', [$org, $household]), [
            'amount' => 600,
            'month_for' => '2026-05',
            'photo' => $this->pngUpload(),
        ]);

        $response->assertSessionHas('success');

        $rows = Contribution::orderBy('id')->get();
        $this->assertCount(3, $rows, '₱600 over ₱200/mo should split into three rows (2 paid + 1 lacking)');
        $this->assertEquals('2026-05-01', $rows[0]->month_for->toDateString());
        $this->assertEquals('2026-06-01', $rows[1]->month_for->toDateString());
        $this->assertEquals('2026-07-01', $rows[2]->month_for->toDateString());
        $this->assertEquals('paid', $rows[0]->status);
        $this->assertEquals('paid', $rows[1]->status);
        $this->assertEquals('lacking', $rows[2]->status);
    }

    public function test_store_defaults_month_for_to_current_month_when_missing(): void
    {
        Storage::fake('s3');

        $org = Organization::factory()->create(['monthly_due_amount' => 200]);
        $household = Household::factory()->create(['organization_id' => $org->id, 'block' => 2, 'lot' => 2]);

        $this->travelTo(Carbon::create(2026, 8, 15));

        $this->post(route('contributions.store', [$org, $household]), [
            'amount' => 200,
            'photo' => $this->pngUpload(),
        ])->assertSessionHas('success');

        $this->assertEquals('2026-08-01', Contribution::first()->month_for->toDateString());

        $this->travelBack();
    }

    public function test_verify_can_overwrite_month_for_and_audit_change(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);

        $contribution = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-shift',
            'verification_status' => 'pending',
            'amount' => 200,
            'month_for' => Carbon::create(2026, 5, 1)->toDateString(),
        ]);

        $this->actingAs($user)->patch(
            route('contributions.verify.store', [$org, 'ref-shift']),
            [
                'verification_status' => 'verified',
                'month_for' => '2026-06',
            ]
        )->assertSessionHas('success');

        $contribution->refresh();
        $this->assertEquals('verified', $contribution->verification_status);
        $this->assertEquals('2026-06-01', $contribution->month_for->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contribution.verified',
            'organization_id' => $org->id,
        ]);
    }

    public function test_contribution_index_renders_billing_period_label(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'month_for' => Carbon::create(2026, 5, 1)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('contributions.index', $org));

        $response->assertOk();
        $response->assertSee('May 2026');
    }
}