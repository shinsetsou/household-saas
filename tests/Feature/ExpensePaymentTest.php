<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpensePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function actingMember(Organization $org, string $role = 'admin'): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => $role]);

        return $user;
    }

    public function test_store_creates_pending_expense_by_default(): void
    {
        Storage::fake('s3');
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);

        $response = $this->actingAs($user)->post(route('expenses.store', $org), [
            'amount' => '500.00',
            'description' => 'Hall cleaning',
        ]);

        $response->assertRedirect(route('expenses.index', $org));
        $expense = Expense::where('organization_id', $org->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame('pending', $expense->status);
        $this->assertNull($expense->paid_at);
        $this->assertNull($expense->receipt);
    }

    public function test_store_can_upload_receipt(): void
    {
        Storage::fake('s3');
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);

        $this->actingAs($user)->post(route('expenses.store', $org), [
            'amount' => '250',
            'description' => 'Receipted expense',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertRedirect(route('expenses.index', $org));

        $expense = Expense::where('organization_id', $org->id)->firstOrFail();
        $this->assertNotNull($expense->receipt);
        Storage::disk('s3')->assertExists($expense->receipt);
    }

    public function test_store_validates_receipt_mime_type(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);

        $this->actingAs($user)->post(route('expenses.store', $org), [
            'amount' => '100',
            'description' => 'Bad file',
            'receipt' => UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('receipt');
    }

    public function test_store_records_audit_log(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);

        $this->actingAs($user)->post(route('expenses.store', $org), [
            'amount' => '123.45',
            'description' => 'Audited',
        ])->assertRedirect();

        $expense = Expense::where('organization_id', $org->id)->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'expense.created',
            'auditable_type' => Expense::class,
            'auditable_id' => $expense->id,
        ]);
    }

    public function test_admin_can_mark_pending_expense_as_paid(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->create([
            'organization_id' => $org->id,
            'status' => Expense::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('expenses.mark-paid', [$org, $expense]))
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('paid', $expense->status);
        $this->assertNotNull($expense->paid_at);
        $this->assertSame($user->id, $expense->paid_by);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'expense.paid',
            'auditable_id' => $expense->id,
        ]);
    }

    public function test_treasurer_can_mark_expense_as_paid(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org, 'treasurer');
        $expense = Expense::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.mark-paid', [$org, $expense]))
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('paid', $expense->status);
    }

    public function test_viewer_cannot_mark_expense_as_paid(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org, 'viewer');
        $expense = Expense::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.mark-paid', [$org, $expense]))
            ->assertForbidden();

        $expense->refresh();
        $this->assertSame('pending', $expense->status);
    }

    public function test_cannot_mark_already_paid_expense(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->paid()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.mark-paid', [$org, $expense]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_admin_can_cancel_expense_with_reason(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.cancel', [$org, $expense]), [
                'cancellation_reason' => 'Vendor overcharged',
            ])
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('cancelled', $expense->status);

        $log = AuditLog::where('action', 'expense.cancelled')->first();
        $this->assertNotNull($log);
        $this->assertSame('Vendor overcharged', $log->metadata['reason']);
    }

    public function test_cannot_mark_cancelled_expense_as_paid(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->cancelled()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.mark-paid', [$org, $expense]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cancelled_expense_can_be_restored(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->cancelled()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post(route('expenses.restore', [$org, $expense]))
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('pending', $expense->status);
        $this->assertNull($expense->paid_at);
        $this->assertNull($expense->paid_by);
    }

    public function test_show_page_renders_receipt_and_status(): void
    {
        Storage::fake('s3');
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        $expense = Expense::factory()->paid()->create([
            'organization_id' => $org->id,
            'description' => 'Power bill',
            'receipt' => 'expenses/test-receipt.jpg',
            'paid_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('expenses.show', [$org, $expense]));

        $response->assertOk();
        $response->assertSee('Power bill');
        $response->assertSee('Paid', false);
    }

    public function test_status_filter_narrows_index(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        Expense::factory()->create(['organization_id' => $org->id, 'description' => 'Pending One']);
        Expense::factory()->paid()->create(['organization_id' => $org->id, 'description' => 'Paid One']);
        Expense::factory()->cancelled()->create(['organization_id' => $org->id, 'description' => 'Cancelled One']);

        $response = $this->actingAs($user)
            ->get(route('expenses.index', [$org, 'status' => 'paid']));

        $response->assertOk();
        $response->assertSee('Paid One');
        $response->assertDontSee('Pending One');
        $response->assertDontSee('Cancelled One');
    }

    public function test_unpaid_total_excludes_paid_and_cancelled(): void
    {
        $org = Organization::factory()->create();
        $this->actingMember($org);
        Expense::factory()->create(['organization_id' => $org->id, 'amount' => 100]);
        Expense::factory()->paid()->create(['organization_id' => $org->id, 'amount' => 200]);
        Expense::factory()->cancelled()->create(['organization_id' => $org->id, 'amount' => 50]);

        $this->assertSame(100.0, (float) Expense::forOrganization($org)->unpaid()->sum('amount'));
    }

    public function test_expense_belongs_to_own_org_only(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = $this->actingMember($orgA);
        $expenseInB = Expense::factory()->create(['organization_id' => $orgB->id]);

        $this->actingAs($user)
            ->get(route('expenses.show', [$orgA, $expenseInB]))
            ->assertNotFound();
    }
}
