<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContributionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_resident_redirected_to_household_dashboard_not_login(): void
    {
        // Receipts now upload to the s3/B2 disk — fake it so the test never hits real B2.
        Storage::fake('s3');

        $organization = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $organization->id,
            'block' => 5,
            'lot' => 12,
        ]);

        // Real 1x1 PNG bytes — avoids the GD extension that fake()->image() requires
        // and satisfies the `mimetypes:image/png` validation (checked via file content).
        $pngPath = tempnam(sys_get_temp_dir(), 'png').'.png';
        file_put_contents($pngPath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQAY3Y2wAAAAAElFTkSuQmCC'
        ));
        $photo = new UploadedFile($pngPath, 'receipt.png', 'image/png', null, true);

        $response = $this->post(
            route('contributions.store', [$organization, $household]),
            [
                'amount' => 200,
                'photo' => $photo,
            ]
        );

        // Must land on the public household dashboard (block+lot lookup), NOT the login page.
        $response->assertRedirect(route('households.index', [
            'organization' => $organization,
            'block' => $household->block,
            'lot' => $household->lot,
        ]));
        $response->assertSessionHas('success');

        // Receipt must be persisted to the s3/B2 disk, not the local public disk.
        $contribution = Contribution::first();
        $this->assertNotNull($contribution);
        $this->assertNotNull($contribution->photo);
        $this->assertStringStartsWith('contributions/', $contribution->photo);
        Storage::disk('s3')->assertExists($contribution->photo);
        Storage::disk('public')->assertMissing($contribution->photo);
    }
}
