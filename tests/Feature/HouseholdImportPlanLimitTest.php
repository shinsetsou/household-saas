<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\HouseholdCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HouseholdImportPlanLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => 'admin']);
    }

    protected function communityPlan(int $limit): Plan
    {
        return Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 19900,
            'max_households' => $limit,
            'sort_order' => 1,
        ]);
    }

    protected function csvUpload(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_');

        file_put_contents($path, $body);

        return new UploadedFile($path, 'households.csv', 'text/csv', null, true);
    }

    public function test_csv_import_partial_when_remaining_room_is_below_total_rows(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(10)->id]);

        $rows = ['name,block,lot'];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = "House {$i},1,{$i}";
        }
        for ($i = 6; $i <= 15; $i++) {
            $rows[] = "House {$i},2,".($i - 5);
        }

        $csv = implode("\n", $rows);

        $importer = $this->app->make(HouseholdCsvImporter::class);

        $result = $importer->import($this->organization->fresh(), $this->csvUpload($csv));

        $this->assertSame(10, $result->imported);
        $this->assertSame(10, Household::where('organization_id', $this->organization->id)->count());
    }

    public function test_csv_import_full_blocked_when_at_limit(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(3)->id]);

        for ($i = 1; $i <= 3; $i++) {
            Household::create([
                'organization_id' => $this->organization->id,
                'block' => 1,
                'lot' => $i,
                'name' => "House {$i}",
            ]);
        }

        $csv = "name,block,lot\nHouse 4,2,1\nHouse 5,2,2";

        $importer = $this->app->make(HouseholdCsvImporter::class);

        try {
            $result = $importer->import($this->organization->fresh(), $this->csvUpload($csv));
            $this->fail('Expected ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('csv', $e->errors());
        }

        $this->assertSame(3, Household::where('organization_id', $this->organization->id)->count());
    }

    public function test_duplicate_block_lot_inside_csv_is_caught(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(50)->id]);

        $csv = "name,block,lot\nA,1,1\nB,1,1";

        $importer = $this->app->make(HouseholdCsvImporter::class);

        $result = $importer->import($this->organization->fresh(), $this->csvUpload($csv));

        $this->assertSame(0, $result->imported);

        $this->assertSame(0, Household::where('organization_id', $this->organization->id)->count());
    }
}
