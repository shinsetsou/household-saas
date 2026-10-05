<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function show(Organization $organization): View
    {
        return view('organizations.settings.general', compact('organization'));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request, Organization $organization): RedirectResponse
    {
        $before = [
            'name' => $organization->name,
            'monthly_due_amount' => (string) $organization->monthly_due_amount,
            'manual_payment_instructions' => (string) $organization->manual_payment_instructions,
        ];

        $organization->fill($request->validated())->save();

        $after = [
            'name' => $organization->name,
            'monthly_due_amount' => (string) $organization->monthly_due_amount,
            'manual_payment_instructions' => (string) $organization->manual_payment_instructions,
        ];

        $changes = [];
        foreach ($before as $field => $oldValue) {
            if ($oldValue !== $after[$field]) {
                $changes[$field] = [$oldValue, $after[$field]];
            }
        }

        if ($changes === []) {
            return redirect()
                ->route('organizations.settings.show', $organization->slug)
                ->with('info', 'No changes to save.');
        }

        AuditLog::record(
            $organization,
            'organization.settings.updated',
            $request->user(),
            $organization,
            ['changes' => $changes],
        );

        return redirect()
            ->route('organizations.settings.show', $organization->slug)
            ->with('success', 'Organization settings saved.');
    }
}
