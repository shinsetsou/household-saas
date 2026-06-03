<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoneStepController extends Controller
{
    public function show(Request $request): View
    {
        $id = $request->session()->get('onboarding.organization_id');

        if (! $id) {
            abort(redirect()->route('onboarding.organization.create'));
        }

        $organization = Organization::findOrFail($id);
        $householdCount = $organization->households()->count();

        $request->session()->forget('onboarding.organization_id');

        return view('onboarding.done', compact('organization', 'householdCount'));
    }
}
