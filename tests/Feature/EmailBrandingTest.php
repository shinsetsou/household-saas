<?php

namespace Tests\Feature;

use App\Mail\Auth\ResetPasswordMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\ContributionAdded;
use App\Mail\OrganizationInvitationMail;
use App\Mail\SalesFollowupMail;
use App\Mail\TrialEndingMail;
use App\Mail\TrialExpiredMail;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailBrandingTest extends TestCase
{
    use RefreshDatabase;

    private const LOGO_URL = 'http://localhost/android-chrome-512x512.png';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
    }

    public function test_reset_password_email_renders_branded_header_footer(): void
    {
        $user = User::factory()->make(['name' => 'Test User', 'email' => 'test@example.com']);
        $mailable = new ResetPasswordMail($user, 'http://localhost/password-reset');

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_verify_email_email_renders_branded_header_footer(): void
    {
        $user = User::factory()->make(['name' => 'Test User', 'email' => 'test@example.com']);
        $mailable = new VerifyEmailMail($user, 'http://localhost/verify-email');

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_contribution_added_email_renders_branded_header_footer(): void
    {
        $mailable = new ContributionAdded([
            'amount' => 200.00,
            'reference_number' => 'GCASH-12345',
            'household_id' => 1,
        ]);

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_organization_invitation_email_renders_branded_header_footer(): void
    {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->create();
        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => 'invited@example.com',
            'role' => 'viewer',
            'invited_by' => $inviter->id,
        ]);

        $mailable = new OrganizationInvitationMail($invitation);

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_sales_followup_email_renders_branded_header_footer(): void
    {
        $lead = new Lead(['email' => 'lead@example.com']);
        $mailable = new SalesFollowupMail($lead, Lead::FOLLOWUP_DAY_0);

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_trial_ending_email_renders_branded_header_footer(): void
    {
        $organization = Organization::factory()->create([
            'trial_ends_at' => now()->addDays(3),
        ]);
        $mailable = new TrialEndingMail($organization, 3);

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    public function test_trial_expired_email_renders_branded_header_footer(): void
    {
        $organization = Organization::factory()->create([
            'trial_ends_at' => now()->subDay(),
        ]);
        $mailable = new TrialExpiredMail($organization);

        $html = $mailable->render();

        $this->assertBranded($html);
    }

    private function assertBranded(string $html): void
    {
        $this->assertStringContainsString(self::LOGO_URL, $html, 'Branded logo URL missing.');
        $this->assertStringContainsString('alt="BarangayBooks"', $html, 'Logo alt text missing.');
        $this->assertStringContainsString('https://barangaybooks.com', $html, 'Footer link missing.');
        $this->assertStringContainsString('hello@barangaybooks.app', $html, 'Contact email missing.');
        $this->assertStringNotContainsString('http://barangaybooks.app', $html, 'Dead http barangaybooks.app link found.');
        $this->assertStringNotContainsString('https://barangaybooks.app', $html, 'Dead https barangaybooks.app link found.');
    }
}
