<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\HouseholdCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HouseholdImportSampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_download_sample_csv(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('households.import.sample', $org));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('name,block,lot', $response->streamedContent());
    }

    public function test_sample_csv_is_valid_for_import(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $csv = $this->actingAs($user)->get(route('households.import.sample', $org))->streamedContent();

        $path = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'households-sample.csv', 'text/csv', null, true);

        $result = app(HouseholdCsvImporter::class)->import($org, $file);

        $this->assertTrue($result->successful());
        $this->assertSame(3, $result->imported);
    }

    public function test_sample_download_requires_membership(): void
    {
        $org = Organization::factory()->create();

        $this->get(route('households.import.sample', $org))->assertRedirect(route('login'));
    }
}
