<?php

namespace Tests\Unit;

use App\Models\Household;
use App\Models\Organization;
use App\Services\HouseholdCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvHouseholdImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_rows_import_households(): void
    {
        $organization = Organization::factory()->create();
        $file = UploadedFile::fake()->createWithContent('households.csv', "name,block,lot\nReyes,1,2\nCruz,1,3\n");

        $result = app(HouseholdCsvImporter::class)->import($organization, $file);

        $this->assertTrue($result->successful());
        $this->assertSame(2, Household::forOrganization($organization)->count());
    }

    public function test_duplicate_block_lot_fails_without_importing_rows(): void
    {
        $organization = Organization::factory()->create();
        Household::factory()->create(['organization_id' => $organization->id, 'block' => 1, 'lot' => 2]);
        $file = UploadedFile::fake()->createWithContent('households.csv', "name,block,lot\nReyes,1,2\nCruz,1,3\n");

        $result = app(HouseholdCsvImporter::class)->import($organization, $file);

        $this->assertFalse($result->successful());
        $this->assertSame(1, Household::forOrganization($organization)->count());
    }

    public function test_missing_required_fields_fail_without_importing_rows(): void
    {
        $organization = Organization::factory()->create();
        $file = UploadedFile::fake()->createWithContent('households.csv', "name,block,lot\n,1,2\nCruz,,3\n");

        $result = app(HouseholdCsvImporter::class)->import($organization, $file);

        $this->assertFalse($result->successful());
        $this->assertSame(0, Household::forOrganization($organization)->count());
    }
}
