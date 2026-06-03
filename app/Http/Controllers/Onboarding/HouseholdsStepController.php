<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\HouseholdCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseholdsStepController extends Controller
{
    public function create(Request $request): View
    {
        $organization = $this->resolveOrganization($request);

        return view('onboarding.households', compact('organization'));
    }

    public function store(Request $request, HouseholdCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $organization = $this->resolveOrganization($request);

        $result = $importer->import($organization, $request->file('file'));

        if (! $result->successful()) {
            return back()->withErrors(['file' => implode(' ', $result->errors)]);
        }

        return redirect()->route('onboarding.done.show');
    }

    public function skip(): RedirectResponse
    {
        return redirect()->route('onboarding.done.show');
    }

    private function resolveOrganization(Request $request): Organization
    {
        $id = $request->session()->get('onboarding.organization_id');

        if (! $id) {
            abort(redirect()->route('onboarding.organization.create'));
        }

        return Organization::findOrFail($id);
    }
}
